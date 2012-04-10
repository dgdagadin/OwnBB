<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//получение модуля форумов
$CategoriesActionArray = array ('list', 'edit', 'del');
$CategoriesModuleArray = array (
						'list' =>'AdminCategoriesList',
						'edit' =>'AdminCategoryEdit',
						'block'=>'AdminCategoryBlock',
						'del'  =>'AdminCategoryDelete'
					);
$CategoriesActionVar = 'cact';
$CategoriesModule = OBB_Main_GetModule ($CategoriesActionArray, $CategoriesModuleArray, $CategoriesActionVar);

//подключение скрипта модуля
include (OBB_ADMIN_DIR  . '/' . $CategoriesModule . '.php');

?>