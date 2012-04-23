<?php

///////////////////////////////////////////главные функции///////////////////////////////////////////

//функция логирования
function OBB_Main_Log ($Message, $LogFile, $IsExit = TRUE) {
	$Microtime = explode(" ",microtime());
	$MicroSecond = explode(".", $Microtime[0]);
	$EventTime = date("Y-m-d H:i:s") . "." . $MicroSecond[1];

	$Backtrace = debug_backtrace ();
	$Caller    = array_shift ($Backtrace);
	$FILE = $Caller['file'];
	$LINE = $Caller['line'];

	$LogString  = "\r\n";
	$LogString .= '[TIME:' . $EventTime . '; FILE:' . $FILE . '; LINE:' . $LINE . ']';
	$LogString .= "\r\n";
	$LogString .= $Message;
	$LogString .= "\r\n";
	$LogString .= '[MESSAGE_END]';
	$LogString .= "\r\n";

	$FileFlag = 'a+';
	$FileHandler = fopen ($LogFile, $FileFlag);
	flock($FileHandler, LOCK_EX);
	$LogWrite = fwrite ($FileHandler, $LogString);
	if (!$LogWrite) {
		exit ('Error while logging');
	}
	flock($FileHandler, LOCK_UN);
	fclose ($FileHandler);

	if ($IsExit) {
		exit ('error. watch logs.');
	}
}

//функция соединения с сервером
function OBB_Main_ConnectDBServer () {
	global $Config_DBType;

	$Return = DB_Connect ($Config_DBType);
	if (!$Return) {
		exit ('Incorrect connect!');
	}
	return ($Return);
}

//функция выбора БД
function OBB_Main_SelectDatabase () {
	global $Config_DBType, $ForumConnection;

	$ForumDatabase = DB_SelectDatabase ($Config_DBType, OBB_CONNECTION_DB, $ForumConnection);
	if (!$ForumDatabase) {
		exit ('Incorrect database!');
	}
	return ($ForumDatabase);
}

//функция получения IP-адреса пользователя
function OBB_Main_GetUserIPAddress () {
	$Return = $_SERVER['REMOTE_ADDR'];
	return ($Return);
}

function OBB_Main_SetDatabaseUTF8 () {
	global $Config_DBType, $ForumConnection;

	switch ($Config_DBType) {
		case'mysql':
		default: {
			DB_Query($Config_DBType, "SET NAMES utf8", $ForumConnection);
			break;
		}
	}
}

//функция получения текущего действия
function OBB_Main_GetForumAction () {
	$Action = isset ($_GET['action']) ? $_GET['action'] : 'main';
	$Action = Main_Strtolower ($Action);
	return ($Action);
}

//функция определения текущего режима выполнения (обычный или "сервер недоступен")
function OBB_Main_SetMaintenanceMode () {
	include (OBB_ADMIN_DIR . '/AdminMaintenance.php');
}

//функция получения текущего модуля
function OBB_Main_GetModule ($ActionArray, $ModuleArray, $Variable) {
	if (!isset ($_GET[$Variable]) || !in_array ($_GET[$Variable], $ActionArray)) {
		$Label = $ActionArray[0];
	}
	else {
		$Label = $_GET[$Variable];
	}
	$Return = $ModuleArray[$Label];
	return ($Return);
}

//функция получения массива настроек почты
function OBB_Main_GetMailConfig () {
	$Config_Mail = array('FromName'      => OBB_MAIL_FROM_NAME,
						'FromMail'       => OBB_MAIL_FROM_MAIL,
						'CurrentCharset' => OBB_MAIL_CURRENT_CHARSET,
						'TrueCharset'    => OBB_MAIL_TRUE_CHARSET,
						'MailMime'       => OBB_MAIL_MAIL_MIME,
						'AdminMail'      => OBB_MAIL_ADMIN_MAIL);
	return ($Config_Mail);
}

//функция настройки интерпретатора
function OBB_Main_SetPHPConfigs () {
	global $DocRoot;
	error_reporting(E_ALL);
	ini_set('magic_quotes_runtime', 0);
	ini_set('magic_quotes_sybase', 0);
	set_time_limit(0);
	//ini_set('session.save_path', $DocRoot . '/' . OBB_ROOT_DIR . '/' . OBB_SESSION_DIR);
	//session_cache_expire(180);
	ini_set('register_globals', 'Off');
	ini_set('session.use_trans_sid', FALSE);
	ini_set('session.use_only_cookies', TRUE);
}

//функция получения списка и количества зарегистрированных пользователей онлайн
function OBB_Main_GetUsersOnline () {
	global $Config_DBType, $ForumConnection;

	$Return = array ();
	if (!file_exists (OBB_CACHE_DIR . '/DBCache/users_online')) {
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
		$Return[] = $UsersTempArray;
	}
	else {
		$UsersTempArray = OBB_Cache_GetCacheArray (OBB_CACHE_DIR . '/DBCache/users_online');
		$Return[] = sizeof ($UsersTempArray);
		$Return[] = $UsersTempArray;
	}
	return ($Return);
}

//ф-ция проверки бана по ип или по идентификатору пользователя
function OBB_Main_CheckBanned ($UserBanArray) {
	global $ForumConnection, $Config_DBType;

	$UserIP = $UserBanArray['ip'];
	$UserID = $UserBanArray['id'];

	$Result = array ();

	$IPIsBanned   = false;
	$IDIsBanned   = false;
	$IdBannedFull = false;
	$IdBannedAdd  = false;
	$IdBannedTime = false;

	//1)Бан по ИП
	if (OBB_IP_BAN) {
		$UserIP = Defence_EscapeString ($Config_DBType, $UserIP);
		$SQL = 'SELECT 1 FROM  ip_bans WHERE BanString = \'' . $UserIP . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		if (DB_NumRows ($Config_DBType, $Query) > 0) {
			$IPIsBanned = TRUE;
		}
	}

	//2)Бан конкретного пользователя по его ИД
	if (OBB_ID_BAN && !OBB_Main_IsEmpty ($UserID)) {
		$UserID = intval ($UserID);
		$SQL = 'SELECT * FROM user_bans WHERE UserID = \'' . $UserID . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$IsBanned = DB_NumRows ($Config_DBType, $Query);
		if ($IsBanned > 0) {
			$UserBanDate   = DB_Result ($Config_DBType, $Query, 0, 'UserBanTime');
			$UserBanPeriod = DB_Result ($Config_DBType, $Query, 0, 'UserBanPeriod');
			$UserBanMethod = DB_Result ($Config_DBType, $Query, 0, 'UserBanMethod');

			$IDIsBanned = TRUE;

			if ((time() - $UserBanDate) < $UserBanPeriod && intval ($UserBanPeriod) <> 0) {
				$IdBannedFull = $UserBanMethod == 'full' ? TRUE : false;
				$IdBannedAdd  = $UserBanMethod == 'add'  ? TRUE : false;
				$IdBannedTime = time() - $UserBanDate;
				$IdBannedTime = $UserBanPeriod - $IdBannedTime;
				$IdBannedTime = intval ($IdBannedTime);
			}
		}
	}

	$Result = array ('ip'      => $IPIsBanned,
					'id'      => $IDIsBanned,
					'id_full' => $IdBannedFull,
					'id_add'  => $IdBannedAdd,
					'id_time' => $IdBannedTime);

	return ($Result);
}

function OBB_Main_GetJSLanguages () {
	$Return = array ('Rus' => 'ru');
	return ($Return);
}

//функция проверки IP-адреса
function OBB_Main_CheckIPAddress ($RealUserIP, $GotUserIP) {
	$IPPattern = '/\d{1,3}(\.\d{1,3}){3}/ui';
	if (!preg_match ($IPPattern, $RealUserIP) || !preg_match ($IPPattern, $GotUserIP)) {
		return (false);
	}
	$RealUserIPArray = explode ('.', $RealUserIP);
	$GotUserIPArray  = explode ('.', $GotUserIP);
	$RealUserIPNewArray = array ();
	$GotUserIPNewArray  = array ();
	for ($i=0; $i<OBB_NUM_IP_OCTETS; $i++) {
		$RealUserIPNewArray[] = $RealUserIPArray[$i];
		$GotUserIPNewArray[]  = $GotUserIPArray[$i];
	}
	$RealUserIPString = implode ('.', $RealUserIPNewArray);
	$GotUserIPString  = implode ('.', $GotUserIPNewArray);
	if ((string)$RealUserIPString <> (string)$GotUserIPString) {
		return (false);
	}
	return (TRUE);
}

//функция проверки автологина
function OBB_Main_Autologin () {
	global $ForumConnection, $Config_DBType, $Config_AutologinCookieName,
			$Config_AuthSalt, $Config_SecureKey, $Config_SecureCookieName;

	if ($_SESSION['UserData']['UserType'] == 'guest' && isset ($_COOKIE[$Config_AutologinCookieName])) {
		//вычисление скеретной строки на основе данных, полученных от пользователя
		$SecureString = OBB_Main_SetAuthorizationString ();

		$UserIP = OBB_Main_GetUserIPAddress ();

		//строка автологина, получаем из $_COOKIE[Config_AutologinCookieName]
		$AutologinString = $_COOKIE[$Config_AutologinCookieName];
		$AutologinString = Defence_EscapeString ($Config_DBType, $AutologinString);

		//проверка, есть ли пользователь с таким автологином
		$SQL = 'SELECT users.UserID AS UserID,
					users.UserLogin AS UserLogin,
					users.GroupID AS GroupID,
					user_autologins.AutoLoginIP AS UserIP
				FROM users, user_autologins
				WHERE users.UserID = user_autologins.UserID
				AND users.UserAutoLogin = \'' . $AutologinString . '\'
				AND user_autologins.AutoLoginString = \'' . $SecureString . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		//есть ли такой автологин в БД
		$IsAutologin = DB_NumRows ($Config_DBType, $Query);
		if ($IsAutologin < 1) {
			return (false);
		}

		//присвоение
		$UserID    = DB_Result ($Config_DBType, $Query, 0, 0);
		$UserLogin = DB_Result ($Config_DBType, $Query, 0, 1);
		$UserGroup = DB_Result ($Config_DBType, $Query, 0, 2);
		$UserDBIP  = DB_Result ($Config_DBType, $Query, 0, 3);

		//проверка реального IP-адреса с тем, что лежит в БД
		if (!OBB_Main_CheckIPAddress ($UserIP, $UserDBIP)) {
			return (false);
		}

		$UserID = intval ($UserID);

		//удаление из гостей онлайн
		//ВНИМАНИЕ!!! Обновление пользователя производится далее в основных скриптах, ф-цией "OBB_Main_UpdateOnlineInfo"
		$SQL = 'DELETE FROM guest_activity WHERE GuestIPAddress=\'' . Defence_EscapeString ($Config_DBType, $UserIP) . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		//выборка статуса пользователя
		$SQL = 'SELECT AclStatus FROM user_group_permissions WHERE GroupID = \'' . $UserGroup . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$UserStatus = DB_Result ($Config_DBType, $Query, 0, 'AclStatus');
		$UserStatus = Main_Strtolower ($UserStatus);

		//регенерация ид сессии
		session_regenerate_id (TRUE);

		//случайная строка - хеш
		$RandomString = Main_GenerateRandString (10, '1');
		$RandomString = sha1 ($RandomString . $Config_AuthSalt . time ());

		//присвоение сессионных переменных
		unset ($_SESSION['UserData']);
		$_SESSION['UserData'] = array ();
		$_SESSION['UserData']['UserType']  = $UserStatus;
		$_SESSION['UserData']['UserID']    = $UserID;
		$_SESSION['UserData']['UserName']  = $UserLogin;
		$_SESSION['UserData']['UserIP']    = OBB_Main_GetUserIPAddress ();
		$_SESSION['UserData']['GroupID']   = $UserGroup;
		$_SESSION['UserData']['CheckTime'] = time ();
		$_SESSION['UserData'][$Config_SecureKey] = $SecureString;
		$_SESSION['UserData'][$Config_SecureCookieName] = $RandomString;

		//куки
		setcookie($Config_SecureCookieName, $RandomString);

		return (TRUE);
	}
	else {
		return (false);
	}
}

