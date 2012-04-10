<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$UsersActionArray = array ('list', 'edit', 'del');
$UsersModuleArray = array (
						'list' =>'AdminUsersList',
						'edit' =>'AdminUserEdit',
						'del'  =>'AdminUserDelete'
					);
$UsersActionVar = 'uact';
$UsersModule = OBB_Main_GetModule ($UsersActionArray, $UsersModuleArray, $UsersActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $UsersModule . '.php');

?>