<?php
//Глобальные переменные
$DocRoot  = $_SERVER['DOCUMENT_ROOT'];
$SelfName = $_SERVER['PHP_SELF'];
$HostName = $_SERVER['HTTP_HOST'];
$Now      = time();

define('NO_DEBUG', true);

//Debug - тест скорости
$start = microtime ();

//Подключение конфига
if (file_exists ('config.php')) {
	include ('config.php');
}
else {
	exit ('Bad config!');
}

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Bad validation!');
}

//Подключение основных библиотек
include (OBB_KERNEL_DIR . '/DBLib.php');
include (OBB_KERNEL_DIR . '/ForumFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Common_' . $Config_Lang . '.php');

//Настройки интерпретатора
OBB_Main_SetPHPConfigs ();

//режим "Сервер временно недоступен"
OBB_Main_SetMaintenanceMode ();

//массив настройки почты
$Config_Mail = OBB_Main_GetMailConfig ();

//Соединение с сервером
$ForumConnection = OBB_Main_ConnectDBServer ();

//выставление верной кодировки (UTF8) в БД
OBB_Main_SetDatabaseUTF8 ();

//Выбор базы данных
$ForumDatabase = OBB_Main_SelectDatabase ();

//ip пользователя
$UserIP = OBB_Main_GetUserIPAddress ();

//Стартует сессия
$SessionID = OBB_Main_SessionStart ();

//получение массива групп пользователей
$UserGroups_Array = OBB_Main_GetUserGroups ();

//получение полного массива разрешений групп
$UserGroups_AllPermissions = OBB_Main_GetPermissions ();

//получение разрешений текущего пользователя
$UserGroups_Permissions = OBB_Main_GetUserPermissions ();

//разрешение на посещение форума
if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['VisitCommon']) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}

//бан или блокировка
$Session_UserID = $_SESSION['UserData']['UserType'] == 'member' ? intval ($_SESSION['UserData']['UserID']) : NULL;
$UserBanArray = array ('ip'=>$UserIP, 'id'=>$Session_UserID);
$User_BanArray = OBB_Main_CheckBanned ($UserBanArray);
$Banned_IPBanned = $User_BanArray['ip'];
$Banned_IDBanned = $User_BanArray['id'];
$Banned_IDBannedFull = (bool)$User_BanArray['id_full'];
$Banned_IDBannedAdd  = (bool)$User_BanArray['id_add'];
$Banned_IDBannedTime = $User_BanArray['id_time'];

//проверка блокировки
// 1)по ИП
if ($Banned_IPBanned && $_SESSION['UserData']['UserType'] <> 'admin') {
	OBB_Main_ShowError ('common_ip_banned', 'STANDART', $ForumLang['Errors']);
}
// 2)по ИД
else if ($Banned_IDBanned && $Banned_IDBannedFull && $_SESSION['UserData']['UserType'] <> 'admin') {
	OBB_Main_ShowError ('common_id_banned_full', 'STANDART', $ForumLang['Errors'], array('time', $Banned_IDBannedTime));
}

//автологин
OBB_Main_Autologin ();

//формирование пер-ной, содержащей тек. действие
$Action = OBB_Main_GetForumAction ();

//делаем освобождение ненужных сессионных переменных
OBB_Main_KillSessionVars ();

//Переходный массив для JavaScript (язык)
$JavaScriptLangArray = OBB_Main_GetJSLanguages ();

//Подключение отдельных модулей в соответствии с действием
switch ($Action) {
	case "main":
	default:
	{
		include (OBB_SHOW_DIR . '/Main.php');
		break;
	}

	case "admin":
	{
		include (OBB_SHOW_DIR . '/Admin.php');
		break;
	}

	case "forumview":
	{
		include (OBB_SHOW_DIR . '/Themes.php');
		break;
	}

	case "themeview":
	{
		include (OBB_SHOW_DIR . '/Posts.php');
		break;
	}

	case "download":
	{
		include (OBB_SHOW_DIR . '/Download.php');
		break;
	}

	case "search":
	{
		include (OBB_SHOW_DIR . '/Search.php');
		break;
	}

	case "usersview":
	{
		include (OBB_SHOW_DIR . '/UserList.php');
		break;
	}

	case "online":
	{
		include (OBB_SHOW_DIR . '/UsersOnline.php');
		break;
	}

	case "add":
	{
		include (OBB_SHOW_DIR . '/Add.php');
		break;
	}

	case "registration":
	{	    
		include (OBB_SHOW_DIR . '/Registration.php');
		break;
	}

	case "login":
	{
		include (OBB_SHOW_DIR . '/Login.php');
		break;
	}

	case "forgot":
	{
		include (OBB_SHOW_DIR . '/ForgotPass.php');
		break;
	}

	case "logout":
	{
		include (OBB_SHOW_DIR . '/Logout.php');
		break;
	}

	case "profile":
	{
		include (OBB_SHOW_DIR . '/UserProfile.php');
		break;
	}

	case "message":
	{
		include (OBB_SHOW_DIR . '/Message.php');
		break;
	}

	case "captcha":
	{
		include (OBB_SHOW_DIR . '/Captcha.php');
		break;
	}

	case "rss":
	{
		include (OBB_SHOW_DIR . '/RSS.php');
		break;
	}

	case "tooling":
	{
		include (OBB_SHOW_DIR . '/Tools.php');
		break;
	}

	case "privatemsg":
	{
		include (OBB_SHOW_DIR . '/PrivateAdd.php');
		break;
	}
}

//Debug - тест скорости
$end = microtime ();
//echo '<script>alert("'.($end - $start).'")</script>';
/* if (!defined ('NO_DEBUG')) {
	echo '<br />' . ($end - $start);
} */
//echo '<br />' . memory_get_usage(true);
//Контрольный выход
exit ();

?>