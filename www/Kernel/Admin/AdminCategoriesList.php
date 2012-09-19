<?php

//Проверка определяющей переменной
//if (!isset ($VALIDATION) || $VALIDATION <> '1') {
//	exit ('Hi, hacker!');
//}
include ('AdminConnect.php');

$ChartersArray = array();
$SQL = 'SELECT * FROM Charters';
$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
while ($Row = DB_FetchAssoc($Config_DBType, $Query)) {
	$CharterID   = $Row['CharterID'];
	$CharterName = $Row['CharterName'];
	$CharterPos  = $Row['CharterPosition'];
	$Charters[$CharterID] = array('Name'    =>$CharterName,
								  'Position'=>$CharterPos);
}

$MainOutput = '';
$MainOutput .= '<a href="http://' . $Config_HostName . '/Kernel/Admin/AdminCategoryEdit.php?cact=edit">Add category</a><br /><br />';
foreach ($Charters as $Key=>$Value) {
	$ID = $Key;
	$Name = $Value['Name'];
	$Pos  = $Value['Position'];

	$MainOutput .= '<strong>' . $Name . '</strong>&nbsp;' . $Pos . '&nbsp;&nbsp;<a href="http://' . $Config_HostName . '/Kernel/Admin/AdminCategoryEdit.php?cact=edit&cid=' . $ID . '">Edit</a><br />';
}

Main_ShowStandartHeader ();
echo $MainOutput;

?>