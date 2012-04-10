<?php

//админская конфигурация
$ADMIN_VALIDATION = '1';

//Глобальные переменные
$DocRoot   = $_SERVER['DOCUMENT_ROOT'];
$SelfName  = 'index.php';
$AdminName = $_SERVER['PHP_SELF'];
$HostName  = $_SERVER['HTTP_HOST'];
$Now       = time();

//Подключение конфига
if (file_exists ('config.php')) {
	include ('config.php');
}
else {
	exit ('Bad config!');
}

//Подключение библиотек
include (OBB_KERNEL_DIR . '/DBLib.php');
include (OBB_KERNEL_DIR . '/ForumFunctions.php');
include (OBB_KERNEL_DIR . '/OwnBB.Captcha.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.RSSGenerator.class.php');
include (OBB_KERNEL_DIR . '/PageLib.php');
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');
include (OBB_KERNEL_DIR . '/CacheFunctions.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');
include (OBB_ADMIN_DIR  . '/CommonFunctions.php');
include (OBB_ADMIN_DIR  . '/AdminFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Common_' . $Config_Lang . '.php');

//выставление настроек интерпретатора
OBB_Admin_SetPHPConfiguration ();

//соединение с сервером БД
$ForumConnection = OBB_Admin_ConnectDBServer ();

//выставление клиентской кодировки в UTF8
OBB_Admin_SetDatabaseUTF8 ();

//выбор базы данных
$ForumDatabase = OBB_Admin_SelectDatabase ();

//ip пользователя
$UserIP = OBB_Admin_GetUserIPAddress ();

//массив настройки почты
$Config_Mail = OBB_Admin_GetMailConfig ();

//старт СЕССИИ
$SessionID = OBB_Admin_SessionStart ();

//проверка, является ли человек админом
OBB_Admin_CheckIfAdmin ();

//Подключение отдельных модулей в соответствии с действием
switch ($Action) {
	case "main":
	default: 
	{
		
		break;
	}
	
	case "fast_install":
	{
		include (OBB_ADMIN_DIR . '/FastInstall.php');
		break;
	}
	
	case "clean_forum":
	{
		include (OBB_ADMIN_DIR . '/CleanForum.php');
		break;
	}
    
    case "forum_edit":
	{
		include (OBB_ADMIN_DIR . '/EditForum.php');
		break;
	}
    
    case "forum_block":
	{
		include (OBB_ADMIN_DIR . '/BlockForum.php');
		break;
	}
    
    case "forum_delete":
	{
		include (OBB_ADMIN_DIR . '/DeleteForum.php');
		break;
	}
    
    case "theme_edit":
	{
		include (OBB_ADMIN_DIR . '/EditTheme.php');
		break;
	}
    
    case "theme_block":
	{
		include (OBB_ADMIN_DIR . '/BlockTheme.php');
		break;
	}
    
    case "theme_delete":
	{
		include (OBB_ADMIN_DIR . '/DeleteTheme.php');
		break;
	}
}

//Контрольный выход
exit ();

?>