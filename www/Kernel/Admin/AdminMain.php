<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

$MainOutput  = '<a href="http://' . $HostName . $SelfName . '?action=admin&adm=cat">Категории</a>';
$MainOutput .= '&nbsp;&nbsp;';
$MainOutput .= '<a href="http://' . $HostName . $SelfName . '?action=admin&adm=forum">Форумы</a>';
$MainOutput .= '&nbsp;&nbsp;';
$MainOutput .= '<a href="http://' . $HostName . $SelfName . '?action=admin&adm=config">Конфигурация</a>';

Main_ShowStandartHeader ();
echo $MainOutput;

?>