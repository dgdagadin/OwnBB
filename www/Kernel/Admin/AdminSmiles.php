<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$SmilesActionArray = array ('list', 'edit', 'del');
$SmilesModuleArray = array (
						'list' =>'AdminSmilesList',
						'edit' =>'AdminSmileEdit',
						'del'  =>'AdminSmileDelete'
					);
$SmilesActionVar = 'sact';
$SmilesModule = OBB_Main_GetModule ($SmilesActionArray, $SmilesModuleArray, $SmilesActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $SmilesModule . '.php');

?>