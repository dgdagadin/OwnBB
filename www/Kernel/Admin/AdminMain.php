<?php

//Проверка определяющей переменной
//if (!isset ($VALIDATION) || $VALIDATION <> '1') {
//	exit ('Hi, hacker!');
//}

include ('AdminConnect.php');

$MainOutput  = '<a href="http://' . $Config_HostName . '/Kernel/Admin/AdminCategoriesList.php">Категории</a>';
$MainOutput .= '&nbsp;&nbsp;';
$MainOutput .= '<a href="http://' . $Config_HostName . '/Kernel/Admin/AdminForumsList.php">Форумы</a>';
$MainOutput .= '&nbsp;&nbsp;';
$MainOutput .= '<a href="http://' . $Config_HostName . '/Kernel/Admin/AdminConfig.php">Конфигурация</a>';

Main_ShowStandartHeader ();
echo $MainOutput;

?>