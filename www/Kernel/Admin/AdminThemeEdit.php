<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

if (isset ($_GET['tid'])) {
	$TestPhrase = 'Edit';
}
else {
	$TestPhrase = 'Add';
}

echo 'Admin Theme ' . $TestPhrase;

?>