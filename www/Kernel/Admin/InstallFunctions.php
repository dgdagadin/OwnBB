<?php

//функция очистки и/или удаления директории
function OBB_Admin_CleanDir ($Directory, $DelCurDir) {
	$CurrentDir = opendir($Directory);    
	while (($DirElement = readdir($CurrentDir))) {
		$FileName = explode ('.', $DirElement);
		$IsHTAccess = ($FileName[0] == '' && $FileName[1] == 'htaccess');
		if (is_file ($Directory . "/" . $DirElement)) {            
			if ((!$DelCurDir && !$IsHTAccess) || $DelCurDir) {
				if (!unlink ($Directory."/".$DirElement)) {
					return (false);
				}
			}
		}
		else if (is_dir ($Directory . "/" . $DirElement) && ($DirElement != ".") && ($DirElement != "..")) {
			if (!OBB_Admin_CleanDir ($Directory . "/" . $DirElement, TRUE)) {
				return (false);
			}
		}
	}
	closedir ($CurrentDir);
	if ($DelCurDir) {
		if (!rmdir ($Directory)) {
			return (false);
		}
	}
	return (TRUE);
}

//функция создания БД-схемы
function OBB_Admin_CreateDBSchema () {
	global $ForumConnection, $Config_DBType;

	$DBSchemaArray = file (OBB_SCHEMA_DIR . '/DBStructure.sql');
	
	$AreTriggers   = false;
	$OneValue      = array ();
	$OneTrigger    = array ();
	$TablesArray   = array ();
	$TriggersArray = array ();

	foreach ($DBSchemaArray as $key=>$value) {
		$value = trim ($value);
		if (substr ($value, 0, 2) <> '--' && $value <> '') {
			if (strtolower ($value) == 'delimiter //') {
				$AreTriggers = TRUE;
				continue;
			}

			if (!$AreTriggers) {
				if (preg_match ('/^insert\s+into\s+users/ui', $value)) {
					$value = str_replace ('{pass}', Main_Crypt ('qwerty'), $value);
					$value = str_replace ('{time}', time (), $value);
				}
				else if (preg_match ('/^insert\s+into\s+user_activity/ui', $value)) {
					$value = str_replace ('{time}', time (), $value);
					$value = str_replace ('{ip}', getenv ("REMOTE_ADDR"), $value);
				}
				$OneValue[] = $value;
				
				if (preg_match ('/;$/ui', $value)) {
					$OneValueString = implode (' ', $OneValue);
					$TablesArray[] = preg_replace ('/;$/ui', '', $OneValueString);
					$OneValue = array ();
				}
			}
		}
	}

	foreach ($TablesArray as $key=>$val) {
		if (!DB_Query ($Config_DBType, $val, $ForumConnection)) {
			exit ($val . '<br />' . DB_Error ($Config_DBType));
		}
	}
}

//создание форумного БД-кэша
function OBB_Main_CreateForumsDBCache () {
	global $ForumConnection, $Config_DBType;

	$ChartersArray = array();
	$ForumsArray   = array();
	$SQL = 'SELECT CharterID, CharterName FROM charters';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		exit ($SQL . "<br />" . DB_Error ($Config_DBType));
	}
	while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
		$ChartersID   = $Row['CharterID'];
		$ChartersName = $Row['CharterName'];
		$ChartersArray[$ChartersID] = $ChartersName;
	}
	$SQL = 'SELECT forums_list.ForumID AS IdForum,
				   forums_list.ForumName AS NameForum,   
				   forums_list.CharterID AS IDCharter
			FROM forums_list
			LEFT JOIN charters ON forums_list.CharterID = charters.CharterID
			ORDER BY charters.CharterPosition, charters.CharterID, forums_list.ForumID';
	$ForumListQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$ForumListQuery) {
		exit ($SQL . "<br />" . DB_Error ($Config_DBType));
	}
	while ($ForumListRow = DB_FetchAssoc ($Config_DBType, $ForumListQuery)) {
		$IDCharter   = $ForumListRow['IDCharter'];
		$NameCharter = $ChartersArray[$IDCharter];
		$IdForum     = $ForumListRow['IdForum'];
		$NameForum   = $ForumListRow['NameForum'];
		$ForumsArray[$NameCharter][$IdForum] = $NameForum;
	}
	OBB_Cache_PutCacheFile (OBB_CACHE_DIR . '/DBCache/goto_common', serialize($ForumsArray));
}

