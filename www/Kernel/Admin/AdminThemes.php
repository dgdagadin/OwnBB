<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$ThemesActionArray = array ('list', 'edit', 'del', 'block');
$ThemesModuleArray = array (
						'list' =>'AdminThemesList',
						'edit' =>'AdminThemeEdit',
						'block'=>'AdminThemeBlock',
						'del'  =>'AdminThemeDelete'
					);
$ThemesActionVar = 'tact';
$ThemesModule = OBB_Main_GetModule ($ThemesActionArray, $ThemesModuleArray, $ThemesActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $ThemesModule . '.php');

?>