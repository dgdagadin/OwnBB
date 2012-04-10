<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//проверка прав доступа
if ($_SESSION['UserData']['UserType'] <> 'admin' && (!$UserGroups_Permissions['AttachesDownload'] || !OBB_ALLOW_ATTACHES)) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}


//идентификатор сообщения
if (!isset ($_GET['id_post']) || !ctype_digit ($_GET['id_post'])) {
	exit ('Wrong post identifier!');
}
$PostID = intval ($_GET['id_post']);

//получение идентификатора форума
$GetForumSQL = 'SELECT ForumID, ThemeID FROM posts WHERE PostID = \'' . $PostID . '\'';
$GetForumQuery = DB_Query ($Config_DBType, $GetForumSQL, $ForumConnection);
if (!$GetForumQuery) {
	OBB_Main_Log ($GetForumSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$GetForumNumRows = DB_NumRows ($Config_DBType, $GetForumQuery);
if ($GetForumNumRows < 1) {
	OBB_Main_ShowError ('add_no_post_exist', 'STANDART', $ForumLang['Errors']);
}
$ForumID = intval (DB_Result ($Config_DBType, $GetForumQuery, 0, 0));
$ThemeID = intval (DB_Result ($Config_DBType, $GetForumQuery, 0, 1));

//получение прав доступа форума
$ForumAccessSQL = 'SELECT ForumGuestView, ForumMinStatus FROM forums_list WHERE ForumID = \'' . $ForumID . '\'';
$ForumAccessQuery = DB_Query ($Config_DBType, $ForumAccessSQL, $ForumConnection);
if (!$ForumAccessQuery) {
	OBB_Main_Log ($ForumAccessSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$ForumAccessRow = DB_FetchAssoc ($Config_DBType, $ForumAccessQuery);
$ForumGuestView = $ForumAccessRow['ForumGuestView'];
$ForumMinStatus = $ForumAccessRow['ForumMinStatus'];

//проверка на гостевой доступ форума
if ($_SESSION['UserData']['UserType'] == 'guest' && $ForumGuestView == 'no') {	
	OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
}

//проверка по группе
if (Access_CheckUserGroup ($ForumMinStatus) == FALSE) {
	OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
}

//Обновление инфо о пользователе
$CurAction = 'download_file';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//выбор аттача
$GetAttachSQL = 'SELECT PostFileName AS FileName,
                        PostFileSize AS FileSize,
						PostFileType AS FileType,
						PostFileExt AS FileExt 
				 FROM post_files 
				 WHERE post_files.PostID = \'' . $PostID . '\'';
$GetAttachQuery = DB_Query ($Config_DBType, $GetAttachSQL, $ForumConnection);
if (!$GetAttachQuery) {
    OBB_Main_Log ($GetAttachSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$GetAttachNumRows = DB_NumRows ($Config_DBType, $GetAttachQuery);
if ($GetAttachNumRows < 1) {
    OBB_Main_ShowError ('download_no_attach', 'STANDART', $ForumLang['Errors']);
}

//присвоение
$GetAttachRow = DB_FetchAssoc ($Config_DBType, $GetAttachQuery);
$FileName   = $GetAttachRow['FileName'];
$FileSize   = $GetAttachRow['FileSize'];
$FileType   = $GetAttachRow['FileType'];
$FileExt    = $GetAttachRow['FileExt'];

$TrueFileName  = sha1 ($PostID . $Config_AttachSalt);
$TrueFileName .= '.' . $FileExt;

//проверка на существование файла
$FileExists = file_exists (OBB_ATTACH_DIR . '/forum_' . $ForumID . '/theme_' . $ThemeID . '/' . $TrueFileName);
if (!$FileExists) {
    OBB_Main_ShowError ('download_no_attach_file_exist', 'STANDART', $ForumLang['Errors']);
}

//Увеличение счетчика скачивания файла на 1
$UpdateViewsSQL = 'UPDATE post_files SET PostFileNumViews = PostFileNumViews+1 WHERE PostID = \'' . $PostID . '\'';
$UpdateViewsQuery = DB_Query ($Config_DBType, $UpdateViewsSQL, $ForumConnection);
if (!$UpdateViewsQuery) {
    OBB_Main_Log ($UpdateViewsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//имя файла - проблеммное место
//if (isset ($_SERVER['HTTP_USER_AGENT'])) {//ТЕСТИТЬ
	//if (stristr ($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $FileName = iconv("utf-8", "windows-1251", $FileName);
    //}
//}

//Подача корректных заголовков в браузер
header ('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header ('Cache-Control: None');
header ('Pragma: no-cache'); 
header ('Content-Type: application/octet-stream; charset=utf-8');
header ('Accept-Ranges: bytes');
header ('Content-Length: ' . $FileSize);
header ('Content-Disposition: attachment; filename="' . $FileName . '"');

//вывод содержимого
readfile (OBB_ATTACH_DIR . '/forum_' . $ForumID . '/theme_' . $ThemeID . '/' . $TrueFileName);
exit ();

?>
