<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$ForumsActionArray = array ('list', 'edit', 'del', 'block');
$ForumsModuleArray = array (
						'list' =>'AdminForumsList',
						'edit' =>'AdminForumEdit',
						'block'=>'AdminForumBlock',
						'del'  =>'AdminForumDelete'
					);
$ForumsActionVar = 'fact';
$ForumsModule = OBB_Main_GetModule ($ForumsActionArray, $ForumsModuleArray, $ForumsActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $ForumsModule . '.php');

?>