//функция изменения статистики форума
function OBB_Main_UpdateForumStatistics ($IdForum, $ThemeAdding=false) {
	global $Config_DBType, $ForumConnection;

	if ($ThemeAdding) {
		$ThemesSQL = 'ForumNumThemes = (SELECT COUNT(*) FROM themes WHERE ForumID = \'' . $IdForum . '\'), ForumLastThemeID = (SELECT MAX(ThemeID) FROM themes WHERE ForumID = \'' . $IdForum . '\'),';
	}
	else {
		$ThemesSQL = '';
	}

	$SQL = 'UPDATE forums_list
			SET ForumNumPosts = (SELECT COUNT(*) FROM posts WHERE ForumID = \'' . $IdForum . '\'),
				' . $ThemesSQL . '
				ForumLastThemeID = (SELECT ThemeID FROM themes WHERE ForumID = \'' . $IdForum . '\' ORDER BY ThemeUpDate DESC LIMIT 1),
				ForumLastUserID = (SELECT UserID FROM posts WHERE ForumID = \'' . $IdForum . '\' ORDER BY PostID DESC LIMIT 1),
				ForumLastPostID = (SELECT MAX(PostID) FROM posts WHERE ForumID = \'' . $IdForum . '\' AND ThemeID = (SELECT ThemeID FROM themes WHERE ForumID = \'' . $IdForum . '\' ORDER BY ThemeUpDate DESC LIMIT 1)),
				ForumLastUserName = (SELECT UserName FROM posts WHERE ForumID = \'' . $IdForum . '\' ORDER BY PostID DESC LIMIT 1),
				ForumLastUpDate = (SELECT PostDate FROM posts WHERE ForumID = \'' . $IdForum . '\' ORDER BY PostID DESC LIMIT 1)
			WHERE ForumID = \'' . $IdForum . '\'';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
}

//функция изменения статистики темы
function OBB_Main_UpdateThemeStatistics ($ThemeID) {
	global $Config_DBType, $ForumConnection;

	$SQL = 'UPDATE themes
			SET ThemeNumPosts = (SELECT COUNT(*) FROM posts WHERE ThemeID = \'' . $ThemeID . '\'),
				UpdatePostID = (SELECT MAX(PostID) FROM posts WHERE ThemeID = \'' . $ThemeID . '\'),
				UpdateUserID = (SELECT UserID FROM posts WHERE ThemeID = \'' . $ThemeID . '\' ORDER BY PostID DESC LIMIT 1),
				ThemeUpdateUserName = (SELECT UserName FROM posts WHERE ThemeID = \'' . $ThemeID . '\' ORDER BY PostID DESC LIMIT 1),
				ThemeUpDate = (SELECT PostDate FROM posts WHERE ThemeID = \'' . $ThemeID . '\' ORDER BY PostID DESC LIMIT 1)
			WHERE ThemeID = \'' . $ThemeID . '\'';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
}

//функция формирования проверочной авторизационной строки
function OBB_Main_SetAuthorizationString () {
	global $Config_AuthSalt;

	$SecureString = '';

	//user-agent
	$CurUserAgent = getenv("HTTP_USER_AGENT");
	//$CurUserAgent = substr ($CurUserAgent, 0, 50);
	$CurUserAgent = sha1 ($CurUserAgent . $Config_AuthSalt);

	//объединение
	$SecureString = $CurUserAgent;

	return ($SecureString);
}

//ф-ция перенаправления
function OBB_Main_Redirect ($URL = '') {
	global $SelfName, $HostName;
	header ('Location:' . $SelfName . $URL);
	exit;
}

//ф-ция абсолютного перенаправления
function OBB_Main_AbsoluteRedirect ($URL = '') {
	global $SelfName, $HostName;
	header ('Location:' . $SelfName . $URL);
	exit;
}

//проверка на пустоту
function OBB_Main_IsEmpty ($Variable) {
	$Result = is_null ($Variable) || $Variable == '' ? TRUE : false;
	return ($Result);
}

//обработка дат
function Main_ConvertDate ($DateString, $DateArray, $Format = 'full') {
	if ($Format == 'full') {
		$DateDay   = date ('l', $DateString);
		$DateDay   = $DateArray[$DateDay];
		$DateOther = date('d.m.Y, H:i', $DateString);
		$Date = $DateDay . ', ' . $DateOther;
	}
	else {
		$Date = date($Format, $DateString);
	}
	return ($Date);
}

//длина - utf8
function Main_Strlen ($String) {
	return (strlen (utf8_decode ($String)));
}

//нижний регистр - utf8
function Main_Strtolower ($String) {
	return (mb_strtolower ($String, 'UTF-8'));
}

//верхний регистр - utf8
function Main_Strtoupper ($String) {
	return (mb_strtoupper ($String, 'UTF-8'));
}

//substring - utf8
function Main_Substring ($String, $Start, $Length) {
	return (mb_substr ($String, (int)$Start, (int)$Length, 'UTF-8'));
}

//ф-ция вывода ошибок
function OBB_Main_ShowError ($ErrorCode, $ErrorStatus, $ErrorsArray, $ReplaceArray=array()) {
	global $VALIDATION, $ForumLang, $SelfName, $Config_NavigDeleter, $Config_Lang, $HostName, $Config_ForumName, $Action;
	$Errors = $ErrorsArray['ErrorsArray'];

	if ($ErrorStatus == 'CRITICAL') {
		echo ($ErrorCodeArray[$ErrorCode]);
	}
	else if ($ErrorStatus == 'NO_ACCESS') {
		$NoAccess = TRUE;
		include (OBB_SHOW_DIR . '/Login.php');
	}
	else if ($ErrorStatus == 'STANDART') {
		$ErrorCode = Main_Strtolower ($ErrorCode);

		$Title   = $ErrorsArray['ErrorTitle'];
		$Content = $Errors[$ErrorCode];
		if (sizeof ($ReplaceArray) > 0) {
			foreach ($ReplaceArray as $ReplaceKey=>$ReplaceVal) {
				$Content = str_replace ('{' . $ReplaceKey . '}', $ReplaceVal, $Content);
			}
		}

		/* $MainOutput  = '<!DOCTYPE html>
						<html style="height:100%;">
							<head>
								<meta content="text/html; charset=utf-8" http-equiv=Content-Type />
							</head>
							<body style="height:100%;">
								<table style="height:100%;width:100%;">
									<tr>
										<td style="height:100%;width:100%;text-align:center;vertical-align:center;">
											<div>
												<strong>' . $Title . '</strong>
											</div>
											<br />
											<div>
												' . $Content . '
											</div>
										</td>
									</tr>
								</table>
							</body>
						</html>'; */

		ob_start ();
		include (OBB_MVC_DIR . '/Error_html_tpl.php');
		$MainOutput = ob_get_contents ();
		ob_end_clean ();

		echo $MainOutput;
	}
	exit;
}

function OBB_Main_ReplaceSymbols ($String, $ReplaceArray) {
	foreach ($ReplaceArray as $ReplaceKey=>$ReplaceValue) {
		$String = str_replace ('{' . $ReplaceKey . '}', $ReplaceValue, $String);
	}
	return ($String);
}

//функция вывода постов
function OBB_Main_ShowPost ($MessageArray, $PathToCacheDir, $AllowSmiles=true) {
	global $bbcode;
	$MessageID   = $MessageArray['MsgID'];
	$MessageText = $MessageArray['MsgText'];

	if (!OBB_GET_MESSAGE_CACHE) {
		if (OBB_BB_PARSE_ALLOWED) {
			$bbcode->SetEnableSmileys($AllowSmiles);
			$Message = $bbcode->parse ($MessageText);
		}
		else {
			$Message = Defence_HTMLSpecials ($MessageText);
			$Message = nl2br ($Message);
		}
		//$Message = nl2br ($Message);
	}
	else {
		$CacheFilePath = OBB_CACHE_DIR . '/' . $PathToCacheDir . '/post_' . $MessageID;
		if (file_exists ($CacheFilePath)) {
			$Message = OBB_Cache_GetCacheFile ($CacheFilePath);
		}
		else {
			$bbcode->SetEnableSmileys($AllowSmiles);
			$Message = $bbcode->parse ($MessageText);
			//$Message = nl2br ($Message);
			OBB_Cache_PutCacheFile ($CacheFilePath, $Message);
		}
	}
	return ($Message);
}

//создание папки с .htaccess
function OBB_Main_CreateDir ($DirPath) {
	mkdir ($DirPath);
}

//размер файла
function Main_ShowSize ($FileSize) {
	$Result = array ();
	if ($FileSize <= 1024) {
		$Unit = 'b';
		$Size = $FileSize;
	}
	else if ($FileSize > 1024 and $FileSize <= 1048576) {
		$Unit = 'kb';
		$Size = $FileSize/1024;
	}
	else {
		$Unit = 'mb';
		$Size = $FileSize/1048576;
	}
	$Size = round ($Size, 2);
	$Size = (string) $Size;
	$Size = str_replace ('.', ',', $Size);
	$Result['Size'] = $Size;
	$Result['Unit'] = $Unit;
	return ($Result);
}

//кешированная информация
function Main_GetCachedData ($Path) {
	global $VALIDATION, $IdForum;
	ob_start ();
	include ($Path);
	$Result = ob_get_contents ();
	ob_end_clean ();
	return ($Result);
}

//функция уничтожения ненужных переменных
function OBB_Main_KillSessionVars () {
	global $Action;

	if ($Action <> 'message') {
		if (isset ($_SESSION['Message'])) {
			unset ($_SESSION['Message']);
		}
		if (isset ($_SESSION['Redirect'])) {
			unset ($_SESSION['Redirect']);
		}
	}
	return (TRUE);
}

//хеширование строки
function Main_Crypt ($String) {
	$String = sha1 ($String);
	$Salt   = sha1 (substr ($String, 0, 8));
	$Result = sha1 ($String . $Salt);
	return ($Result);
}

//формирование случайной строки заданной длины
function Main_GenerateRandString ($StringLength, $RegisterFlag) {
	global $CharArray;
	$Result = '';
	$NumChars = sizeof ($CharArray) - 1;

	for ($i = 0; $i < $StringLength; $i++) {
		$RandomKey  = rand(0, $NumChars);
		$RandomFlag = rand (0, 1);
		if ($RandomFlag == 1 && !ctype_digit ($CharArray[$RandomKey]) && $RegisterFlag == '1') {
			$RandomChar = strtoupper ($CharArray[$RandomKey]);
		}
		else {
			$RandomChar = Main_Strtolower ($CharArray[$RandomKey]);
		}
		$Result .= $RandomChar;
	}
	return ($Result);
}

//получение массива групп - УБРАТЬ!!!!!!!!!!!!
function Main_GetUserGroups () {
	global $Config_DBType, $ForumConnection;
	$UserGroupArr = array ();
	$GetGroupsSQL = 'SELECT * FROM user_groups';
	$GetGroupsQuery = DB_Query ($Config_DBType, $GetGroupsSQL, $ForumConnection);
	if (!$GetGroupsQuery) {
		OBB_Main_Log ($GetGroupsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	while ($GroupRow = DB_FetchAssoc ($Config_DBType, $GetGroupsQuery)) {
		$GroupID = $GroupRow['GroupID'];
		$GroupNM = $GroupRow['GroupName'];
		$GroupDs = $GroupRow['GroupDescr'];
		$UserGroupArr[$GroupID] = array ();
		$UserGroupArr[$GroupID]['Eng'] = $GroupNM;
		$UserGroupArr[$GroupID]['Des'] = $GroupDs;
	}
	return ($UserGroupArr);
}

//получение массива групп
function OBB_Main_GetUserGroups () {
	global $ForumConnection, $Config_DBType;

	$HardCode = 'users';
	$UserGroups = array ();
	$SQL = 'SELECT * FROM user_groups';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
		$GroupID     = $Row['GroupID'];
		$GroupName   = $Row['GroupName'];
		$GroupDescr  = $Row['GroupDescr'];
		$GroupColor  = $Row['GroupColor'];
		$UserGroups[$GroupID] = array('Name'=>$GroupName, 'Description'=>$GroupDescr, 'Color'=>$GroupColor);
		$UserGroups[$GroupID]['Default'] = Main_Strtolower ($GroupName) == Main_Strtolower ($HardCode) ? TRUE : false;
	}
	return ($UserGroups);
}

//функция получения разрешений группы текущего пользователя
function OBB_Main_GetPermissions () {
	global $ForumConnection, $Config_DBType;

	$SQL = 'SELECT * FROM user_group_permissions';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	$PermissionsArray = array ();
	while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
		$GroupID = intval ($Row['GroupID']);
		$PermissionsArray[$GroupID]['Status']              = $Row['AclStatus'];
		$PermissionsArray[$GroupID]['VisitCommon']         = $Row['AclVisitCommon']         == 'yes';
		$PermissionsArray[$GroupID]['VisitIndex']          = $Row['AclVisitIndex']          == 'yes';
		$PermissionsArray[$GroupID]['VisitThemes']         = $Row['AclVisitThemes']         == 'yes';
		$PermissionsArray[$GroupID]['VisitPosts']          = $Row['AclVisitPosts']          == 'yes';
		$PermissionsArray[$GroupID]['VisitUserlist']       = $Row['AclVisitUserlist']       == 'yes';
		$PermissionsArray[$GroupID]['VisitUserProfile']    = $Row['AclVisitUserProfile']    == 'yes';
		$PermissionsArray[$GroupID]['EditUserProfile']     = $Row['AclEditUserProfile']     == 'yes';
		$PermissionsArray[$GroupID]['ShowMainStatistics']  = $Row['AclShowMainStatistics']  == 'yes';
		$PermissionsArray[$GroupID]['ShowMainOnline']      = $Row['AclShowMainOnline']      == 'yes';
		$PermissionsArray[$GroupID]['ShowMainFastGoto']    = $Row['AclShowMainFastGoto']    == 'yes';
		$PermissionsArray[$GroupID]['ShowThemesFastGoto']  = $Row['AclShowThemesFastGoto']  == 'yes';
		$PermissionsArray[$GroupID]['ShowPostsFastGoto']   = $Row['AclShowPostsFastGoto']   == 'yes';
		$PermissionsArray[$GroupID]['ShowShortAnswer']     = $Row['AclShowShortAnswer']     == 'yes';
		$PermissionsArray[$GroupID]['AddThemes']           = $Row['AclAddThemes']           == 'yes';
		$PermissionsArray[$GroupID]['AddPosts']            = $Row['AclAddPosts']            == 'yes';
		$PermissionsArray[$GroupID]['EditPosts']           = $Row['AclEditPosts']           == 'yes';
		$PermissionsArray[$GroupID]['DeletePosts']         = $Row['AclDeletePosts']         == 'yes';
		$PermissionsArray[$GroupID]['ReportPosts']         = $Row['AclReportPosts']         == 'yes';
		$PermissionsArray[$GroupID]['AttachesAdd']         = $Row['AclAttachesAdd']         == 'yes';
		$PermissionsArray[$GroupID]['AttachesDownload']    = $Row['AclAttachesDownload']    == 'yes';
		$PermissionsArray[$GroupID]['LinksAllowed']        = $Row['AclLinksAllowed']        == 'yes';
		$PermissionsArray[$GroupID]['AvatarsAllowed']      = $Row['AclAvatarsAllowed']      == 'yes';
		$PermissionsArray[$GroupID]['SearchAllowed']       = $Row['AclSearchAllowed']       == 'yes';
		$PermissionsArray[$GroupID]['MailSendAllowed']     = $Row['AclMailSendAllowed']     == 'yes';
		$PermissionsArray[$GroupID]['CaptchaAddTheme']     = $Row['AclCaptchaAddTheme']     == 'yes';
		$PermissionsArray[$GroupID]['CaptchaAddPost']      = $Row['AclCaptchaAddPost']      == 'yes';
		$PermissionsArray[$GroupID]['CaptchaEditPost']     = $Row['AclCaptchaEditPost']     == 'yes';
		$PermissionsArray[$GroupID]['CaptchaReportPost']   = $Row['AclCaptchaReportPost']   == 'yes';
		$PermissionsArray[$GroupID]['CaptchaEditProfile']  = $Row['AclCaptchaEditProfile']  == 'yes';
		$PermissionsArray[$GroupID]['CaptchaSendMail']     = $Row['AclCaptchaSendMail']     == 'yes';
	}
	return ($PermissionsArray);
}

//функция получения разрешений текущего пользователя
function OBB_Main_GetUserPermissions () {
	global $UserGroups_AllPermissions;
	$CurrentUserGroup = intval ($_SESSION['UserData']['GroupID']);
	return ($UserGroups_AllPermissions[$CurrentUserGroup]);
}

//Определение, является ли группа с указанным ИД администраторской
function OBB_Main_IsAdminGroup ($GroupID) {
	global $UserGroups_AllPermissions;
	if (Main_Strtolower ($UserGroups_AllPermissions[$GroupID]['Status']) == 'admin') {
		return (TRUE);
	}
	else {
		return (false);
	}
}

//Получение массива разрешений указанной группы
function OBB_Main_GetPermissionsByGroup ($GroupID) {
	global $UserGroups_AllPermissions;
	return ($UserGroups_AllPermissions[$GroupID]);
}

//Получение конкретного разрешения по группе
function OBB_Main_GetSpecifiedPermission ($GroupID, $PermissionString) {
	global $UserGroups_AllPermissions;
	if (!isset ($UserGroups_AllPermissions[$GroupID][$PermissionString])) {
		return (false);
	}
	else {
		$Permission = $UserGroups_AllPermissions[$GroupID][$PermissionString];
		if ($Permission) {
			return (TRUE);
		}
		else {
			return (false);
		}
	}
	return (false);
}

//обновление последнего посещения темы пользователем
function Main_UpdateThemeView ($ThemeID) {
	global $Config_DBType, $ForumConnection;
	if ($_SESSION['UserData']['UserType'] <> 'guest') {
		$UserID = intval ($_SESSION['UserData']['UserID']);

		$CheckU2ThSQL = 'SELECT 1 FROM users_to_themes WHERE UserID = \'' . $UserID . '\' AND ThemeID = \'' . $ThemeID . '\'';
		$CheckU2ThQuery = DB_Query ($Config_DBType, $CheckU2ThSQL, $ForumConnection);
		if (!$CheckU2ThQuery) {
			OBB_Main_Log ($CheckU2ThSQL. "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		if (DB_NumRows ($Config_DBType, $CheckU2ThQuery) > 0) {
			$ThemeViewSQL = 'UPDATE users_to_themes SET ViewDate = \'' . time () . '\' WHERE UserID = \'' . $UserID . '\' AND ThemeID = \'' . $ThemeID . '\'';
		}
		else {
			$ThemeViewSQL = 'INSERT INTO users_to_themes (UserID, ThemeID, ViewDate) VALUES (\'' . $UserID . '\', \'' . $ThemeID . '\', \'' . time () . '\')';
		}
		$ThemeViewQuery = DB_Query ($Config_DBType, $ThemeViewSQL, $ForumConnection); //echo $ThemeViewSQL;
		if (!$ThemeViewQuery) {
			OBB_Main_Log ($ThemeViewSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
	}
}

//запрет кеширования и прочие стандарные заголовки
function Main_ShowStandartHeader () {
	header('Content-Type: text/html;charset=UTF-8');
	/* Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	Header("Cache-Control: no-cache, must-revalidate");
	Header("Pragma: no-cache");
	Header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT"); */
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Expires: " . date("r"));
	header('Pragma: no-cache');
}

///////////////////////////////////////////функции доступа///////////////////////////////////////////

//функция изменения онлайн-данных о пользователях и гостях
function OBB_Main_UpdateOnlineInfo ($UserData, $UserIP, $UserAction) {
	global $Config_DBType, $ForumConnection;

	$CurrentTime = time ();
	$UserIP = DB_EscapeString ($Config_DBType, $UserIP);

	//Изменение активности текущего посетителя
	//  -если гость
	$UserType = Main_Strtolower ($UserData['UserType']);
	if ($UserType == 'guest') {
		//проверка, есть ли такой IP-адрес в таблице активных гостей
		$IPCheckSQL = 'SELECT 1 FROM guest_activity WHERE GuestIPAddress = \'' . $UserIP . '\'';
		$IPCheckQuery = DB_Query ($Config_DBType, $IPCheckSQL, $ForumConnection);
		if (!$IPCheckQuery) {
			OBB_Main_Log ($IPCheckSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		if (DB_NumRows ($Config_DBType, $IPCheckQuery) > 0) {
			$UpdateGuestActivSQL = 'UPDATE guest_activity SET GuestLastUpdate = \'' . $CurrentTime . '\' WHERE GuestIPAddress = \'' . $UserIP . '\'';
		}
		else {
			$UpdateGuestActivSQL = 'INSERT INTO guest_activity (GuestIPAddress, GuestLastUpdate) VALUES (\'' . $UserIP . '\', \'' . $CurrentTime . '\')';
		}
		$UpdateGuestActivQuery = DB_Query ($Config_DBType, $UpdateGuestActivSQL, $ForumConnection);
		if (!$UpdateGuestActivQuery) {
			OBB_Main_Log ($UpdateGuestActivSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
	}
	//  -если зарегистрированный пользователь
	else if ($UserType == 'member' || $UserType == 'admin') {
		$UserUpdateActivSQL = 'UPDATE user_activity
							SET
							UserLastLogin = \'' . $CurrentTime . '\',
							UserLastAction = \'' . $UserAction . '\',
							UserIPAddress = \'' . $UserIP . '\',
							UserIsOnline = \'yes\'
							WHERE UserID = \'' . intval ($UserData['UserID']) . '\'';
		$UserUpdateActivQuery = DB_Query ($Config_DBType, $UserUpdateActivSQL, $ForumConnection);
		if (!$UserUpdateActivQuery) {
			OBB_Main_Log ($UserUpdateActivSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
	}
	return (TRUE);
}

//функция периодической проверки пользователя
function OBB_Main_CheckUser () {
	global $ForumConnection, $Config_DBType;

	if (isset ($_SESSION['UserData']['UserID']) && isset ($_SESSION['UserData']['GroupID'])) {
		$UserID  = intval ($_SESSION['UserData']['UserID']);
		$GroupID = intval ($_SESSION['UserData']['GroupID']);
	}
	else {
		return (false);
	}

	$Now = time ();
	$TimeForChecking    = isset ($_SESSION['UserData']['CheckTime']) ? intval ($_SESSION['UserData']['CheckTime']) : intval (0);
	$CheckingTimePeriod = intval (OBB_USER_CHECK_TIME * 60);
	if (($Now - $TimeForChecking) >= $CheckingTimePeriod) {
		$SQL = 'SELECT 1 FROM users WHERE UserID = \'' . intval ($UserID) . '\' AND GroupID = \'' . $GroupID . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		if (DB_NumRows ($Config_DBType, $Query) < 1) {
			return (false);
		}
		$_SESSION['UserData']['CheckTime'] = time ();//ПРИМЕЧАНИЕ: ПОД ВОПРОСОМ!!!
	}
	return (TRUE);
}

//проверка доступа по группе
function Access_CheckUserGroup ($ForumGroupString) {
	if ($_SESSION['UserData']['UserType'] == 'member') {
		$GroupID = $_SESSION['UserData']['GroupID'];
	}
	else {
		$GroupID = 0;
	}

	if (Main_Strlen ($ForumGroupString) == 0) {
		return (TRUE);
	}
	else {
		$ForumGroupArr = unserialize ($ForumGroupString);
		if (!in_array ($GroupID, $ForumGroupArr)) {
			return (FALSE);
		}
		else {
			return (TRUE);
		}
	}
}
///////////////////////////////////////////функции сессии///////////////////////////////////////////

//старт сессии
function OBB_Main_SessionStart () {
	global $Config_SecureKey, $Config_SecureCookieName, $Config_AuthSalt;

	$LifeTime = 0;
	$Path = '/';
	$Domain = '';
	$Secure = FALSE;
	$HttpOnly = TRUE;
	session_set_cookie_params($LifeTime, $Path, $Domain, $Secure, $HttpOnly);

	session_start ();

	if (!isset ($_SESSION['UserData'])) {
		$_SESSION['UserData'] = array ();
		$_SESSION['UserData']['UserType'] = 'guest';
		$_SESSION['UserData']['GroupID'] = '3'; //ПЕРЕДЕЛАТЬ
	}
	else{
		$Type = $_SESSION['UserData']['UserType'];
		$SessionValidator = 0;

		if ($Type <> 'guest') {
			if (
				!isset ($_SESSION['UserData']['UserID'])          ||
				!isset ($_SESSION['UserData']['UserName'])        ||
				!isset ($_SESSION['UserData']['GroupID'])         ||
				!isset ($_SESSION['UserData']['CheckTime'])       ||
				!isset ($_SESSION['UserData']['UserIP'])          ||
				!isset ($_SESSION['UserData'][$Config_SecureKey]) ||
				!isset ($_SESSION['UserData'][$Config_SecureCookieName])
			) {
				$SessionValidator++;
			}

			if (!isset ($_COOKIE[$Config_SecureCookieName])) {
				$SessionValidator++;
			}

			//секретная строка
			$SecureString = OBB_Main_SetAuthorizationString ();

			if ($_SESSION['UserData'][$Config_SecureKey] <> $SecureString) {
				$SessionValidator++;
			}

			//проверка IP-адреса пользователя
			$RealUserIP = OBB_Main_GetUserIPAddress ();
			$SessionUserIP = $_SESSION['UserData']['UserIP'];
			if (!OBB_Main_CheckIPAddress ($RealUserIP, $SessionUserIP)) {
				$SessionValidator++;
			}

			if (isset ($_COOKIE[$Config_SecureCookieName]) && ($_SESSION['UserData'][$Config_SecureCookieName] <> $_COOKIE[$Config_SecureCookieName])) {
				$SessionValidator++;
			}

			if (!OBB_Main_CheckUser ()) {
				$SessionValidator++;
			}

			if ($SessionValidator > 0) {
				session_regenerate_id ();
				$_SESSION['UserData'] = array ();
				$_SESSION['UserData']['UserType'] = 'guest';
				$_SESSION['UserData']['GroupID'] = '3'; //ПЕРЕДЕЛАТЬ
			}
		}
	}
	return (session_id ());
}

///////////////////////////////////////////функции защиты///////////////////////////////////////////

//функция контроля откуда пришли
function Defence_ComingControl ($AllowedURL, $Strict) {
	if (!isset ($_SERVER['HTTP_REFERER'])) {
		return (FALSE);
	}

	if ($Strict == '1') {
		if ($AllowedURL <> $_SERVER['HTTP_REFERER']) {
			return (FALSE);
		}
	}

	$AllowedLength = Main_Strlen ($AllowedURL);
	$RealURLPart = substr ($_SERVER['HTTP_REFERER'], 0, $AllowedLength);
	if ($RealURLPart <> $AllowedURL) {
		return (FALSE);
	}

	return (TRUE);
}

//функция проверки капчи
function Defence_CheckCaptcha () {
	$IsCaptchaTrue = TRUE;
	$CaptchaImg = isset ($_POST['CaptchaImg']) ? trim($_POST['CaptchaImg']) : '';
	if (!isset ($_SESSION['CapchaValue']) || Main_Strlen ($_SESSION['CapchaValue']) < 1) {
		return ('CaptchaError');
	}
	else {
		$SessCaptcha = $_SESSION['CapchaValue'];
		unset ($_SESSION['CapchaValue']);
		if (Main_Strlen ($CaptchaImg) == 0) {
			return ('EmptyCaptcha');
		}
		else {
			$SessCaptcha = Main_Strtolower (trim ($SessCaptcha));
			$UserCaptcha = Main_Strtolower ($CaptchaImg);
			if ($SessCaptcha <> $UserCaptcha) {
				return ('BadCaptcha');
			}
		}
	}
	return (TRUE);
}

//функция полной проверки логина
function OBB_Defence_CheckLogin ($LoginString) {
	global $ForumConnection, $Config_DBType;

	if ($LoginString == '') {
		return (array (FALSE, 'EmptyLogin'));
	}
	else {
		$LoginLength = Main_Strlen ($LoginString);
		if ($LoginLength < OBB_MIN_LOGIN_LENGTH || $LoginLength > OBB_MAX_LOGIN_LENGTH) {
			return (array (FALSE, 'BadLoginLength'));
		}
		else {
			$RegPattern = '/^[_а-яА-ЯёЁa-z0-9][-_а-яА-ЯёЁa-z0-9]*$/iu';
			if (!preg_match ($RegPattern, $LoginString)) {
				return (array (FALSE, 'BadLoginSymbols'));
			}

			$SQL = 'SELECT 1 FROM users WHERE UserLogin = \'' . Defence_EscapeString ($Config_DBType, $LoginString) . '\'';
			$SameLoginQuery = DB_Query($Config_DBType, $SQL, $ForumConnection);
			if (!$SameLoginQuery) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			if (DB_NumRows ($Config_DBType, $SameLoginQuery) > 0) {
				return (array (FALSE, 'LoginExists'));
			}
		}
	}
	return (array (TRUE, NULL));
}

//функция проверки почты (полная)
function OBB_Defence_CheckMail ($MailString) {
	global $ForumConnection, $Config_DBType;

	if ($MailString == '') {
		return (array(FALSE, 'EmptyMail'));
	}
	else {
		$MailLength  = Main_Strlen ($MailString);
		if ($MailLength < OBB_MIN_MAIL_LENGTH || $MailLength > OBB_MAX_MAIL_LENGTH) {
			return (array(FALSE, 'BadMailLength'));
		}
		else {
			$BadSymbols = 0;
			if (function_exists ('filter_var')) {
				if (!filter_var($MailString, FILTER_VALIDATE_EMAIL)) {
					$BadSymbols++;
				}
			}
			else {
				$RegPattern = '"/^[a-z0-9\.\-_]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is"';
				if (preg_match ($RegPattern, $MailString)) {
					$BadSymbols++;
				}
			}

			if ($BadSymbols > 0) {
				return (array(FALSE, 'BadMailSymbols'));
			}
			else {
				$SQL = 'SELECT 1 FROM users WHERE UserMail = \'' . Defence_EscapeString ($Config_DBType, $MailString) . '\'';
				$SameMailQuery = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$SameMailQuery) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				if (DB_NumRows ($Config_DBType, $SameMailQuery) > 0) {
					return (array(FALSE, 'MailExists'));
				}
			}
		}
	}
	return (array(TRUE, NULL));
}

//ф-ция экранирования SQL-символов
function Defence_EscapeString ($Config_DBType, $String) {
	$MagicQuotes = get_magic_quotes_gpc();
	if ($MagicQuotes) {
		$Result = $String;
	}
	else {
		$Result = DB_EscapeString ($Config_DBType, $String);
	}
	return ($Result);
}

//
function Defence_HTMLSpecials ($String) {
	$Return = htmlspecialchars ($String, ENT_QUOTES, 'UTF-8');
	return ($Return);
}

//функция проверки к-ва строк в текстарии
function Defence_GetTextNumRows ($Text) {
	$RowSeparator = "\n";
	$RowArray = explode ($RowSeparator, $Text);
	$Return = sizeof ($RowArray);
	return ($Return);
}

///////////////////////////////////////////функции вывода///////////////////////////////////////////

//функция быстрого перехода по форумам
function Echo_GotoForum ($ForumConnection, $SelfName, $ForumLang) {
	$ForumsArray = OBB_Cache_GetCacheArray (OBB_CACHE_DIR . '/DBCache/goto_common');

	$ForumsList = '<option value="0">' . $ForumLang['JumpSelectForum'] . '</option>';
	foreach ($ForumsArray as $key=>$val) {
		$ForumsList .= '<optgroup label="- - ' . Defence_HTMLSpecials ($key) . '">';
		foreach ($val as $valk => $valv) {
			$ForumsList .= '<option value="' . intval ($valk) . '">- - - ' . Defence_HTMLSpecials ($valv) . '</option>';
		}
		$ForumsList .= '</optgroup>';
	}

	$Return = '<form action="' . $SelfName . '" method="get">
					<div>
						<input type="hidden" name="action" value="forumview" />
						<select name="id_forum">' . $ForumsList . '</select>
						<input class="FastGoButton" type="submit" value="' . $ForumLang['Submit'] . '" />
					</div>
				</form>';
	return ($Return);
}

//функция определения иконки темы
function Echo_GetThemeIcon ($ThemeNumPosts, $ThemeViewTime, $ThemeUpdateTime, $ThemeBlock, $IsSearch=false) {
	global $ForumLang;

	$Prefix = !$IsSearch ? 'Themes' : 'SearchTheme';
	$ThemeIsPop = $ThemeNumPosts >= OBB_NUM_POSTS_FOR_POP_THEME;
	$ThemeNewPosts = $_SESSION['UserData']['UserType'] == 'guest' || (OBB_Main_IsEmpty ($ThemeViewTime) || ($ThemeUpdateTime > $ThemeViewTime));

	$ThemeIcon = 'usual';
	$ThemeTitle = $ForumLang[$Prefix.'UsualTitle'];
	if ($ThemeBlock == 'yes') {
		$ThemeIcon  = $ThemeIsPop ? 'theme_block_pop.png' : 'theme_block_usual.png';
		$ThemeTitle = $ThemeIsPop ? $ForumLang[$Prefix.'BlockPopTitle'] : $ForumLang[$Prefix.'BlockTitle'];
	}
	else {
		if (!$ThemeIsPop) {
			$ThemeIcon  = $ThemeNewPosts ? 'theme_usual_new.png' : 'theme_usual.png';
			$ThemeTitle = $ThemeNewPosts ? $ForumLang[$Prefix.'UsualNewPostsTitle'] : $ForumLang[$Prefix.'UsualTitle'];
		}
		else {
			$ThemeIcon =  $ThemeNewPosts ? 'theme_usual_pop_new.png' : 'theme_usual_pop.png';
			$ThemeTitle = $ThemeNewPosts ? $ForumLang[$Prefix.'UsualPopNewPostsTitle'] : $ForumLang[$Prefix.'UsualPopTitle'];
		}
	}
	$Return = '<img alt="" title="' . $ThemeTitle . '" src="' . OBB_IMAGE_DIR . '/' . $ThemeIcon . '" />';
	return ($Return);
}

//функция быстрого перехода по темам
function Echo_GotoTheme ($ForumConnection, $SelfName, $ForumLang, $ForumID, $ThemeID=null) {
	$ThemesArray = file_exists(OBB_CACHE_DIR . '/DBCache/goto_forum_' . $ForumID) ? OBB_Cache_GetCacheArray (OBB_CACHE_DIR . '/DBCache/goto_forum_' . $ForumID) : array();

	$ThemesList = '<option value="0">' . $ForumLang['JumpSelectTheme'] . '</option>';
	foreach ($ThemesArray as $key=>$val) {
		$key = intval ($key);
		$Selected = !OBB_Main_IsEmpty ($ThemeID) && $ThemeID == $key ? ' selected' : '';
		$ThemesList .= '<option' . $Selected . ' value="' . $key . '">- - - ' . Defence_HTMLSpecials ($val) . '</option>';
	}

	$Return = '<form action="' . $SelfName . '" method="GET">
			   <input type="hidden" name="action" value="themeview">
			   <input type="hidden" name="id_forum" value="' . $ForumID . '">
			   <select name="id_theme" onChange="submit();">' . $ThemesList . '</select>
			   <input type="submit" value="' . $ForumLang['Submit'] . '">
			   </form>';

	return ($Return);
}

//Построение главной навигации
function Echo_Navigation ($NavigArray) {
	$NavArray = array ();
	$Logical = false;
	foreach ($NavigArray as $Key=>$Value) {
		if (!$Logical) {
			$ClassLi = ' class="IndexLI"';
			$ClassA  = ' class="Index"';
		}
		else {
			$ClassLi = '';
			$ClassA  = '';
		}
		if ($Value[0] <> '') {
			$HrefStart = '<a' . $ClassA . ' title="' . $Value[1] . '" href=\'' . Defence_HTMLSpecials ($Value[0]) . '\'>';
			$HrefEnd   = '</a>';
		}
		else {
			$HrefStart = '';
			$HrefEnd   = '';
		}

		$NavArray[] = '<li' . $ClassLi . '>' . $HrefStart . $Value[1] . $HrefEnd . '</li>';
		$Logical = TRUE;
	}
	$Return = implode ('<li class="DividerLI"><img alt=">" src="' . OBB_IMAGE_DIR . '/home.gif" height="16" width="16" /></li>', $NavArray);
	return ($Return);
}

//вывод капчи
function Echo_CaptchaBlock ($CaptchaTitle, $EnterPaptchaWord, $CaptchaInputID = 'CaptchaField') {
	global $SelfName, $Config_Captcha;
	$NumLetters  = OBB_CAPTCHA_NUM_LETTERS;
	$InputSize   = $NumLetters < 1 ? 1 : $NumLetters;
	$CaptchaRand = rand (1000, 9999);
	$Result =  '<fieldset style="width:80%;">
					<legend>' . $CaptchaTitle . '</legend>
					<div>
						<span>
							<img src="' . $SelfName . '?action=captcha&random=' . $CaptchaRand . '" border="1" />
						</span>
						<br />
						<span>
							<input id="' . $CaptchaInputID . '" type="text" name="CaptchaImg" size="10" maxlength="' . $InputSize . '" value="" />
						</span>
						<br />
						<span>
							<small>' . $EnterPaptchaWord . '</small>
						</span>
					</div>
				</fieldset>';
	return ($Result);
}

//вывод капчи2
function Echo_CaptchaBlock2 ($CaptchaTitle, $EnterPaptchaWord, $CaptchaInputID = 'CaptchaField') {
	global $SelfName, $Config_Captcha, $ForumLang;
	$NumLetters  = OBB_CAPTCHA_NUM_LETTERS;
	$InputSize   = $NumLetters < 1 ? 1 : $NumLetters;
	$CaptchaRand = rand (1000, 9999);
	$Result = ' <tr>
					<td style="" class="FormTitleTD">
						<div class="InputTitle">
							' . $CaptchaTitle . '
						</div>
					</td>
					<td class="FormInputTD">
						<img class="CaptchaImage" alt="" src="' . Defence_HTMLSpecials ($SelfName . '?action=captcha&random=' . $CaptchaRand) . '" />
					</td>
				</tr>
				<tr>
					<td style="" class="FormTitleTD">
						<div class="InputTitle">
							' . $EnterPaptchaWord . '<span class="Important">*</span>
						</div>
						<div class="InputDescr">
							' . $ForumLang['CaptchaNoRegister'] . '
						</div>
					</td>
					<td class="FormInputTD">
						<input class="InpEl InpText" id="' . $CaptchaInputID . '" type="text" name="CaptchaImg" size="10" maxlength="' . $InputSize . '" value="" />
					</td>
				</tr>';
	return ($Result);
}

//хедер
function Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, $DelimiterDivName='DelimiterDiv') {
	global $Action, $SelfName, $ForumLang, $VALIDATION, $Config_JSTabs, $UserGroups_Array, $Config_ShortDescription, $UserGroups_Permissions;
	$Return = '';

	//SOME FEATURES
	//  --1)Search lightbox
		if ($Action == 'search') {
			global $SearchStatisticsArray;
			$SearchWordDiv = '<div>
								<strong>' . $ForumLang['Lightbox']['SearchPhrase'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchStatisticsArray['Phrase']) . '
							  </div>';
			$NumResultsDiv = '<div>
								<strong>' . $ForumLang['Lightbox']['SearchNum'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchStatisticsArray['Results']) . '
							  </div>';
			$SearchMethod = $ForumLang['Lightbox']['SearchMethod'.$SearchStatisticsArray['Method']];
			$SearchMethodDiv= '<div>
								<strong>' . $ForumLang['Lightbox']['SearchMethod'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchMethod) . '
							  </div>';
			if (Main_Strlen ($SearchStatisticsArray['User']) > 0) {
				$UserLoginDiv = '<div>
									<strong>' . $ForumLang['Lightbox']['SearchUser'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchStatisticsArray['User']) . '
								</div>';
			}
			else {
				$UserLoginDiv = '';
			}
			$SearchSortBy = $ForumLang['Lightbox']['SearchSortField'.$SearchStatisticsArray['SortBy']];
			$SearchSortByDiv = '<div>
									<strong>' . $ForumLang['Lightbox']['SearchSortField'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchSortBy) . '
								</div>';
			$SearchSortHow = $ForumLang['Lightbox']['SearchSortHow'.$SearchStatisticsArray['SortHow']];
			$SearchSortHowDiv = '<div>
									<strong>' . $ForumLang['Lightbox']['SearchSortHow'] . ':</strong>&nbsp;' . Defence_HTMLSpecials ($SearchSortHow) . '
								</div>';
			$Search_Lightbox = '<div id="LightboxDiv">
									<div style="position:absolute; top:20px; left:50%; width:600px; z-index:100; margin-left:-300px;" id="SearchStatistics">
										<table style="width:100%;" class="LightboxTable" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td colspan="2" class="LightboxHead">
													<div style="float:left;">' . $ForumLang['Lightbox']['SearchStatisticsTitle'] . '</div>
													<div style="float:right;">
														<a title="' . $ForumLang['Lightbox']['SearchStatisticsClose'] . '" href="javascript:void(0);" onclick="javascript:hideSearchStatistics();">
															<img style="vertical-align:middle;" title="' . $ForumLang['Lightbox']['SearchStatisticsClose'] . '" alt="" src="' . OBB_IMAGE_DIR . '/lightbox_close.png" />
														</a>
													</div>
												</td>
											</tr>
											<tr>
												<td class="LightboxTD1" style="width:40px;">
													<img title="" alt="" class="ForumStats" src="' . OBB_IMAGE_DIR . '/statistics.png" />
												</td>
												<td class="LightboxTD2" style="text-align:left; vertical-align:top;">
													' . $SearchWordDiv   . '
													' . $NumResultsDiv   . '
													' . $UserLoginDiv    . '
													' . $SearchMethodDiv . '
													' . $SearchSortByDiv . '
													' . $SearchSortHowDiv. '
												</td>
											</tr>
											<tr>
												<td style="text-align:center;" colspan="2" class="LightboxBottom">
													<input style="width:50px;" class="LightboxInput" onclick="javascript:hideSearchStatistics();" type="button" value="OK" />
													&nbsp;
													<input style="width:70px;" class="LightboxInput" onclick="javascript:hideSearchStatistics();" type="button" value="Cancel" />
												</td>
											</tr>
										</table>
									</div>
								</div>';
		}
		else {
			$Search_Lightbox = '';
		}
	//  --1)Search lightbox - end
	//SOME FEATURES - END

	$Return .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	$Return .= "\r\n";
	$Return .= '<html xmlns="http://www.w3.org/1999/xhtml">';

	//head - начало
	$Return .= "\r\n";
	$Return .= '<head>';
	$Return .= "\r\n";

	//  --Заголовок
	$Return .= "\t".'<title>' . $Title . '</title>';
	$Return .= "\r\n";

	//Важные заголовки
	$Return .=
<<<META
	<meta http-equiv="Expires" content="Mon, 26 Jul 2004 05:00:00 GMT" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-store" />
	<meta http-equiv="Cache-Control" content="0" />
	<meta name="document-State" content="dynamic" />
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
META;

	$Return .= "\r\n\t";

	//  --HEAD INCLUDES
	//  --1)CSS
	$Return .= '<link rel="stylesheet" type="text/css" href="' . OBB_CSS_DIR . '/style.css" />';
	$Return .= "\r\n\t";
	$Return .= '<!--[if IE 6]>
		<link rel="stylesheet" type="text/css" href="' . OBB_CSS_DIR . '/ie6_style.css" />
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" href="' . OBB_CSS_DIR . '/ie7_style.css" />
	<![endif]-->
	<!--[if IE 8]>
		<link rel="stylesheet" type="text/css" href="' . OBB_CSS_DIR . '/ie8_style.css" />
	<![endif]-->';
	//  --1)CSS

	$Return .= "\r\n\t";

	//  --2)JAVASCRIPT
	$JavaScriptString = Echo_PrintJavaScript ($JavaScriptArray);
	$Return .= $JavaScriptString;
	//  --2)JAVASCRIPT - КОНЕЦ
	//  --HEAD INCLUDES - КOНЕЦ

	$Return .= '</head>';
	$Return .= "\r\n";
	//head - конец

	//приветствие - начало
	$UserGroupID = $_SESSION['UserData']['GroupID'];
	$UserGroupColor = $UserGroups_Array[$UserGroupID]['Color'];
	$StatusesArray = $ForumLang['ForumStatuses'];
	$ShalomLeft  = '';
	$ShalomRight = '';
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		if (OBB_ALLOW_REGISTRATION) {
			$RegisterString = ' | <a title="' . $ForumLang['Shalom']['RegisterTitle'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=registration') . '">' . $ForumLang['Shalom']['Register'] . '</a>';
		}
		else {
			$RegisterString = '';
		}
		$ShalomLeft .= '<a title="' . $ForumLang['Shalom']['RSSTitle'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=rss') . '">' . $ForumLang['Shalom']['RSS'] . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a title="' . $ForumLang['Shalom']['RulesTitle'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=tooling&label=rules') . '">' . $ForumLang['Shalom']['Rules'] . '</a>';
		$ShalomRight .= $ForumLang['Shalom']['GuestWelcome'] . ' <span class="WelcomeSpan" style="color:#' . $UserGroupColor . '">' . $ForumLang['Shalom']['Guest'] . '</span> ( <a href="' . Defence_HTMLSpecials ($SelfName . '?action=login') . '" title="' . $ForumLang['Shalom']['LoginTitle'] . '">' . $ForumLang['Shalom']['Login'] . '</a>' . $RegisterString . ' )' ;
	}
	else {
		$ShalomLeft .= '<a title="' . $ForumLang['Shalom']['RSSTitle'] . '" href="' . $SelfName . '?action=rss">' . $ForumLang['Shalom']['RSS'] . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a title="' . $ForumLang['Shalom']['RulesTitle'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=tooling&label=rules') . '">' . $ForumLang['Shalom']['Rules'] . '</a>';
		if ($_SESSION['UserData']['UserType'] == 'admin') {
			$ShalomLeft .= '&nbsp;&nbsp;|&nbsp;&nbsp;<a title="' . $ForumLang['Shalom']['AdminTitle'] . '" class="Admin" href="' . Defence_HTMLSpecials ($SelfName . '?action=admin') . '">' . $ForumLang['Shalom']['Admin'] . '</a>';
		}
		if ($_SESSION['UserData']['UserType'] == 'admin' || ($UserGroups_Permissions['VisitUserProfile'] && OBB_WATCH_PROFILE)) {
			$ProfileString = ' | <a title="' . $ForumLang['Shalom']['ProfileTitle'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $_SESSION['UserData']['UserID']) . '">' . $ForumLang['Shalom']['Profile'] . '</a>';
		}
		else {
			$ProfileString = '';
		}
		$ShalomRight .= $ForumLang['Shalom']['UserWelcome'] . ' <span class="WelcomeSpan" style="color:#' . $UserGroupColor . '">' . Defence_HTMLSpecials ($_SESSION['UserData']['UserName']) . '</span> ( <a href="' . Defence_HTMLSpecials ($SelfName . '?action=logout') . '" title="' . $ForumLang['Shalom']['LogoutTitle'] . '">' . $ForumLang['Shalom']['Logout'] . '</a>' . $ProfileString . ' )' ;
	}
	//приветствие - конец

	$Return .= '<body>';
	$Return .= "\r\n";
	$Return .= '
	' . $Search_Lightbox . '
	<!-- Container -->
		<div id="Container">
				<!-- Container table -->
				<table id="ContainerTable" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<!-- Container table TD -->
						<td id="ContainerTD">
							<!-- Content table -->
							<table id="MainTable" cellpadding="0" cellspacing="0" border="0">
								<!-- Header TD -->
								<tr>
									<td id="Header">
										<table id="HeaderTable" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td id="HeaderLogoTD">
													<div id="HeaderLogoCommonDiv">
														<div id="HeaderLogoDiv">
															<a href="' . Defence_HTMLSpecials ($SelfName) . '">
																<img title="Own Bulletin Board" alt="OwnBB" src="' . OBB_IMAGE_DIR . '/logo1.gif" width="120" height="41" />
															</a>
														</div>
														<div id="HeaderLogoDescription1">
															Own Bulletin Board
														</div>
														<div id="HeaderLogoDescription2">
															' . $Config_ShortDescription . '
														</div>
														<div id="HeaderLogoSearch">
															<form id="LogoSubmit" action="' . Defence_HTMLSpecials ($SelfName . '?action=search&search_build=1') . '" method="post">
																<div>
																	<input type="hidden"  name="SearchMethod" value="1" />
																	<input type="hidden" name="SearchMethod" value="1" />
																	<input type="hidden" name="SearchUser" value="" />
																	<input type="hidden" name="SearchSortBy" value="1" />
																	<input type="hidden" name="SearchSortHow" value="2" />
																	<input id="ProfileSearchMethodIn" type="hidden" name="SearchMethodIn" value="1" />
																	<input type="hidden" name="search" value="1" />
																	<input onblur="javascript:headerSearch2(this, \'' . $ForumLang['CommonSearch'] . '\');" onclick="javascript:headerSearch1(this, \'' . $ForumLang['CommonSearch'] . '\');" class="HeaderSearchInput" id="SearchWord1" type="text" name="SearchWord" size="20" maxlength="' . OBB_MAX_SEARCH_WORD . '" value="' . $ForumLang['CommonSearch'] . '..." />
																	<input class="HeaderSearchSubmit" type="submit" value="' . $ForumLang['CommonSearchButton'] . '" />
																</div>
															</form>
														</div>
														<div id="HeaderAdvancdSearch">
															<a href="' . $SelfName . '?action=search">Расширенный поиск</a>
														</div>
														' . Echo_PrintMainNavigation () . '
													</div>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<!-- Header TD END -->
								<!-- Shalom TD -->
								<tr>
									<td id="Shalom">
										<div id="ShalomDiv">
											<div id="Welcome">
												' . $ShalomRight . '
											</div>
											<div id="RSS">
												' . $ShalomLeft . '
											</div>
										</div>
									</td>
								</tr>
								<!-- Shalom TD END -->
								<!-- Navigation TD -->
								<tr>
									<td id="Navigation">
										<ul id="NavigationUL">
											' . Echo_Navigation ($NavigArray) . '
										</ul>
									</td>
								</tr>
								<!-- Navigation TD END -->
								<tr>
									<td id="MainContTD">
									<div id="' . $DelimiterDivName . '"><!-- --></div>';

	return ($Return);
}

//функция формирования JavaScript-скрипта на основе массива специального формата
function Echo_PrintJavaScript ($JavaScriptArray) {
	global $VALIDATION, $ForumLang, $Config_JSTabs;
	if (sizeof ($JavaScriptArray) > 0) {
		$JavaScriptNewArray = array();
		foreach ($JavaScriptArray as $key=>$value) {
			$JSFileName = $value[0];
			$JSFilePath = $value[1];
			if ($JSFileName == 'parameters') {
				$ParametersArray = $value[1];
				$OneJavaScript = '';
				$OneJavaScript .= Echo_InitializeJSData ($ParametersArray);
				$JavaScriptNewArray[] = $OneJavaScript;
			}
			else {
				if (Main_Strlen ($JSFilePath) == 0) {
					$JSFilePath = '';
				}
				else {
					$JSFilePath = '/' . $JSFilePath;
				}
				$OneJSInclude = '<script type="text/javascript" src="' . OBB_JS_DIR . $JSFilePath . '/' . $JSFileName . '"></script>';
				$JavaScriptNewArray[] = $OneJSInclude;
			}
		}
		$JavaScriptString = "\r\n\t" . implode ("\r\n\t", $JavaScriptNewArray) . "\r\n";
	}
	else {
		$JavaScriptString = '';
	}
	return ($JavaScriptString);
}

//функция инициализации глобальных javascript-данных
function Echo_InitializeJSData ($Parameters) {
	global $ForumLang;

	$Return       = '';
	$JSTextArray  = array ();
	$JSParamArray = array ();
	$GlobalAction = $Parameters['Action'];
	$SubAction    = $Parameters['SubAction'];

	//Параметры
	$ParamString = '';
	switch ($GlobalAction) {
		case'add': {
			//параметры
			$IsCaptcha = $Parameters['IsCaptcha'] ? 'true' : 'false';
			$JSParamArray = array (
								'IsCptch'    => $IsCaptcha,
								'LoadSmiles' => 'true');
			if ($SubAction == 'add') {
				$IsGuest   = $Parameters['IsGuest']   ? 'true' : 'false';
				$IsTheme   = $Parameters['IsTheme']   ? 'true' : 'false';
				$JSParamArray['IsGst'] = $IsGuest;
				$JSParamArray['IsThm'] = $IsTheme;
			}

			//ошибки
			$JSTextArray = array (
							'EmptyCaptcha'      	 => $ForumLang['Javascript']['EmptyCaptcha'],
							'AddEmptyLogin'      	 => $ForumLang['Javascript']['AddEmptyLogin'],
							'AddEmptyMail'       	 => $ForumLang['Javascript']['AddEmptyMail'],
							'AddThemeName' 	    	 => $ForumLang['Javascript']['AddEmptyThemeName'],
							'AddPostField'      	 => $ForumLang['Javascript']['AddEmptyPostField']
			);
			break;
		}

		case'forgot': {
			//ошибки
			$JSTextArray = array (
							'ForgotEmptyLogin' 		 => $ForumLang['Javascript']['ForgotEmptyLogin'],
							'ForgotEmptyMail'  		 => $ForumLang['Javascript']['ForgotEmptyMail']
			);
			break;
		}

		case'login': {
			//ошибки
			$JSTextArray = array (
							'LoginEmptyLogin' 		 => $ForumLang['Javascript']['LoginEmptyLogin'],
							'LoginEmptyPass'  		 => $ForumLang['Javascript']['LoginEmptyPass']
				);
			break;
		}

		case'tooling': {
				//параметры
				$IsCaptcha = $Parameters['IsCaptcha'] ? 'true' : 'false';
				$JSParamArray = array ('IsCptch' => $IsCaptcha);

				//ошибки
				$JSTextArray = array (
							'EmptyCaptcha'      	 => $ForumLang['Javascript']['EmptyCaptcha'],
							'ToolEmptyMailLetter'    => $ForumLang['Javascript']['ToolEmptyMailLetter'],
							'ToolEmptyReportReason'  => $ForumLang['Javascript']['ToolsEnterReportReason']
				);
			break;
		}

		case'themeview': {
			//параметры
			$IsCaptcha = $Parameters['IsCaptcha'] ? 'true' : 'false';
			$IsGuest   = $Parameters['IsGuest']   ? 'true' : 'false';
			$JSParamArray = array (
								'IsCptch'    => $IsCaptcha,
								'IsGst'      => $IsGuest,
								'LoadSmiles' => 'true'
							);

			//ошибки
			$JSTextArray = array (
							'EmptyCaptcha'      	 => $ForumLang['Javascript']['EmptyCaptcha'],
							'PostsEmptyLogin'      	 => $ForumLang['Javascript']['PostsEmptyLogin'],
							'PostsEmptyMail'      	 => $ForumLang['Javascript']['PostsEmptyMail'],
							'PostsEmptyPostField' 	 => $ForumLang['Javascript']['PostsEmptyPostField']
				);
			break;
		}

		case'registration': {
			//параметры
			$IsCaptcha  = $Parameters['IsCaptcha']  ? 'true' : 'false';
			$IsPassword = $Parameters['IsPassword'] ? 'true' : 'false';
			$JSParamArray = array (
								'IsCptch'  => $IsCaptcha,
								'IsPsswrd' => $IsPassword
							);

			//ошибки
			$JSTextArray = array (
							'EmptyCaptcha'      	 => $ForumLang['Javascript']['EmptyCaptcha'],
							'RegEmptyLogin'      	 => $ForumLang['Javascript']['RegEmptyLogin'],
							'RegEmptyMail'       	 => $ForumLang['Javascript']['RegEmptyMail'],
							'RegEmptyRepeatMail' 	 => $ForumLang['Javascript']['RegEmptyRepeatMail'],
							'RegPasswordIsEmpty'   	 => $ForumLang['Javascript']['RegPasswordIsEmpty'],
							'RegRepeatPassIsEmpty' 	 => $ForumLang['Javascript']['RegRepeatPassIsEmpty'],
							'RegEmptyDate'       	 => $ForumLang['Javascript']['RegEmptyDate']
				);
			break;
		}

		case'profile': {
			//параметры
			$IsCaptcha  = $Parameters['IsCaptcha']  ? 'true' : 'false';
			$IsPassword = $Parameters['IsPassword'] ? 'true' : 'false';
			$JSParamArray = array (
								'IsCptch'  => $IsCaptcha,
								'IsPsswrd' => $IsPassword,
								'LoadSmiles' => 'true'
							);

			//ошибки
			$JSTextArray = array (
							'EmptyCaptcha'      	 => $ForumLang['Javascript']['EmptyCaptcha'],
							'ProfileEmptyRepeatPass' => $ForumLang['Javascript']['ProfileEmptyRepeatPass'],
							'ProfileEmptyMail' 		 => $ForumLang['Javascript']['ProfileEmptyMail'],
							'ProfileEmptyDate' 		 => $ForumLang['Javascript']['ProfileEmptyDate']
			);
			break;
		}

		case'search': {
			//ошибки
			$JSTextArray = array (
							'SearchEmptyFields' 	 => $ForumLang['Javascript']['SearchEmptyFields']
			);
			break;
		}
	}

	$TextParamArray = array ();

	//в строки
	$TextString  = '';
	foreach ($JSTextArray as $key=>$value) {
		$TextString .= "\t" . $key . ' = \'' . $value . '\';' . "\r\n";
	}
	$ParamString = '';
	foreach ($JSParamArray as $key=>$value) {
		$ParamString .= "\t" . $key . ' = ' . $value . ";\r\n";
	}
	if (Main_Strlen ($ParamString) > 0) {
		$TextParamArray[] = $ParamString;
	}
	if (Main_Strlen ($TextString) > 0) {
		$TextParamArray[] = $TextString;
	}
	if (sizeof ($TextParamArray) > 0) {
		$Return .= '<script type="text/javascript">'."\r\n" . implode ("\r\n", $TextParamArray) .'</script>';
	}
	return ($Return);
}

//функция вывода главной навигации
function Echo_PrintMainNavigation () {
	global $SelfName, $ForumLang, $UserGroups_Permissions;

	$Return = '';
	$HeaderHref = array ();

	//1.Главная
	$HeadHref[$SelfName] = $ForumLang['Navig']['Main'];

	//2.Профиль/Регистрация
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		if (OBB_ALLOW_REGISTRATION) {
			$HeadHref[$SelfName . '?action=registration'] = $ForumLang['Navig']['Reg'];
		}
	}
	else {
		if (
				$_SESSION['UserData']['UserType'] == 'admin'
				||
				(
					$UserGroups_Permissions['VisitUserProfile']
					&&
					OBB_WATCH_PROFILE
				)
		) {
			$HeadHref[$SelfName . '?action=profile&user_id=' . $_SESSION['UserData']['UserID']] = $ForumLang['Navig']['Profile'];
		}
	}

	//2.Вход/Выход
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		$HeadHref[$SelfName . '?action=login'] = $ForumLang['Navig']['Login'];
	}
	/* else {
		$HeadHref[$SelfName . '?action=logout'] = $ForumLang['Navig']['Logout'];
	} */

	//3.Список пользователей
	if (
			$_SESSION['UserData']['UserType'] == 'admin'
			||
			(
				$UserGroups_Permissions['VisitUserlist']
				&&
				OBB_SHOW_USERLIST
			)
	) {
		$HeadHref[$SelfName . '?action=usersview'] = $ForumLang['Navig']['Members'];
	}

	//4.Поиск
	$HeadHref[$SelfName . '?action=search'] = $ForumLang['Navig']['Search'];

	//5.Правила
	$HeadHref[$SelfName.'?action=tooling&label=rules'] = $ForumLang['Navig']['Rules'];

	$SizeOf = sizeof ($HeadHref);
	$First  = 0;
	$Last   = $SizeOf - 1;
	$Count  = 0;

	$Return .= '<ul id="HeaderTopMenuUL">';
	foreach ($HeadHref as $key=>$value) {
		if ($Count == $First) {
			$Class = 'First';
		}
		else if ($Count == $Last) {
			$Class = 'Last';
		}
		else {
			$Class = 'Standart';
		}
		$Return .= '<li class="' . $Class . '"><a title="' . $value . '" href="' . Defence_HTMLSpecials ($key) . '">' . $value . '</a></li>';
		$Count++;
	}
	$Return .= '</ul>';
	return ($Return);
}

//Футер
function Echo_PrintFoot () {
	global $Config_FooterText;
	$Result  = '
										<div id="BottomDelimiterDiv"><!-- --></div>
									</td>
								</tr>
							</table>
							<!-- Content table END -->
						</td>
						<!-- Container table TD END -->
					</tr>
				</table>
				<!-- Container table END -->
			<div id="Garant"></div>
		</div>
		<!-- Container END -->
		<!-- Footer -->
		<div id="Footer">
			<table id="FooterTable" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td id="FooterTD">
						' . $Config_FooterText . '
					</td>
				</tr>
			</table>
		</div>
		<!-- Footer END -->
	</body>
</html>';

	return ($Result);
}

//вывод ошибки в блок на форме
function Echo_DisplayUserErrors ($ErrorArray, $BlockTitle) {
	$Return = '';
	$Return .= '<fieldset><legend>' . $BlockTitle . '</legend><ul>';
	foreach ($ErrorArray as $key=>$value) {
		$Return .= '<li>' . $value . '</li>';
	}
	$Return .= '</ul></fieldset>';
	return ($Return);
}

//функция показа BB-редактора
function Echo_BBEditor ($Parameters) {
	global $ForumLang;

	$TempBBArray  = $Parameters['BBArray'];
	$NumberCols   = $Parameters['NumberCols'];
	$NumberRows   = $Parameters['NumberRows'];
	$SmilesLink   = $Parameters['SmilesLink'];
	$ImagesPath   = $Parameters['ImagesPath'];
	$TextareaName = $Parameters['TextareaName'];
	$TextareaID   = $Parameters['TextareaID'];
	$FormID       = $Parameters['FormID'];
	$ColorsArray  = isset ($Parameters['ColorsArray']) ?
					$Parameters['ColorsArray']         :
					array ();

	$BBArray = array ();
	foreach ($TempBBArray as $TempKey=>$TempValue) {
		$OneBBArray = array ();
		$BBType  = $TempValue['BBType'];
		if ($BBType == 'simple') {
			$SubType = $TempValue['BBSubType'];
			switch ($SubType) {
				case'b': {
					$Onclick = 'addTagBold(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'bold';
					$Title   = 'Bold';
					break;
				}
				case'i': {
					$Onclick = 'addTagItalic(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'italic';
					$Title   = 'Italic';
					break;
				}
				case'u': {
					$Onclick = 'addTagUnderline(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'underline';
					$Title   = 'Underline';
					break;
				}
				case's': {
					$Onclick = 'addTagStrike(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'strike';
					$Title   = 'Strike';
					break;
				}
				case'code': {
					$Onclick = 'addTagCode(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'code';
					$Title   = 'Code';
					break;
				}
				case'quote': {
					$Onclick = 'addTagQuote(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'quote';
					$Title   = 'Quote';
					break;
				}
				case'list': {
					$Onclick = 'addTagList(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'list';
					$Title   = 'List';
					break;
				}
				case'url': {
					$Onclick = 'addTagURL(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'url';
					$Title   = 'Url';
					break;
				}
				case'img': {
					$Onclick = 'addTagImg(\'' . $FormID . '\', \'' . $TextareaID . '\')';
					$Source  = 'img';
					$Title   = 'Img';
					break;
				}
				case'smile': {
					$Onclick = 'popupSmileBlock(\'' . $FormID . '\', \'' . $TextareaID . '\', \'' . $SmilesLink . '\')';
					$Source  = 'smiley';
					$Title   = 'Smile';
					break;
				}
				case'color': {
					$Onclick = 'popupColorBlock()';
					$Source  = 'color';
					$Title   = 'Color';
					break;
				}
			}
			$OneBBArray['Type']    = 'default';
			$OneBBArray['Source']  = OBB_IMAGE_DIR . '/bbcodes/' . $Source . '.png';
			$OneBBArray['Onclick'] = $Onclick;
			$OneBBArray['Title']   = $ForumLang['BBEditor'][$Title];
		}
		else {
			$OneBBArray['Type'] = 'divider';
		}
		$BBArray[] = $OneBBArray;
	}

	ob_start ();
	include (OBB_MVC_DIR . '/BBEditor_html_tpl.php');
	$Return = ob_get_contents ();
	ob_end_clean ();
	return ($Return);
}

//получение ширины колонки
function Echo_GetColumnWidth ($AdminWidth, $StandartWidth) {
	return ($_SESSION['UserData']['UserType'] == 'admin' ? intval ($AdminWidth) : intval ($StandartWidth));
}

//получение значения colspan
function Echo_GetColspan ($AdminColspan, $StandartColspan) {
	return ($_SESSION['UserData']['UserType'] == 'admin' ? intval ($AdminColspan) : intval ($StandartColspan));
}

//получение иконки форума
function Echo_GetCharterIcon ($CharterID, $Description) {
	if (file_exists (OBB_IMAGE_DIR . '/ChartIcons/' . $CharterID . '.png')) {
		$CharterIcon = $CharterID;
	}
	else {
		$CharterIcon = 'default';
	}
	$Return = '<img alt="" title="' . $Description . '" src="' . OBB_IMAGE_DIR . '/ChartIcons/' . $CharterIcon . '.png" />';
	return ($Return);
}

//вывод "Ваших возможностей
function Echo_ShowGroupAbilities () {
	global $UserGroups_Permissions, $ForumLang;

	$Abilities = '';
	if ($_SESSION['UserData']['UserType'] == 'admin') {
		$Abilities .= '<div class="AllAbilities">' . $ForumLang['YouHaveAllAbilities'] . '</div>';
	}
	else {
		//1)Добавление тем
		if ($UserGroups_Permissions['AddThemes'] && OBB_ADD_THEMES) {
			$AddThemesAbility = $ForumLang['AllowCreateThemes'];
		}
		else {
			$AddThemesAbility = $ForumLang['DisAllowCreateThemes'];
		}
		$Abilities .= '<div>' . $AddThemesAbility . '</div>';

		//2)Добавление постов
		if ($UserGroups_Permissions['AddPosts'] && OBB_ADD_POSTS) {
			$AddPostsAbility = $ForumLang['AllowCreatePosts'];
		}
		else {
			$AddPostsAbility = $ForumLang['DisAllowCreatePosts'];
		}
		$Abilities .= '<div>' . $AddPostsAbility . '</div>';

		//3)Редактирование постов
		if ($UserGroups_Permissions['EditPosts'] && OBB_EDIT_POSTS) {
			$EditPostsAbility = $ForumLang['AllowEditPosts'];
		}
		else {
			$EditPostsAbility = $ForumLang['DisAllowEditPosts'];
		}
		$Abilities .= '<div>' . $EditPostsAbility . '</div>';

		//4)Удаление постов
		if ($UserGroups_Permissions['DeletePosts'] && OBB_DELETE_POSTS) {
			$DeletePostsAbility = $ForumLang['AllowDeletePosts'];
		}
		else {
			$DeletePostsAbility = $ForumLang['DisAllowDeletePosts'];
		}
		$Abilities .= '<div>' . $DeletePostsAbility . '</div>';

		//5)Поиск по форуму
		if ($UserGroups_Permissions['SearchAllowed'] && OBB_SEARCH_ALLOWED) {
			$SearchAbility = $ForumLang['AllowSearch'];
		}
		else {
			$SearchAbility = $ForumLang['DisAllowSearch'];
		}
		$Abilities .= '<div>' . $SearchAbility . '</div>';
	}
	$Return = $Abilities;

	return ($Return);
}

//функция вычисления статуса пользователя
function Echo_GetUserStatus ($NumPosts, $NumPostsInStatus, $MaxStatus, $ImageDir) {
	$UserStatus = floor($NumPosts/$NumPostsInStatus);
	if ($UserStatus > $MaxStatus) {
		$UserStatus = $MaxStatus;
	}
	else if ($UserStatus < 1) {
		$UserStatus = 1;
	}
	$StatusImage = '';
	for ($i = 0; $i < $UserStatus; $i++) {
		$StatusImage .= '<img style="padding-left:1px;" title="" alt="" src="' . $ImageDir . '/star.png" />';
	}
	if ($UserStatus < $MaxStatus) {
		$StatusDiff = $MaxStatus - $UserStatus;
		for ($i = 0; $i < $StatusDiff; $i++) {
			$StatusImage .= '<img style="padding-left:1px;" title="" alt="" src="' . $ImageDir . '/star_off.png" />';
		}
	}
	return ($StatusImage);
}

function Echo_SignatureSeparator () {
	return ('--------------------------');
}

//функция постоения конфиг-массива для RSS
function OBB_RSS_SetConfig ($Description, $ForumID, $ForumName='') {
	global $Config_DBType, $ForumConnection, $ForumLang, $Config_ForumName, $HostName, $SelfName;

	//дата
	if (is_integer ($ForumID)) {
		$SQL = 'SELECT MAX(PostDate) AS RSSTime FROM posts WHERE ForumID = \'' . $ForumID . '\'';
	}
	else {
		$SQL = 'SELECT MAX(PostDate) AS RSSTime FROM posts';
	}
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	$RSSDate = DB_Result ($Config_DBType, $Query, 0, 0);

	//название ленты
	if (Main_Strlen ($ForumName) > 0) {
		$RSSTitleOneForum = OBB_Main_ReplaceSymbols ($ForumLang['RSSTitleOneForum'], array('limit'=>15, 'forum_name'=>$ForumName));
		$RSSTitle = $RSSTitleOneForum . ' "' . $Config_ForumName . '"';
	}
	else {
		$RSSTitle = $ForumLang['RSSTitleCommon'] . ' "' . $Config_ForumName . '"';
	}

	//ссылка на ленту
	if (is_integer ($ForumID)) {
		$RSSLink = 'http://' . $HostName;
	}
	else {
		$RSSLink = 'http://' . $HostName . $SelfName . '?id_forum=' . $ForumID;
	}

	//массив
	$ConfigRSS = array (
					'RSSTitle' => $RSSTitle,
					'RSSLink' => $RSSLink,
					'RSSDescription' => $Description,
					'RSSLastDate' => $RSSDate,
					'RSSLanguage' => OBB_RSS_LANGUAGE,
					'RSSGenerator' => OBB_RSS_GENERATOR,
					'RSSProgrammer' => OBB_RSS_POGRAMMER_MAIL
				);

	return ($ConfigRSS);
}

//функция очистки фразы для подсветки от нежелательных символов
function OBB_Search_PrepareToHighlight ($String) {
	return preg_quote ($String, "/");
}

//функция подсветки искомой фразы
//ПРИМЕЧАНИЕ: в рабочей версии простую подсветку УБРАТЬ!!!
function OBB_Search_Highlight ($HighlightArray, $Text, $IsSimpleReplace) {
	if ($IsSimpleReplace) {
		/* foreach ($HighlightArray as $key=>$value) {
			$Text = preg_replace ('/(' . $value . ')/ui', '<font color="red">\1</font>', $Text);
		} */
	}
	else {
		preg_match_all('/<[^>]*>/ui', $Text, $HTMLTags);
		$HTMLTags = array_unique ($HTMLTags);

		$TagList = array();
		$Num = 0;
		foreach($HTMLTags[0] as $value) {
			$TagList[++$Num] = $value;
			$Text = str_replace($value, '<' . $Num . '>', $Text);
		}

		foreach ($HighlightArray as $key=>$val) {
			if (!is_numeric ($val)) {
				$Text = preg_replace ('/' . $val . '/ui', '<span style="color:#AA0000; background:yellow;">$0</span>', $Text);
			}
		}

		foreach($TagList as $key=>$val) {
			$Text = str_replace ('<' . $key . '>', $val, $Text);
		}
	}
	return ($Text);
}

//функция очистки поста от html-тегов - для поиска
function OBB_Search_PrepareToSearch ($Text) {
	//1)Простая замена: <b> <u> <i> <s>
	$SimpleStartArray   = array ();
	$SimpleReplaceArray = array ();

	$SimpleStartArray[]   = '/\<(?:\/)?(b|i|u|strike)\>/ui';
	$SimpleReplaceArray[] = '';

	$Text = preg_replace ($SimpleStartArray, $SimpleReplaceArray, $Text);

	//2)<br>
	$BRStartArray   = array ();
	$BRReplaceArray = array ();

	$BRStartArray[] = '/(\<br(?: \/)?\>)+/ui';
	$BRReplaceArray[] = "\n";

	$Text = preg_replace ($BRStartArray, $BRReplaceArray, $Text);

	//3)<div> </div> <a> </a> <img>
	$DivStartArray   = array ();
	$DivReplaceArray = array ();

	//  --заголовки цитаты и кода
	$DivStartArray[]   = '/\<div[\s]+class="obb_bb_codehead"\>.*?\<\/div\>/ui';
	$DivReplaceArray[] = "\n";
	$DivStartArray[]   = '/\<div[\s]+class="obb_bb_quotehead"\>.*?\<\/div\>/ui';
	$DivReplaceArray[] = "\n";

	//  --div у img
	$DivStartArray[]   = '/\<div[\s]+class="obb_bb_img"\>.*?\<\/div\>/ui';
	$DivReplaceArray[] = "\n";

	//  --оставшиеся теги div
	$DivStartArray[]   = '/\<(?:\/)?div.*?\>/ui';
	$DivReplaceArray[] = "\n";

	$Text = preg_replace ($DivStartArray, $DivReplaceArray, $Text);

	//  --тег img
	$ImgStartArray   = array ();
	$ImgReplaceArray = array ();

	$ImgStartArray[]   = '/\<img(?:.*?)\/\>/ui';
	$ImgReplaceArray[] = "\n";

	$Text = preg_replace ($ImgStartArray, $ImgReplaceArray, $Text);

	//4)Тег url, mail
	$URLStartArray   = array ();
	$URLReplaceArray = array ();

	$URLStartArray[]   = '/\<a(?:.*?)\>.*?\<\/a\>/ui';
	$URLReplaceArray[] = "\n";

	$Text = preg_replace ($URLStartArray, $URLReplaceArray, $Text);

	//5)Тег ul и теги li внутри него
	$Text = preg_replace_callback ('/(\<ul(?:.*?)\>(.*?)\<\/ul\>)/ui', 'OBB_Callback_Ul', $Text);

	//6)Тег span
	$Text = preg_replace ('/\<(?:\/)?span.*?\>/ui', '', $Text);

	//7)Убираем лишние переносы строки+trim
	$Text = preg_replace ("/\n{2,}/ui", "\n", $Text);
	$Text = trim ($Text);
	$Text = preg_replace ("/\n/ui", ' ', $Text);
	$Text = preg_replace ("/\s/ui", " ", $Text);

	//8)htmlspecialchars_decode
	$Text = htmlspecialchars_decode ($Text, ENT_QUOTES);

	return ($Text);
}

//обратный вызов - ul
function OBB_Callback_Ul ($matches) {
	$Match = $matches[2];
	$Return = preg_replace('/\<li\>(.*?)\<\/li\>/ui', "\n\\1\n", $Match);
	return ($Return);
}

//функция запрета ссылок в постах
function OBB_Main_DisallowURLs ($Text, $ReplaceText) {
	$ReplaceText = '<span class="NoURLs">[' . $ReplaceText . ']</span>';
	$HrefPattern = '/\<a(?:.*?)\>.*?\<\/a\>/ui';
	$Text = preg_replace ($HrefPattern, $ReplaceText, $Text);
	return ($Text);
}

?>
