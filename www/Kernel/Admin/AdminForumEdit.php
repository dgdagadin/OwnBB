<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

if (isset ($_GET['fid'])) {
	$TestPhrase = 'Edit';
}
else {
	$TestPhrase = 'Add';
}

echo 'Admin Forum ' . $TestPhrase;

?>