<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//Подключение административных админпанели
include (OBB_ADMIN_DIR  . '/AdminFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Admin_' . $Config_Lang . '.php');

//проверка на вхождение в админскую часть
//OBB_Admin_CheckAdminPermissions ();

//определение текущего скрипта
$AdminModule = OBB_Admin_GetAdminModule ();

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $AdminModule . '.php');

//контрольный выход
exit;

?>