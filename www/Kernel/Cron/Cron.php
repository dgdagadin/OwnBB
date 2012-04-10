<?php

//config
include ('../../config.php');

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Bad validation!');
}

//Подключение основных библиотек
include ('../../' . OBB_KERNEL_DIR . '/DBLib.php');
include ('../../' . OBB_KERNEL_DIR . '/CacheFunctions.php');
include ('../../' . OBB_KERNEL_DIR . '/ForumFunctions.php');
include ('../../' . OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');

//настройки интерпретатора
error_reporting(E_ALL);
set_time_limit(0);

//Соединение с сервером
$ForumConnection = DB_Connect ($Config_DBType);
if (!$ForumConnection) {
	exit ('Incorrect connect!');
}
DB_Query($Config_DBType, "SET NAMES utf8", $ForumConnection);

//Выбор базы данных
$ForumDatabase = DB_SelectDatabase ($Config_DBType, OBB_CONNECTION_DB, $ForumConnection);
if (!$ForumDatabase) {
	exit ('Incorrect database!');
}

//действие в соответствии с параметром shell
$CronAction = $_SERVER['argv'][1];

switch (Main_Strtolower ($CronAction)) {
	//удаляет неактивных пользователей/гостей
	case'online':
	default: {
		//текущее время
		$CurrentTime = time ();

		//  --1)гости
		$DelGuestSQL = 'DELETE FROM guest_activity WHERE GuestLastUpdate < (' . $CurrentTime . ' - ' . OBB_USER_ONLINE_TIME . ')';
		$DelGuestQuery = DB_Query ($Config_DBType, $DelGuestSQL, $ForumConnection);
		if (!$DelGuestQuery) {
			OBB_Main_Log ($DelGuestSQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}

		//  --2)пользователи
		$UserActivitySQL = 'UPDATE user_activity SET UserIsOnline=\'no\' WHERE UserLastLogin < (' . $CurrentTime . ' - ' . OBB_USER_ONLINE_TIME . ')';
		$UserActivityQuery = DB_Query ($Config_DBType, $UserActivitySQL, $ForumConnection);
		if (!$UserActivityQuery) {
			OBB_Main_Log ($UserActivitySQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}

		//  --3)кэш онлайна
		OBB_Cache_DeleteCacheFile (OBB_CACHE_DIR . '/DBCache/users_online');
		$UsersTempArray = array ();
		$UsersOnlineSQL = 'SELECT users.UserID AS UID, 
							users.UserLogin AS ULogin,
							users.GroupID as UGroup
						FROM users 
						LEFT JOIN user_activity ON users.UserID = user_activity.UserID 
						WHERE user_activity.UserIsOnline = \'yes\'';
		$UsersOnlineQuery = DB_Query ($Config_DBType, $UsersOnlineSQL, $ForumConnection);
		if (!$UsersOnlineQuery) {
			OBB_Main_Log ($UsersOnlineSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$Return[] = DB_NumRows ($Config_DBType, $UsersOnlineQuery);
		while ($UsersOnlineRow = DB_FetchAssoc ($Config_DBType, $UsersOnlineQuery)) {
			$UID    = $UsersOnlineRow['UID'];
			$ULogin = $UsersOnlineRow['ULogin'];
			$UGroup = $UsersOnlineRow['UGroup'];
			$OneUserArray = array ('id'=>$UID, 'login'=>$ULogin, 'group'=>$UGroup);
			$UsersTempArray[] = $OneUserArray;
		}
		OBB_Cache_PutCacheArray (OBB_CACHE_DIR . '/DBCache/users_online', $AddingValuesArray);

		break;
	}

	//очищает пользователей, не активиров. в течение недели
	case'users': {
		//одна неделя
		$OneWeek = 3600*24*7;
		$NowTime = time ();

		//выборка пользователей
		$SQL = 'SELECT UserID FROM users WHERE CONVERT(UserRegDate, UNSIGNED) < ' . ($NowTime - $OneWeek);
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}
		if (DB_NumRows ($Config_DBType, $Query) > 0) {
			//массив
			$UsersArray = array ();
			while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
				$UsersArray[] = array ($Row['UserID'], $Row['UserAvatar']);
			}

			//удаление файлов активации
			foreach ($UsersArray as $key=>$value) {
				@unlink ('../../' . OBB_ACTIVATION_DIR . '/' . sha1 ($value[0]));
			}

			//удаление аватаров
			foreach ($UsersArray as $key=>$value) {
				if (!OBB_Main_IsEmpty ($value[1])) {
					@unlink ('../../' . OBB_AVATAR_DIR . '/' . $value[0] . '.' . $value[1]);
				}
			}

			//удаление пользователей
			//  --1)из таблицы user_activity
			$SQL = 'DELETE FROM user_activity WHERE UserID IN (' . implode(',', $UsersArray) . ')';
			$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($UserActivitySQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
			}

			//  --2)из таблицы users
			$SQL = 'DELETE FROM users WHERE UserID IN (' . implode(',', $UsersArray) . ')';
			$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($UserActivitySQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
			}
		}

		break;
	}

	//очищает таблицу поиска от устаревших записей
	case'search': {
		//количество часов, в течение которых актуальна поисковая информация
		$ActualSearchTime = OBB_MAIN_SEARCH_ACTUAL_TIME * 3600;

		//запрос
		$SQL = 'DELETE FROM search_data WHERE CONVERT(SearchDate, UNSIGNED) < ' . (time() - $ActualSearchTime);
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}

		break;
	}

	//очищает таблицу банов ользователей от устаревших банов
	case'ban': {
		//сейчас
		$NowTimeString = intval (time());

		//запрос
		$SQL = 'DELETE FROM user_bans WHERE CONVERT(UserBanTime, UNSIGNED) < ' . $NowTimeString . ' - UserBanPeriod';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}

		break;
	}

	//очищает кэш сообщений, не использовавшихся более n времени
	case'postcache': {
	
		break;
	}
}

?>