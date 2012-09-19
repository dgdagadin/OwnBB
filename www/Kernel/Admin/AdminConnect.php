<?php

//Проверка определяющей переменной
include ('../../config.php');
include ('../Includes/ForumFunctions.php');
include ('../Includes/DBLib.php');

OBB_Main_SetPHPConfigs ();

//Соединение с сервером
$ForumConnection = OBB_Main_ConnectDBServer ();

//выставление верной кодировки (UTF8) в БД
OBB_Main_SetDatabaseUTF8 ();

//Выбор базы данных
$ForumDatabase = OBB_Main_SelectDatabase ();

//ip пользователя
$UserIP = OBB_Main_GetUserIPAddress ();

?>