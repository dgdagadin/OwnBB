<?php

//проверка системной переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//доступ сюда разрешен только пользователям
if ($_SESSION['UserData']['UserType'] == 'guest') {
	OBB_Main_Redirect ();
}

$UserID = intval ($_SESSION['UserData']['UserID']);

//переменная редиректа
$RedirectURL = 'http://' . $HostName . $SelfName;
$RedirectURLPattern = '/http:\/\/' . preg_quote ($HostName, "/") . preg_quote ($SelfName, "/") . '(?:\?action=[_a-z]{3-10}(?:&[_a-z]{2,20}=(.*?))*)?/ui';
if (isset ($_SERVER['HTTP_REFERER'])) {
	if (preg_match ($RedirectURLPattern, $_SERVER['HTTP_REFERER'])) {
		preg_match_all ('/\?action=(.*?)&/ui', $_SERVER['HTTP_REFERER'], $matches);
		if (!isset ($matches[1][0]) || Main_Strtolower ($matches[1][0]) == 'message' || Main_Strlen ($matches[1][0]) < 1) {
			$RedirectURL = 'http://' . $HostName . $SelfName;
		}
		else {
			$RedirectURL = $_SERVER['HTTP_REFERER'];
		}
	}
}

//редактирование статуса - оффлайн
$SQLUpdateUser = 'UPDATE user_activity
				SET UserIsOnline=\'no\'
				WHERE UserID=\'' . $UserID . '\'';
$UpdateUserQuery = DB_Query ($Config_DBType, $SQLUpdateUser, $ForumConnection);
if (!$UpdateUserQuery) {
	OBB_Main_Log ($SQLUpdateUser . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//УНИЧТОЖИТЬ КУКИ - ОБЯЗАТЕЛЬНО!!! - на час назад :)
setcookie($Config_SecureCookieName, "", time()-3600);

//если пользователь был в автологине
if (isset ($_COOKIE[$Config_AutologinCookieName])) {
	$SecureString = OBB_Main_SetAuthorizationString ();

	// 1)удаление из таблицы автологина записи с таким UserID и AutoLoginString
	$SQL = 'DELETE FROM user_autologins WHERE UserID = \'' . $UserID . '\' AND AutoLoginString = \'' . $SecureString . '\'';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	// 2)УДАЛЕНИЕ КУКИ АВТОЛОГИНА - на час назад :)
	setcookie($Config_AutologinCookieName, "", time()-3600);
}

//регенерация ид сессии
session_regenerate_id (TRUE);

unset ($_SESSION['UserData']);
$_SESSION['UserData'] = array ();
$_SESSION['UserData']['UserType'] = 'guest';
$_SESSION['UserData']['GroupID'] = '3';

//добавление в таблицу гостей (или изменение сущ. записи)
// 1)проверка, есть ли в гостевой таблице такой ИП
$SQL = 'SELECT 1 FROM guest_activity WHERE GuestIPAddress = \'' . Defence_EscapeString ($Config_DBType, $UserIP) . '\'';
$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
if (!$Query) {
	OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

// 2)если нет, то добавляем
if (DB_NumRows ($Config_DBType, $Query) < 1) {
	$GuestInsertSQL = 'INSERT INTO guest_activity (GuestIPAddress, GuestLastUpdate) VALUES (\'' . Defence_EscapeString ($Config_DBType, $UserIP) . '\', \'' . time () . '\')';
	$GuestInsertQuery = DB_Query ($Config_DBType, $GuestInsertSQL, $ForumConnection);
	if (!$GuestInsertQuery) {
		OBB_Main_Log ($GuestInsertSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
}

//перенаправление
$_SESSION['Message']  = 'logout_sucess';
$_SESSION['Redirect'] = str_replace ('http://' . $HostName . $SelfName, '', $RedirectURL);
$URL = '?action=message';
OBB_Main_Redirect ($URL);

?>