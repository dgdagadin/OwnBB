<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$GroupsActionArray = array ('list', 'edit', 'del');
$GroupsModuleArray = array (
						'list' =>'AdminGroupsList',
						'edit' =>'AdminGroupEdit',
						'del'  =>'AdminGroupDelete'
					);
$GroupsActionVar = 'gact';
$GroupsModule = OBB_Main_GetModule ($GroupsActionArray, $GroupsModuleArray, $GroupsActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $GroupsModule . '.php');

?>