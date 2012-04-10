<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//вложения
include (OBB_KERNEL_DIR . '/OwnBB.RSSGenerator.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/RSS_' . $Config_Lang . '.php');

//Функция обновления онлайн-информации о пользователе/госте
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'rss_page');

//получение объекта парсера
$bbcode = OBB_BBCode_GetParseRSS ();

//текст замены ссылок
$ReplaceText = '<font color="red">' . $ForumLang['RSSNoURLAccess'] . '</font>';

//построение основного массива
//  -определение
$ItemsArray = array ();

//если не указан форум, делаем стандартный RSS-показ - первые 15 постов
if (!isset ($_GET['id_forum'])) {
	//Проверка на доступ к главной странице
	if ($_SESSION['UserData']['UserType'] <> 'admin'&& !$UserGroups_Permissions['VisitIndex']) {
		$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
		OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
	}

	//выбор строки форума, к которым разрешен доступ
	//ПРИМЕЧАНИЕ: сейчас форумы берутся из БД, впоследствии - будут браться из кэша
	if ($_SESSION['UserData']['UserType'] <> 'admin') {
		$AllowedForumsArray = array ();
		$SQL = 'SELECT ForumID, ForumMinStatus AS ForumGroups, ForumGuestView FROM forums_list';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
			$ForumID     = $Row['ForumID'];
			$ForumGroups = $Row['ForumGroups'];
			$ForumGuest  = $Row['ForumGuestView'];

			$AllowedForum = TRUE;
			if (($_SESSION['UserData']['UserType'] == 'guest' && $ForumGuest == 'no') || (!Access_CheckUserGroup ($ForumGroups))) {
				$AllowedForum = false;
			}

			if ($AllowedForum) {
				$AllowedForumsArray[] = $ForumID;
			}
		}
		if (sizeof ($AllowedForumsArray) > 0) {
			$AllowedForumsString = implode (',', $AllowedForumsArray);
		}
		else {
			$AllowedForumsString = "''";
		}
		$AllowedForumsString = ' WHERE forums_list.ForumID IN (' . $AllowedForumsString . ') ';
	}
	else {
		$AllowedForumsString = '';
	}

	//построение конф. массива
	$ConfigRSS = OBB_RSS_SetConfig ($ForumLang['RSSDescription'], false, $ForumName='');

	//  -достаем из базы    
	$RSSDataSQL = 'SELECT posts.PostID AS PID,
						posts.PostText AS PText,
						posts.PostDate AS PDate,
						posts.UserID AS PUID,
						posts.UserName AS PUserName,
						posts.ForumID AS FID,
						posts.ThemeID AS TID,
						post_files.PostFileName AS PFileName,
						post_files.PostFileSize AS PFileSize,
						forums_list.ForumName AS FName,
						themes.ThemeName AS TName
				   FROM posts
				   LEFT JOIN themes ON posts.ThemeID = themes.ThemeID
				   LEFT JOIN forums_list ON posts.ForumID = forums_list.ForumID
				   LEFT JOIN post_files ON posts.PostID = post_files.PostID
				   ' . $AllowedForumsString . '
				   ORDER BY posts.PostDate DESC 
				   LIMIT 0, 15';
	$RSSDataQuery = DB_Query ($Config_DBType, $RSSDataSQL, $ForumConnection);
	if (!$RSSDataQuery) {
		OBB_Main_Log ($RSSDataSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	while ($RSSDataRow = DB_FetchAssoc ($Config_DBType, $RSSDataQuery)) {
		//присвоение
		$FID       = $RSSDataRow['FID'];
		$TID       = $RSSDataRow['TID'];
		$PID       = $RSSDataRow['PID'];
		$FName     = $RSSDataRow['FName'];
		$PDate     = $RSSDataRow['PDate'];
		$PUserID   = $RSSDataRow['PUID'];
		$PUserName = $RSSDataRow['PUserName'];
		$TName     = $RSSDataRow['TName'];
		$PText     = $RSSDataRow['PText'];
		$PFileName = $RSSDataRow['PFileName'];
		$PFileSize = $RSSDataRow['PFileSize'];

		//переопределение
		$FID  = intval ($FID);
		$TID  = intval ($TID);
		$PID  = intval ($PID);
		$PUID = intval ($PUserID);        

		//title
		$ItemTitle = $ForumLang['RSSFromTheme'] . ': "' . Defence_HTMLSpecials ($TName) . '"';

		//link
		$ItemLink = Defence_HTMLSpecials ('http://' . $HostName . $SelfName . '?action=themeview&id_forum=' . $FID . '&id_theme=' . $TID . '&last_post=' . $PID . '#pid' . $PID);

		//date
		$ItemDate = $PDate;

		//description
		//  -форум, в котором находится пост
		$DescrForum  = '<strong>' . $ForumLang['RSSFromForum']  . ':&nbsp;</strong><font color="green">' . Defence_HTMLSpecials ($FName) . '</font><br />';

		//  -автор сообщения
		$UserName = Defence_HTMLSpecials ($PUserName);
		$AuthorLink = $UserName;
		$DescrAuthor = '<strong>' . $ForumLang['RSSAuthor'] . ':&nbsp;</strong>' . $AuthorLink;

		//  -ссылка на прикрепление
		if (!is_null ($PFileName) && !is_null ($PFileSize)) {
			$SizeArr = Main_ShowSize ($PFileSize);
			$Size = $SizeArr['Size'];
			$Unit = $SizeArr['Unit'];
			if ($_SESSION['UserData']['UserType'] == 'admin' || $UserGroups_Permissions['AttachesDownload']) {
				$AttachLink = '<a href="' . $SelfName . '?action=download&id_forum=' . $FID . '&id_theme=' . $TID . '&id_post=' . $PID . '""><font decoration="none" color="green">' . Defence_HTMLSpecials ($PFileName) . '</font></a>';
			}
			else {
				$AttachLink = '<font decoration="none" color="green">' . Defence_HTMLSpecials ($PFileName) . '</font>';
			}
			$DescrAttach = '<br /><b>' . $ForumLang['RSSAttach'] . ':</b>&nbsp;' . $AttachLink . '&nbsp; (' . $Size . ' ' . $ForumLang['RSS'.$Unit] . ')';

		}
		else {
			$DescrAttach = '';
		}

		//  -распарсенный текст
		$PText = $bbcode->parse($PText);
		$PText = nl2br ($PText);
		if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['LinksAllowed']) {
			$PText = OBB_Main_DisallowURLs ($PText, $ReplaceText);
		}

		//  -объединение опсиания
		$ItemDescr = $DescrForum . $DescrAuthor . '<br />-------------<br />' . $PText . $DescrAttach . '<br /><br /><br /><br />';

		$OneItem = array ('ItemTitle' => $ItemTitle,
						  'ItemLink'  => $ItemLink,
						  'ItemDate'  => $ItemDate,
						  'ItemDescr' => $ItemDescr);

		$ItemsArray[] = $OneItem;
	}
}
//выводим последние n записей указанного форума
else {
	//гостевой доступ
	if ($_SESSION['UserData']['UserType'] <> 'admin'&& !$UserGroups_Permissions['VisitThemes']) {
		$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
		OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
	}

	//проверка
	if (!ctype_digit ($_GET['id_forum'])) {
		OBB_Main_ShowError ('rss_wrong_forum', 'STANDART', $ForumLang['Errors']);
	}    
	$IdForum = intval ($_GET['id_forum']);

	//если форум = 0, редирект на гл. стр.
	if ($IdForum == (int)0) {
		OBB_Main_Redirect ();
	}

	//инфо о форуме
	$SQLForumInfo = 'SELECT ForumName, ForumGuestView, ForumMinStatus
					FROM forums_list
					WHERE ForumID = \'' . $IdForum . '\'';
	$ForumInfoQuery = DB_Query ($Config_DBType, $SQLForumInfo, $ForumConnection);
	if (!$ForumInfoQuery) {
		OBB_Main_Log ($SQLForumInfo . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	//если такого форума не существуeт
	$ForumExists = DB_NumRows ($Config_DBType, $ForumInfoQuery);
	if ($ForumExists == 0) {
		OBB_Main_ShowError ('rss_no_forum', 'STANDART', $ForumLang['Errors']);
	}

	//данные о форуме    
	$ForumName      = DB_Result ($Config_DBType, $ForumInfoQuery, 0, 'ForumName');
	$ForumGuestView = DB_Result ($Config_DBType, $ForumInfoQuery, 0, 'ForumGuestView');
	$ForumMinStatus = DB_Result ($Config_DBType, $ForumInfoQuery, 0, 'ForumMinStatus');

	//гостевой доступ ДАННОГО форума
	if ($_SESSION['UserData']['UserType'] == 'guest' && $ForumGuestView == 'no') {	
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);	
	}

	//доступ по группе
	if (Access_CheckUserGroup ($ForumMinStatus) == FALSE) {
		OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
	}

	/*если ошибок нет*/
	//  -построение конф. массива
	$ConfigRSS = OBB_RSS_SetConfig ($ForumLang['RSSDescriptionOneForum'], $IdForum, $ForumName);
	
	//  -запрос к БД
	$SQL = 'SELECT posts.PostID AS PID, 
				   posts.PostText AS PText,
				   posts.PostDate AS PDate,
				   posts.UserID AS PUserID,
				   posts.UserName AS PUserName,
				   posts.ThemeID AS PThemeID,
				   post_files.PostFileName AS PFileName,
				   post_files.PostFileSize AS PFileSize,
				   themes.ThemeName AS PThemeName
			FROM posts
			LEFT JOIN post_files ON post_files.PostID = posts.PostID
			LEFT JOIN themes ON themes.ThemeID = posts.ThemeID
			WHERE posts.ForumID = \'' . $IdForum . '\'
			ORDER BY posts.PostDate DESC 
			LIMIT 0, 15';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
		//инициализация
		$RSSPostID    = $Row['PID'];
		$RSSPostText  = $Row['PText'];
		$RSSPostDate  = $Row['PDate'];
		$RSSUserID    = $Row['PUserID'];
		$RSSUserName  = $Row['PUserName'];
		$RSSThemeID   = $Row['PThemeID'];
		$RSSFileName  = $Row['PFileName'];
		$RSSFileSize  = $Row['PFileSize'];
		$RSSThemeName = $Row['PThemeName'];

		//intval
		$RSSPostID  = intval ($RSSPostID);
		$RSSUserID  = intval ($RSSUserID);
		$RSSThemeID = intval ($RSSThemeID);

		//Элементы
		//  -title
		$ItemTitle = $ForumLang['RSSTheme'] . ': "' . Defence_HTMLSpecials ($RSSThemeName) . '"';

		//  -link -guid
		$ItemLink = Defence_HTMLSpecials ('http://' . $HostName . $SelfName . '?action=themeview&id_forum=' . intval ($IdForum) . '&id_theme=' . $RSSThemeID . '&last_post=' . $RSSPostID . '#pid' . $RSSPostID);

		//  -date
		$ItemDate = $RSSPostDate;

		//  -description
		//  -1.Автор        
		$AuthorLink = Defence_HTMLSpecials ($RSSUserName);
		$DescrAuthor = '<strong>' . $ForumLang['RSSAuthor'] . ':</strong>&nbsp;' . $AuthorLink;

		//  -2.Прикрепление
		if (!OBB_Main_IsEmpty ($RSSFileName) && !OBB_Main_IsEmpty ($RSSFileSize)) {
			$SizeArr = Main_ShowSize ($RSSFileSize);
			$Size = $SizeArr['Size'];
			$Unit = $SizeArr['Unit'];
			if ($_SESSION['UserData']['UserType'] == 'admin' || $UserGroups_Permissions['AttachesDownload']) {
				$AttachLink = '<a href="' . $SelfName . '?action=download&id_forum=' . $IdForum . '&id_theme=' . $RSSThemeID . '&id_post=' . $RSSPostID . '""><font decoration="none" color="green">' . Defence_HTMLSpecials ($RSSFileName) . '</font></a>';
			}
			else {
				$AttachLink = '<font decoration="none" color="green">' . Defence_HTMLSpecials ($RSSFileName) . '</font>';
			}
			$DescrAttach = '<br /><strong>' . $ForumLang['RSSAttach'] . ':&nbsp;</strong>' . $AttachLink . '&nbsp; (' . $Size . ' ' . $ForumLang['RSS'.$Unit] . ')';            
		}
		else {
			$DescrAttach = '';
		}

		//  -3.Текст сообщения (распарсенный)
		$PText = $bbcode->parse($RSSPostText);
		$PText = nl2br ($PText);
		if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['LinksAllowed']) {
			$PText = OBB_Main_DisallowURLs ($PText, $ReplaceText);
		}

		$ItemDescr = $DescrAuthor . '<br />-------------<br />' . $PText . $DescrAttach . '<br /><br /><br /><br />';

		//массив одной записи
		$OneItem = array ('ItemTitle' => $ItemTitle,
						  'ItemLink'  => $ItemLink,
						  'ItemDate'  => $ItemDate,
						  'ItemDescr' => $ItemDescr);

		$ItemsArray[] = $OneItem;
	}
}

//генерация RSS
$RSSObject = new OwnBB_RSSGenerator ($ConfigRSS);

Main_ShowStandartHeader ();	
$RSSObject->GenerateRSS ($ItemsArray);
exit ();

?>