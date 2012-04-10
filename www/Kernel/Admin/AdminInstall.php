<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//Подключение административных админпанели
include (OBB_ADMIN_DIR  . '/InstallFunctions.php');

OBB_Admin_FastForumInstall ();

?>