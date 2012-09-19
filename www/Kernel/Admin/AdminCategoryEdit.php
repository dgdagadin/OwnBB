<?php

//Проверка определяющей переменной
//if (!isset ($VALIDATION) || $VALIDATION <> '1') {
//	exit ('Hi, hacker!');
//}

include ('AdminConnect.php');

$MainOutput = '';

if (isset ($_GET['cid'])) {
	$TestPhrase = 'Edit';
}
else {
	if (isset($_POST['CategoryAdd'])) {
		if (isset($_POST['CategoryName'])) {
			$CategoryName = trim($_POST['CategoryName']);
		}
		else {
			$CategoryName = '';
		}
		
		if (isset($_POST['CategoryPosition']) && ctype_digit($_POST['CategoryPosition'])) {
			$CategoryPosition = intval($_POST['CategoryPosition']);
		}
		else {
			$CategoryPosition = intval(0);
		}
	
		$SQL = 'INSERT INTO charters ("CharterID", "CharterPosition") VALUES (\'' . DB_EscapeString($Config_DBType, ) . '\', ' . $CategoryPosition . ')';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), '../../' . OBB_ERROR_LOG_FILE);
		}
	}
	$MainOutput .= ' <div>
						<div>
							<strong>Добавление новой категории</strong>
						</div>
						<div>
							<form action="http://' . $Config_HostName . '/Kernel/Admin/AdminCategoryEdit.php?cact=edit" method="post">
								<div>
									<strong>Имя категории:</strong>&nbsp;<input type="text" name="CategoryName" value="" />
									<br />
									<strong>Позиция категории:</strong>&nbsp;<input type="text" name="CategoryPosition" value="" />
									<br />
									<input type="hidden" name="CategoryAdd" value="1" />
									<input type="submit" value="Добавить" value="1" />
								</div>
							</form>
						</div>
					 </div>';
}

Main_ShowStandartHeader ();
echo $MainOutput;

?>