//создание форумных папок для кэша постов м для аттачей
function OBB_Admin_CreateForumsDirs () {
	global $ForumConnection, $Config_DBType;

	$CacheDirPath = OBB_CACHE_DIR . '/PostsCache';
	$FilesDirPath = OBB_ATTACH_DIR;
	
	$SQL = 'SELECT forums_list.ForumID AS IdForum, forums_list.CharterID AS IDCharter FROM forums_list ORDER BY forums_list.ForumID';
	$ForumListQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$ForumListQuery) {
		exit ($SQL . "<br />" . DB_Error ($Config_DBType));
	}
	while ($ForumListRow = DB_FetchAssoc ($Config_DBType, $ForumListQuery)) {
		$IdForum     = $ForumListRow['IdForum'];
		OBB_Main_CreateDir ($CacheDirPath . '/forum_' . $IdForum);
		OBB_Main_CreateDir ($FilesDirPath . '/forum_' . $IdForum);
	}
}

//функция быстрой установки форума
function OBB_Admin_FastForumInstall () {
	global $Config_DBType, $ForumConnection;

	OBB_Admin_CleanForum ();

	$SQL = 'CREATE DATABASE ' . OBB_CONNECTION_DB;
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		exit ($SQL . "<br />" . DB_Error ($Config_DBType));
	}

	$ForumDatabase = DB_SelectDatabase ($Config_DBType, OBB_CONNECTION_DB, $ForumConnection);
	if (!$ForumDatabase) {
		exit ('Incorrect database!');
	}

	OBB_Admin_CreateDBSchema ();

	OBB_Main_CreateForumsDBCache ();

	OBB_Admin_CreateForumsDirs ();
}

//функция очистки форума
function OBB_Admin_CleanForum () {
	global $Config_DBType, $ForumConnection;

	//1)Удаление БД
	$SQL = 'DROP DATABASE IF EXISTS ' . OBB_CONNECTION_DB;
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		return (array(false, 'bad_db_drop'));
	}

	//2)Очистка папки Files
	// -1)Очистка прикреплений
	$DownloadDir = OBB_ATTACH_DIR;
	if (!OBB_Admin_CleanDir ($DownloadDir, false)) {
		return (array(false, 'bad_attachdir_clean'));
	}
	
	// -2)Очистка файлов активации
	$ActivationsDir = OBB_ACTIVATION_DIR;
	if (!OBB_Admin_CleanDir ($ActivationsDir, false)) {
		return (array(false, 'bad_activationsdir_clean'));
	}

	// -2)Очистка аватаров
	$AvatarsDir = OBB_AVATAR_DIR;
	if (!OBB_Admin_CleanDir ($AvatarsDir, false)) {
		return (array(false, 'bad_avatarsdir_clean'));
	}
	//2)Очистка папки Files - КОНЕЦ

	//3)Очистка папки Logs
	// -1)Очистка логов ошибок
	$ErrorLogsDir = OBB_ERROR_LOG_DIR;
	if (!OBB_Admin_CleanDir ($ErrorLogsDir, false)) {
		return (array(false, 'bad_errorlogsdir_clean'));
	}

	// -2)Очистка логов почты - временно
	$MailLogsDir = OBB_ERROR_MAIL_DIR;
	if (!OBB_Admin_CleanDir ($MailLogsDir, false)) {
		return (array(false, 'bad_maillogsdir_clean'));
	}
	//3)Очистка папки Logs - КОНЕЦ

	//4)Очистка папки Cache
	// -1)Очистка кэша постов
	$PostsCacheDir = OBB_CACHE_DIR . '/PostsCache';
	if (!OBB_Admin_CleanDir ($PostsCacheDir, false)) {
		return (array(false, 'bad_postscachedir_clean'));
	}

	// -2)Очистка кэша БД
	$DBCacheDir = OBB_CACHE_DIR . '/DBCache';
	if (!OBB_Admin_CleanDir ($DBCacheDir, false)) {
		return (array(false, 'bad_dbcachedir_clean'));
	}
	//4)Очистка папки Cache - КОНЕЦ

	//5)Очистка папки Sessions
	$SessionsDir = OBB_SESSION_DIR;
	if (!OBB_Admin_CleanDir ($SessionsDir, false)) {
		return (array(false, 'bad_sessionsdir_clean'));
	}
	return (array(TRUE, NULL));	
}

?>