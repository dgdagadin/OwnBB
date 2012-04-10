<?php

//откат предыдущих запросов
function Main_RollbackQueries ($QueryArray) {
	global $Config_DBType, $ForumConnection;
	$QueriesArray = array ();
	
	foreach ($QueryArray as $key=>$val) {		
		$Operation  = strtolower ($val[0]);
		$FieldArray = $val[1];
		$WhereArray = $val[2];
		$TableName  = $val[3];		
		$WhereFields = array ();
		$FieldOperations = array ();
		
		$SQL = '';
		$Action = $Operation == 'd' ? 'DELETE' : 'UPDATE';
		$From   = $Operation == 'd' ? 'FROM'   : '';
		$Set    = $Operation == 'u' ? 'SET'    : '';
		
		foreach ($FieldArray as $FieldKey=>$FieldValue) {						
			$OneOperation = $FieldKey . '=';
			$Value = $FieldValue[0];
			$Flag  = $FieldValue[1];
			
			if ($Flag == 'none') {
				$OneOperation .= '\'' . Defence_EscapeString ($Config_DBType, $Value) . '\'';
			}
			else {
				$Operator = $Flag == 'inc' ? '+' : '-';
				$OneOperation .= $FieldKey . $Operator . '1';
			}
			$FieldOperations[] = $OneOperation;
		}
		
		foreach ($WhereArray as $WhereKey=>$WhereValue) {
			$OneDef = $WhereKey . '=\'' . Defence_EscapeString ($Config_DBType, $WhereValue) . '\'';
			$WhereFields[] = $OneDef;
		}
		
		$OperationString = sizeof ($FieldOperations) > 0 ? implode (', ', $FieldOperations) : '';
		$WhereString = implode (' AND ', $WhereFields);
		
		$SQL .= $Action . ' ' . $From . ' ' . $TableName . ' ' . $Set . ' ' . $OperationString . ' WHERE ' . $WhereString;
		$QueriesArray[] = $SQL;
	}
	foreach ($QueriesArray as $QueryKey=>$QueryVal) {
		DB_Query ($Config_DBType, $QueryVal, $ForumConnection);
		//Logging
	}
}

//проверка файла
function Defence_CheckAttachFile ($FileDataArray, $MaxAttachSize, $MaxImageWidth, $MaxImageHeight) {
	global $FilesArray;
	$ResultArray = array ();
	
	//1)проверка, загружен ли
	if (!is_uploaded_file ($FileDataArray['tmp_name'])) {
		$ResultArray['IsTrue'] = FALSE;
		$ResultArray['Error'] = 'not_upload';
		return ($ResultArray);
	}
	
	//2)присвоение
	$FileName = $FileDataArray['name'];
	$TMPName  = $FileDataArray['tmp_name']; 
	$FileSize = $FileDataArray['size'];
	$FileType = $FileDataArray['type'];
	
	//3)количество точек
	$DotsArray = explode ('.', $FileName);
	$NumOfDots = sizeof ($DotsArray);
	if ($NumOfDots > 2) {
		$ResultArray['IsTrue'] = FALSE;
		$ResultArray['Error'] = 'too_many_dots';
		return ($ResultArray);
	}
	
	//4)Расширение
	$FilePathParts   = pathinfo ($FileName);
	$FileExtenseion  = strtolower ($FilePathParts['extension']);
	
	//4)сравниваем расширение и тип с допустимыми	
	$FileMimeArr = array ();
	foreach ($FilesArray as $FilesKey=>$FilesValue) {
		$Extension = $FilesValue['extension'];
		$IsImage   = $FilesValue['image'];
		$MimeArray = $FilesValue['mime'];		
		
		if ($FileExtenseion == $Extension) {
			$FileMimeArr = $MimeArray;
			break;
		}
	}
	$FileError = '';
	if (sizeof ($FileMimeArr) < 1) {		
		$FileError = 'bad_extension';	
	}
	else if (!in_array ($FileType, $FileMimeArr)) {
		$FileError = 'bad_mime';
	}
	if ($FileError <> '') {
		$ResultArray['IsTrue'] = FALSE;
		$ResultArray['Error'] = $FileError;
		return ($ResultArray);
	}
	
	//5)размер файла
	$FileTrueSize = filesize ($TMPName);
	if ($FileTrueSize == 0 || $FileTrueSize > $MaxAttachSize) {
		$ResultArray['IsTrue'] = FALSE;
		$ResultArray['Error'] = 'bad_size';
		return ($ResultArray);
	}
	
	//если файл является картинкой
	if ($IsImage == '1') {
		//6)getimagesize
		$ImageSizeArr = getimagesize ($TMPName);
		if (!$ImageSizeArr) {
			$ResultArray['IsTrue'] = FALSE;
			$ResultArray['Error'] = 'image_not';
			return ($ResultArray);
		}
		else {
			$ImageWidth  = $ImageSizeArr[0];
			$ImageHeight = $ImageSizeArr[1];
			if ($ImageWidth < 0 || $ImageHeight < 0 || $ImageWidth > $MaxImageWidth || $ImageHeight > $MaxImageHeight) {
				$ResultArray['IsTrue'] = FALSE;
				$ResultArray['Error'] = 'image_bad_size';
				return ($ResultArray);
			}			
		}
	}
	
	$ResultArray['IsTrue'] = TRUE;
	$ResultArray['Name']   = $FileName;
	$ResultArray['Type']   = $FileType;
	$ResultArray['Data']   = $TMPName;
	$ResultArray['Ext']    = $FileExtenseion;
	$ResultArray['Size']   = $FileTrueSize;	
	$ResultArray['Image']  = $IsImage;	
	if ($IsImage == '1') {
		$ResultArray['Width']  = $ImageWidth;
		$ResultArray['Height'] = $ImageHeight;		
	}
	return ($ResultArray);
}

function Access_UpdateOnlineInfo ($UserData, $SessionID, $Now, $Action) {
	global $Config_DBType, $ForumConnection;

	//Удаляем записи из активности гостей с превышенным интервалом онлайн
	$SQL = 'DELETE 
			FROM guest_activity
			WHERE GuestLastUpdate < (' . $Now . ' - ' . OBB_USER_ONLINE_TIME . ')';
				
	$DelGuestOnlQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$DelGuestOnlQuery) {
		exit ('Error while updating guest online activity');
	}
	
	//активность пользователей
	$SQL = 'UPDATE user_activity
			SET UserIsOnline=\'no\' 
			WHERE UserLastLogin < (' . $Now . ' - ' . OBB_USER_ONLINE_TIME . ')';
				
	$DelUserOnlQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$DelGuestOnlQuery) {
		exit ('Error while updating user online activity');
	}

	//2)Если гость -
	if ($UserData['UserType'] == 'guest') {
		//а)проверяем есть ли уже с таким ид сессии
		//б)вставляем/обновляем запись с полученным ид	
		$SQL = 'SELECT 1
		        FROM guest_activity
				WHERE SessionID = \'' . $SessionID . '\'';
		$ValidExistGuestQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$ValidExistGuestQuery) {
			exit ('Error while existing guest with current session_id!');
		}
		
		if (DB_NumRows ($Config_DBType, $ValidExistGuestQuery) > 0) {
			$SQL = 'UPDATE guest_activity
			        SET GuestLastUpdate = \'' . $Now . '\'
					WHERE SessionID = \'' . $SessionID . '\'';
		}
		else {
			$SQL = 'INSERT INTO guest_activity
			        (SessionID, GuestLastUpdate)
			        VALUES (\'' . $SessionID . '\', \'' . $Now . '\')';
		}
		
		$UpdateGuestOnlTimeQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$UpdateGuestOnlTimeQuery) {
			exit ('Error while updating online guest time!');
		}
	}
	//2)Если пользователь -
	else if ($UserData['UserType'] == 'member') {
		$SQL = 'UPDATE user_activity
		        SET UserLastLogin = \'' . $Now . '\', UserLastAction = \'' . $Action . '\', UserIsOnline = \'yes\'
				WHERE UserID = \'' . intval ($UserData['UserID']) . '\'';
		$UpdateUserOnlTimeQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$UpdateUserOnlTimeQuery) {
			exit ('Error while updating user activity!');
		}
	}
}

?>