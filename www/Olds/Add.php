<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//тема или сообщение или редактирование
$LabelArray = array ('theme','post','edit');
$Label = isset ($_GET['label']) && in_array ($_GET['label'], $LabelArray) ? $_GET['label'] : 'theme';

//проверка бан пользователя (на добавление)
if ($Banned_IDBannedAdd) {
    OBB_Main_ShowError ('common_id_banned_add', 'STANDART', $ForumLang['Errors'], array('time', $Banned_IDBannedTime));
}

//проверка ид форума/темы/сообщения
$DigitValid = 0;
if (!isset ($_GET['id_forum']) || !ctype_digit ($_GET['id_forum'])) {
	$DigitValid++;
}
if ($Label == 'post' || $Label == 'edit') {
	if (!isset ($_GET['id_theme']) || !ctype_digit ($_GET['id_theme'])) {
		$DigitValid++;
	}
	if ($Label == 'edit') {
		if (!isset ($_GET['id_post']) || !ctype_digit ($_GET['id_post'])) {
			$DigitValid++;
		}
	}
}
if ($DigitValid > 0) {	
	OBB_Main_ShowError ('add_wrong_forum_or_theme_or_post', 'STANDART', $ForumLang['Errors']);
}

//проверка на разрешение гостям создания тем/сообщений
if ($_SESSION['UserData']['UserType'] == 'guest') {
	if ($Label == 'edit' || ($Label == 'theme' && OBB_GUEST_ADD_THEMES <> TRUE) || ($Label == 'post' && OBB_GUEST_ADD_POSTS <> TRUE)) {
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
	}
}

//определение всех переменных
$IdForum = intval ($_GET['id_forum']);
$IdTheme = $Label == 'post' || $Label == 'edit' ? intval ($_GET['id_theme']) : '';
$IdPost  = $Label == 'edit' ? intval ($_GET['id_post'])  : '';

//получение св-в форума /и темы/и поста
if ($Label == 'theme') {
	$CheckSQL = 'SELECT ForumName AS FNm, ForumGuestView AS FGuestV, ForumBlock AS FBlock, ForumMinStatus AS FGroups
	             FROM forums_list
				 WHERE ForumID = \'' . $IdForum . '\'';
				 
    $CheckError = 'add_no_forum_exist';
}
else if ($Label == 'post') {
	$CheckSQL = 'SELECT forums_list.ForumName AS FNm, forums_list.ForumGuestView AS FGuestV, forums_list.ForumBlock AS FBlock, forums_list.ForumMinStatus AS FGroups, themes.ThemeName AS TName, themes.ThemeBlock AS TBlock, themes.ThemeAllowSmiles AS T_AllowSmiles
	             FROM themes
				 LEFT JOIN forums_list ON themes.ForumID = forums_list.ForumID
				 WHERE themes.ThemeID = \'' . $IdTheme . '\' AND themes.ForumID = \'' . $IdForum . '\'';
				 
	$CheckError = 'add_no_theme_exist';
}
else if ($Label == 'edit') {
	$CheckSQL = 'SELECT forums_list.ForumName AS FNm, 
	                    forums_list.ForumGuestView AS FGuestV, 
						forums_list.ForumBlock AS FBlock, 
						forums_list.ForumMinStatus AS FGroups, 
						themes.ThemeName AS TName, 
						themes.ThemeBlock AS TBlock, 
                        themes.ThemeAllowSmiles AS T_AllowSmiles,
						posts.UserID AS PUserID, 
						posts.PostDate AS PDateID, 
                        posts.PostText AS PText, 
						posts.PostEditDate AS PEditDate,
                        posts.PostSmilesAllow AS PSmilesAllow, 
						post_files.PostFileName AS PFileName,
						post_files.PostFileSize AS PFileSize,
						post_files.PostFileType AS PFileType,
						post_files.PostFileHeight AS PHeight,
						post_files.PostFileWidth AS PWidth,
						post_files.PostFileNumViews AS PNumViews,
						search_content.SearchPostContent AS PSearch
	             FROM posts
				 LEFT JOIN forums_list ON posts.ForumID = forums_list.ForumID
				 LEFT JOIN themes ON posts.ThemeID = themes.ThemeID
				 LEFT JOIN post_files ON post_files.PostID = posts.PostID 
				 LEFT JOIN search_content ON posts.PostID = search_content.PostID 
				 WHERE posts.ThemeID = \'' . $IdTheme . '\' 
				 AND posts.ForumID = \'' . $IdForum . '\' 
				 AND posts.PostID = \'' . $IdPost . '\'';
				 
	$CheckError = 'add_no_post_exist';
}
$CheckQuery = DB_Query ($Config_DBType, $CheckSQL, $ForumConnection);
if (!$CheckQuery) {
	OBB_Main_Log ($CheckSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
//проверка существования сущности, если такой нет - формирования корректного перенаправления
$IsGoodRequest = DB_NumRows ($Config_DBType, $CheckQuery);
if ($IsGoodRequest < 1) {
	OBB_Main_ShowError ($CheckError, 'STANDART', $ForumLang['Errors']);
}

//присвоение - удобство
$InfoRow = DB_FetchAssoc ($Config_DBType, $CheckQuery);
$ForumGroups = $InfoRow['FGroups'];
$ForumName   = $InfoRow['FNm'];
$GuestView   = $InfoRow['FGuestV'];
$ForumBlock  = $InfoRow['FBlock'];

//нотисы
$ThemeBlock    = '';
$ThemeName     = '';
$PUserID       = '';
$PDateID       = '';
$PFileName     = '';
$PFileSize     = '';
$PSmilesAllow  = '';
$PText         = '';
$PHeight       = '';
$PWidth        = '';
$PEditDate     = '';
$PNumViews     = '';
$PFileType     = '';
$PSearch       = '';
$T_SmilesAllow = '';

if ($Label == 'post' || $Label == 'edit') {
	$ThemeBlock    = $InfoRow['TBlock'];
	$ThemeName     = $InfoRow['TName'];
    $T_SmilesAllow = $InfoRow['T_AllowSmiles'];
	
	if ($Label == 'edit') {
		$PUserID      = $InfoRow['PUserID'];
		$PDateID      = $InfoRow['PDateID'];
		$PFileName    = $InfoRow['PFileName'];
		$PFileSize    = $InfoRow['PFileSize'];
		$PHeight      = $InfoRow['PHeight'];
		$PWidth       = $InfoRow['PWidth'];
        $PSmilesAllow = $InfoRow['PSmilesAllow'];
        $PText        = $InfoRow['PText'];
		$PEditDate    = $InfoRow['PEditDate'];
		$PNumViews    = $InfoRow['PNumViews'];
		$PFileType    = $InfoRow['PFileType'];
		$PSearch      = $InfoRow['PSearch'];
	}   
}

//если форум/тема заблокированы
if ($ForumBlock == 'yes') {
	OBB_Main_ShowError ('add_forum_block', 'STANDART', $ForumLang['Errors']);
}
else if (($Label == 'post' || $Label == 'edit') && $ThemeBlock == 'yes') {
	OBB_Main_ShowError ('add_theme_block', 'STANDART', $ForumLang['Errors']);
}	

//доступ конкретного форума гостю
if ($_SESSION['UserData']['UserType'] == 'guest') {
	if ($GuestView == 'no') {
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
	}
}

//доступ по группе
if (Access_CheckUserGroup ($ForumGroups) == FALSE) {
	OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
}

//если редактирование сообщения, то а)проверка на то, тот ли пользователь его редактирует; б)время ред-я
if ($Label == 'edit') {
    if ($_SESSION['UserData']['UserType'] == 'member') {
        if (($PUserID <> $_SESSION['UserData']['UserID']) || OBB_USER_EDIT_POSTS <> TRUE) {
            OBB_Main_ShowError ('add_bad_editer', 'STANDART', $ForumLang['Errors']);
        }
    }
}

//флаг, если разрешен аттач
if (OBB_COMMON_ADD_ATTACH == TRUE) {
	if ($_SESSION['UserData']['UserType'] == 'member' || 
	   ($_SESSION['UserData']['UserType'] == 'guest' && OBB_GUEST_ADD_ATTACH == TRUE)) {
		$Attach = '1';
	}
	else {
		$Attach = '0';
	}
}
else {
	$Attach = '0';
}

//флаг, если необходима капча
if ($Label <> 'edit' && OBB_ADD_USER_CAPTCHA == TRUE) {
	if ($_SESSION['UserData']['UserType'] == 'member' || ($_SESSION['UserData']['UserType'] == 'guest' && OBB_ADD_GUEST_CAPTCHA == TRUE)) {
		$CaptchaFlag = TRUE;
	}	
}
else {
	$CaptchaFlag = FALSE;
}

//подключение
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');
include (OBB_KERNEL_DIR . '/CacheFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Add_' . $Config_Lang . '.php');

//обновление инфо о пользователе
if ($Label == 'theme') {
	$CurAction = 'add_theme';
}
else {
	$CurAction = $Label == 'post' ? 'add_post' : 'edit_post';	
}
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//массив ощибок
$AddErrorArray = array ();

//если нажата кнопка submit
if (isset ($_POST['add'])) {
	//проверка
	//1)капча
	if ($CaptchaFlag) {
		$CaptchaChech = Defence_CheckCaptcha ('CaptchaImg');
		if (is_string ($CaptchaChech)) {
			$AddErrorArray[] = $ForumLang['AddErrors'][$CaptchaChech];
		}
	}
	
	//2)антифлуд
	
	//иконка темы + разрешать ли смайлы
	$ThemeIconArray =  array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11');
	$AllowSmiles    =  isset ($_POST['AllowSmiles']) && $_POST['AllowSmiles']  == TRUE  ? 'yes' : 'no';	
	$ThemeIcon      = !isset ($_POST['ThemeIcon']) || !in_array ($_POST['ThemeIcon'], $ThemeIconArray) ? '0' : intval ($_POST['ThemeIcon']);
	
	//удаление пробелов
	$PostContentVar  = isset ($_POST['PostContent'])  ? trim ($_POST['PostContent'])  : '';
	
	//если гость
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		$GuestLoginVar = isset ($_POST['GuestLogin']) ? trim ($_POST['GuestLogin']) : '';
		$GuestMailVar  = isset ($_POST['GuestMail'])  ? trim ($_POST['GuestMail'])  : '';
		
		//проверка логина
		$CheckGuestLogin = OBB_Defence_CheckLogin ($GuestLoginVar);
		$GuestLoginCorrect = $CheckGuestLogin[0];
		if (!$GuestLoginCorrect) {
			$GuestLoginError = $CheckGuestLogin[1];
			$AddErrorArray[] = $ForumLang['AddErrors'][$GuestLoginError];
		}

		//проверка почты
		$CheckGuestMail = OBB_Defence_CheckMail ($GuestMailVar);
		$GuestMailCorrect = $CheckGuestMail[0];
		if (!$GuestMailCorrect) {
			$GuestMailError = $CheckGuestMail[1];
			$AddErrorArray[] = $ForumLang['AddErrors'][$GuestMailError];
		}
	}
	
	//если добавляем тему
	if ($Label == 'theme') {
		$ThemeNameVar  = isset ($_POST['ThemeName'])  ? trim ($_POST['ThemeName'])  : '';
		
		//имя темы
		if ($ThemeNameVar == '') {
			$AddErrorArray[] = $ForumLang['AddErrors']['EmptyThemeNameLength'];
		}
		else {
			$ThemeNameLength = Main_Strlen ($ThemeNameVar);
			if ($ThemeNameLength < OBB_MIN_THEME_NAME_LENGTH || $ThemeNameLength > OBB_MAX_THEME_NAME_LENGTH) {
				$AddErrorArray[] = $ForumLang['AddErrors']['BadThemeNameLength'];
			}			
			else {		
				$SameThemeSQL = 'SELECT 1 FROM forums_list, themes WHERE forums_list.ForumID=\'' . $IdForum . '\' AND themes.ThemeName=\'' . Defence_EscapeString ($Config_DBType, $ThemeNameVar) . '\'';
				$SameThemeQuery = DB_Query($Config_DBType, $SameThemeSQL, $ForumConnection);
				if (!$SameThemeQuery) {
					OBB_Main_Log ($SameThemeSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				if (DB_NumRows ($Config_DBType, $SameThemeQuery) > 0) {
					$AddErrorArray[] = $ForumLang['AddErrors']['ThemeExists'];
				}
			}
		}		
	}
	
	//сообщение
	if ($PostContentVar == '') {
		$AddErrorArray[] = $ForumLang['AddErrors']['EmptyPostField'];
	}
	else {
		if (Main_Strlen ($PostContentVar) > OBB_MAX_POST_LENGTH) {
			$AddErrorArray[] = $ForumLang['AddErrors']['BadPostLength'];
		}
	}
	
	//5)Проверка прикрепленного файла (если он есть)
	if ($Attach == '1') {
		if (isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '') {		
			$FileDataArray = $_FILES['FileAttach'];
			$AttachObject = new OwnBB_CheckUpload ($FilesArray, OBB_MAX_ATTACH_SIZE, OBB_MAX_IMAGE_WIDTH, OBB_MAX_IMAGE_HEIGHT);
			
			$AttachArray = $AttachObject->FullCheckOfUpload ($FileDataArray);
			$IsTrue = $AttachArray['IsTrue'];
			if ($IsTrue == FALSE) {
				$LangAttach      = $ForumLang['Attach'];
				$FileErrorCode   = $AttachArray['Error'];
				$AddErrorArray[] = $LangAttach[$FileErrorCode];
			}			
		}
	}
	
	//если ошибок не обнаружено
	if (sizeof ($AddErrorArray) == 0) {
		//получение ресурса парсинга
		$bbcode = OBB_BBCode_GetParseStandart ();

		//переменные
		$UserID   = $UpdateUserID   = $_SESSION['UserData']['UserType'] <> 'guest' ? intval ($_SESSION['UserData']['UserID']) : '0';
		$UserName = $UpdateUserName = $_SESSION['UserData']['UserType'] <> 'guest' ? Defence_EscapeString ($Config_DBType, $_SESSION['UserData']['UserName']) : Defence_EscapeString ($Config_DBType, $GuestLoginVar);
		
		//парсинг для поиска
		//смайлы - включаем для их уничтожения
		$bbcode->SetEnableSmileys(TRUE);
		$SearchContentPost = $bbcode->parse ($PostContentVar);
		$SearchContentPost = nl2br ($SearchContentPost);
		$SearchContentPost = OBB_Search_PrepareToSearch ($SearchContentPost);
		//парсинг для поиска - КОНЕЦ
		
		$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
		$Transaction->StartTransaction ();
		
		//{1} Добавление сообщение (и темы) ИЛИ редактирование сообщения
		if ($Label == 'edit') {
			$UpdatePostSQL = 'UPDATE posts 
			                  SET PostText=\'' . Defence_EscapeString ($Config_DBType, $PostContentVar) . '\', 
							      PostSmilesAllow = \'' . $AllowSmiles . '\', 
								  PostEditDate = \'' . time () . '\' 
							  WHERE PostID = \'' . $IdPost . '\' 
							  AND   UserID = \'' . $UserID . '\'';//echo $UpdatePostSQL;
			$UpdatePostQuery = DB_Query($Config_DBType, $UpdatePostSQL, $ForumConnection);
			if (!$UpdatePostQuery) {
				OBB_Main_Log ($UpdatePostSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
			
			$UpdatePostArray = array (
									  'Operation'  => 'u',
								      'FieldArray' => array ('PostText'        => array ('Value'=>$PText, 'Flag'=>'none', 'Type'=>'string'),
												             'PostSmilesAllow' => array ('Value'=>$PSmilesAllow, 'Flag'=>'none', 'Type'=>'string'),
												             'PostEditDate'    => array ('Value'=>$PEditDate, 'Flag'=>'none', 'Type'=>'string')),
									  'WhereArray' => array ('PostID'=>array ('Value'=>$IdPost, 'Type'=>'int')), 
									  'TableName'  => 'posts'
									 );
			$Transaction->PrepareQueryToRollback ($UpdatePostArray);
			
			$ThemeInsertId = $IdTheme;
		}
		else {
			//если добавление темы - соотв. запрос
			if ($Label == 'theme') {
				$ThemeInsertSQL = 'INSERT INTO themes (ThemeName, ThemeDate, ThemeSmile, ThemeBlock, ThemeNumPosts, ThemeNumViews, ThemeNumAttaches, ThemeImportant, ThemeQuiz, ThemeAllowSmiles, ThemeUpDate, UpdateUserID, UserID, UserName, ThemeUpdateUserName, ForumID)
								   VALUES
								   (\'' . Defence_EscapeString ($Config_DBType, $ThemeNameVar) . '\',
									\'' . $Now . '\',
									\'' . $ThemeIcon . '\',
									\'no\',
									\'0\',
									\'0\',
									\'0\',
									\'no\',
									\'no\',
									\'' . $AllowSmiles . '\',
									\'' . $Now . '\',								
									\'' . $UpdateUserID . '\',
									\'' . $UserID . '\',
									\'' . $UserName . '\',
									\'' . $UpdateUserName . '\',
									\'' . $IdForum . '\')';//echo $ThemeInsertSQL;
				$ThemeInsertQuery = DB_Query($Config_DBType, $ThemeInsertSQL, $ForumConnection);
				if (!$ThemeInsertQuery) {
					OBB_Main_Log ($ThemeInsertSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$ThemeInsertId = DB_LastID ($Config_DBType, $ForumConnection);
				
				$ThemeInsertArray = array (
										   'Operation'  => 'd',
										   'FieldArray' => array (),
										   'WhereArray' => array('ThemeID'=>array ('Value'=>$ThemeInsertId, 'Type'=>'int')), 
										   'TableName'  => 'themes'
									      );
				$Transaction->PrepareQueryToRollback ($ThemeInsertArray);
									  
				//создание папкок темы - для прикреплений и кэша
                OBB_Main_CreateDir (OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId);
                OBB_Main_CreateDir (OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $ThemeInsertId);
			}
			else {
				$ThemeInsertId = $IdTheme;			
			}
			
			//добавление сообщения
			$InsertPostSQL = 'INSERT INTO posts (PostText,  PostDate, PostSmilesAllow, UserID, UserName, ForumID, ThemeID)
							  VALUES
							  (\'' . Defence_EscapeString ($Config_DBType, $PostContentVar) . '\',
							   \'' . $Now . '\',
							   \'' . $AllowSmiles . '\',
							   \'' . $UserID . '\',
							   \'' . $UserName . '\',
							   \'' . $IdForum . '\',
							   \'' . $ThemeInsertId . '\')';//echo $InsertPostSQL;
			$InsertPostQuery = DB_Query($Config_DBType, $InsertPostSQL, $ForumConnection);
			if (!$InsertPostQuery) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ($InsertPostSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
			$LastPostId = DB_LastID ($Config_DBType, $ForumConnection);
			
			$InsertPostArray = array (
			                          'Operation'  => 'd',
									  'FieldArray' => array (),
									  'WhereArray' => array('PostID'=>array ('Value'=>$LastPostId, 'Type'=>'int')), 
									  'TableName'  => 'posts'
								     );
			$Transaction->PrepareQueryToRollback ($InsertPostArray);
		}
		
		//идентификатор поста
		if ($Label == 'edit') {
			$PostInsertID = $IdPost;
		}
		else {
			$PostInsertID = $LastPostId;
		}
		
		//{2}Добавление информации, обработанной для поиска
		if ($Label == 'theme' || $Label == 'post') {
			$SearchSQL = 'INSERT INTO search_content (PostID, SearchPostContent, ForumID) VALUES (\'' . $PostInsertID . '\', \'' . Defence_EscapeString ($Config_DBType, $SearchContentPost) . '\', \'' . $IdForum . '\')';
		}
		else if ($Label == 'edit') {
			$SearchSQL = 'UPDATE search_content SET SearchPostContent = \'' . Defence_EscapeString ($Config_DBType, $SearchContentPost) . '\' WHERE PostID = \'' . $PostInsertID . '\'';
		}
		$SearchQuery = DB_Query($Config_DBType, $SearchSQL, $ForumConnection);
		if (!$SearchQuery) {
			OBB_Main_Log ($SearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		
		if ($Label == 'theme' || $Label == 'post') {
			$SearchPostArray = array (
			                          'Operation'  => 'd',
									  'FieldArray' => array (),
									  'WhereArray' => array('PostID'=>array ('Value'=>$PostInsertID, 'Type'=>'int')), 
									  'TableName'  => 'search_content'
								     );
		}
		else if ($Label == 'edit') {
			$SearchPostArray = array (
			                          'Operation'  => 'u',
									  'FieldArray' => array ('PostText' => array ('Value'=>$PSearch, 'Flag'=>'none', 'Type'=>'string')),
									  'WhereArray' => array('PostID'=>array ('Value'=>$PostInsertID, 'Type'=>'int')), 
									  'TableName'  => 'search_content'
								     );
		}
		$Transaction->PrepareQueryToRollback ($SearchPostArray);
		
		//{3} Статистика пользователя
		//увеличение статистики пользователя
		if ($Label <> 'edit' && $_SESSION['UserData']['UserType'] <> 'guest') {
			$UpdatedFields = array ('UserNumPosts' => array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'string'));
		
			if ($Label == 'theme') {
				$UserThemesIncr = ', UserNumThemes = UserNumThemes + 1 ';
				$UpdatedFields[] = array ('UserNumThemes' => array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'string'));
			}
			else {
				$UserThemesIncr = '';
			}
			
			$UserIncrSQL = 'UPDATE users SET UserNumPosts = UserNumPosts + 1' . $UserThemesIncr . ' WHERE UserID = \'' . $UserID . '\'';//echo $UserIncrSQL;
			$UserIncrQuery = DB_Query ($Config_DBType, $UserIncrSQL, $ForumConnection);
			if (!$UserIncrQuery) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ($UserIncrSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
						
			$UserIncrArray = array (
			                        'Operation'  => 'u',
									'FieldArray' => $UpdatedFields,
									'WhereArray' => array ('UserID'=>array ('Value'=>$UserID, 'Type'=>'int')), 
									'TableName'  => 'users'
								   );
			$Transaction->PrepareQueryToRollback ($UserIncrArray);
		}
		
		//{4} Прикрепление (добавление/удаление/заменение) - ОПИСАНИЕ
		//определение флага, выбран ли аттач
		if ($Attach == '1' && isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '') {
			$GetFile = true;
		}
		else {
			$GetFile = false;
		}
		
		//действие с аттачем (если редактирование)
		$AttachActionArr = array ('Save', 'Delete', 'Replace');
		$AttachAction = $Label == 'edit' && isset ($_POST['AttachAction']) && in_array ($_POST['AttachAction'], $AttachActionArr) ? $_POST['AttachAction'] : 'Save';
		
		//если добавляем/меняем аттач
		if ((($Label == 'edit' && $AttachAction == 'Replace') || $Label <> 'edit') && $GetFile) {
			//присвоение данных файла (удобство)
			$AttachName  = $AttachArray['Name'];
			$AttachType  = $AttachArray['Type'];
			$AttachData  = $AttachArray['Data'];
			$AttachExt   = $AttachArray['Ext'];
			$AttachSize  = $AttachArray['Size'];
			$AttachImage = $AttachArray['Image'];
			if ($AttachImage == '1') {
				$AttachWidth  = $AttachArray['Width'];
				$AttachHeight = $AttachArray['Height'];
			}
			else {
				$AttachWidth = $AttachHeight = 0;
			}
		
			//если редактируем и не сущ. аттача
			if (($Label == 'edit' && OBB_Main_IsEmpty ($PFileName) && OBB_Main_IsEmpty ($PFileSize)) || $Label <> 'edit') {
			
				//ув-е статистики прикреплений тем
				$AttachThemeSQL = 'UPDATE themes SET ThemeNumAttaches=ThemeNumAttaches+1 WHERE ThemeID = \'' . $ThemeInsertId . '\'';
				$AttachThemeQuery = DB_Query($Config_DBType, $AttachThemeSQL, $ForumConnection);
				if (!$AttachThemeQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($AttachThemeSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$AttachThemeArray = array (
										   'Operation'  => 'u',
										   'FieldArray' => array ('ThemeNumAttaches'=>array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'string')),
										   'WhereArray' => array('ThemeID'=>array ('Value'=>$ThemeInsertId, 'Type'=>'int')), 
										   'TableName'  => 'themes'
								          );
				$Transaction->PrepareQueryToRollback ($AttachThemeArray);
			
								
			
				//запрос на описание аттача
				$DescrInsertSQL = 'INSERT INTO post_files
								   (PostID, 
								    PostFileName, 
									PostFileSize, 
									PostFileType, 
									PostFileExt, 
									PostFileWidth, 
									PostFileHeight, 
									PostFileNumViews)
								    VALUES 
								    (\'' . intval ($PostInsertID) . '\',
									 \'' . Defence_EscapeString ($Config_DBType, $AttachName) . '\',									
									 \'' . intval ($AttachSize) . '\',									
									 \'' . Defence_EscapeString ($Config_DBType, $AttachType) . '\',
									 \'' . Defence_EscapeString ($Config_DBType, $AttachExt) . '\',
									 \'' . intval ($AttachWidth) . '\',
									 \'' . intval ($AttachHeight) . '\',
									 \'0\')';//echo $DescrInsertSQL;									
				
				//$Transaction->PrepareQueryToRollback ($DescrInsertArray);
									 
				$DescrInsertQuery = DB_Query ($Config_DBType, $DescrInsertSQL, $ForumConnection);
				if (!$DescrInsertQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($DescrInsertSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				
				$DescrInsertArray = array (
								           'Operation'  => 'd',
									       'FieldArray' => array (),
									       'WhereArray' => array('PostID'=>array ('Value'=>$PostInsertID, 'Type'=>'int')), 
									       'TableName'  => 'post_files'
								          );				
				$Transaction->PrepareQueryToRollback ($DescrInsertArray);
			}
			else {
				$UpdatePostsSQL = 'UPDATE post_files
				                   SET 
								   PostFileName = \'' . Defence_EscapeString ($Config_DBType, $AttachName) . '\',
								   PostFileSize = \'' . intval ($AttachSize) . '\',
								   PostFileType = \'' . Defence_EscapeString ($Config_DBType, $AttachType) . '\',
								   PostFileExt = \'' . Defence_EscapeString ($Config_DBType, $AttachExt) . '\',
								   PostFileWidth = \'' . intval ($AttachWidth) . '\',
								   PostFileHeight = \'' . intval ($AttachHeight) . '\',
								   PostFileNumViews = \'0\'
								   WHERE PostID = \'' . $IdPost . '\'';
				
				$PFileExtArr = explode ('.', $PFileName);
				$PFileExt = array_pop ($PFileExtArr);			    

				$UpdatePostsQuery = DB_Query ($Config_DBType, $UpdatePostsSQL, $ForumConnection);
				if (!$UpdatePostsQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($UpdatePostsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				
				$UpdatePostsArray = array (
										   'Operation'   => 'u',
									       'FieldArray'  => array ('PostFileName'     => array ('Value'=>$PFileName, 'Flag'=>'none', 'Type'=>'string'),
																   'PostFileSize'     => array ('Value'=>$PFileSize, 'Flag'=>'none', 'Type'=>'int'),
																   'PostFileType'     => array ('Value'=>$PFileType, 'Flag'=>'none', 'Type'=>'string'),
																   'PostFileExt'      => array ('Value'=>$PFileExt, 'Flag'=>'none', 'Type'=>'string'),
																   'PostFileWidth'    => array ('Value'=>$PWidth, 'Flag'=>'none', 'Type'=>'int'),
														 		   'PostFileHeight'   => array ('Value'=>$PHeight, 'Flag'=>'none', 'Type'=>'int'),
																   'PostFileNumViews' => array ('Value'=>$PNumViews, 'Flag'=>'none', 'Type'=>'int')),
									        'WhereArray' => array ('PostID'=>array ('Value'=>$IdPost, 'Type'=>'int')), 
									        'TableName'  => 'post_files'
								          );
				$Transaction->PrepareQueryToRollback ($UpdatePostsArray);
				
				//UNLINK делать тут - не самая удачная идея, но так как сейчас на часах
				//0.14 ночи, и я целый день сегодня читал срач между армянами и азерами на ЮТУБ,
				//то пока сделаю тут, потом переделаю :)
				$PFileExtArr = explode ('.', $PFileName);
				$PFileExt = array_pop ($PFileExtArr);
				$DeletingFile = sha1 ($IdPost . $Config_AttachSalt)  . '.' . $PFileExt;
				$FullDelFilePath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/' . $DeletingFile;
				if (file_exists ($FullDelFilePath)) {
					if (!unlink ($FullDelFilePath)) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ('Error while deleting or editing file', OBB_ERROR_LOG_FILE);
					}
				}
			}
			
			//добавление содержимого аттача
			$PostIdUpload = $Label == 'edit' ? $IdPost : $LastPostId;
			$UploadFileName = sha1 ($PostIdUpload . $Config_AttachSalt) . '.' . $AttachExt;				
			if (!move_uploaded_file ($AttachData, OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/' . $UploadFileName)) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ('Error while moving attach data', OBB_ERROR_LOG_FILE);
			}
		}
		else if ($Label == 'edit' && $AttachAction == 'Delete') {
			//удаление описание файла
			if ($PFileName <> NULL && $PFileSize <> NULL) {
				$PFileExtArr = explode ('.', $PFileName);
				$PFileExt = array_pop ($PFileExtArr);
			
				$DelFileSQL = 'DELETE FROM post_files WHERE PostID = \'' . $IdPost . '\'';			

				$DescrInsertQuery = DB_Query ($Config_DBType, $DelFileSQL, $ForumConnection);
				if (!$DescrInsertQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($DelFileSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				
				$DelFileArray = array (
									   'Operation'   => 'i',
									   'FieldArray'  => array ('PostID'           => array ('Value'=>$IdPost, 'Flag'=>'none', 'Type'=>'int'),
															   'PostFileName'     => array ('Value'=>$PFileName, 'Flag'=>'none', 'Type'=>'string'),
															   'PostFileSize'     => array ('Value'=>$PFileSize, 'Flag'=>'none', 'Type'=>'int'),
									                           'PostFileType'     => array ('Value'=>$PFileType, 'Flag'=>'none', 'Type'=>'string'),
									                           'PostFileExt'      => array ('Value'=>$PFileExt, 'Flag'=>'none', 'Type'=>'string'),
									                           'PostFileWidth'    => array ('Value'=>$PWidth, 'Flag'=>'none', 'Type'=>'int'),
									                           'PostFileHeight'   => array ('Value'=>$PHeight, 'Flag'=>'none', 'Type'=>'int'),
											                   'PostFileNumViews' => array ('Value'=>$PNumViews, 'Flag'=>'none', 'Type'=>'int')),
									   'WhereArray'  => array(), 
									   'TableName'   => 'post_files'		 
									  );
				$Transaction->PrepareQueryToRollback ($DelFileArray);
				
				//уменьшение к-ва аттачей в теме
				$DecrAttachesSQL = 'UPDATE themes SET ThemeNumAttaches = ThemeNumAttaches-1 WHERE ThemeID = \'' . $IdTheme . '\'';
				$DecrAttachesQuery = DB_Query ($Config_DBType, $DecrAttachesSQL, $ForumConnection);
				if (!$DecrAttachesQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($DecrAttachesSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$DecrAttachesArray =  array (	
											 'Operation'   => 'u',
				                             'FieldArray'  => array ('ThemeNumAttaches' => array ('Value'=>'', 'Flag'=>'incr', 'Type'=>'int')),
											 'WhereArray'  => array ('ThemeID' => array ('Value'=>$IdTheme, 'Type'=>'int')),
											 'TableName'   => 'themes'
											);
				$Transaction->PrepareQueryToRollback ($DecrAttachesArray);
				
				$UploadFileName = sha1 ($IdPost . $Config_AttachSalt) . '.' . $PFileExt;
				$FullUploadFilePath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/' . $UploadFileName;
				if (file_exists ($FullUploadFilePath)) {
					if (!unlink ($FullUploadFilePath)) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ('Error while deleting attach', OBB_ERROR_LOG_FILE);
					}
				}
			}
		}
        
        //КЭШИРОВАНИЕ ПОСТА
        if (OBB_PUT_MESSAGE_CACHE) {
            //разрешать ли парсить смайлы
            if      ($Label == 'edit' || $Label == 'post') { $CacheAllowSmiles = $T_SmilesAllow == 'yes' && $AllowSmiles == 'yes' ? TRUE : false; }
            else if ($Label == 'theme')                    { $CacheAllowSmiles = $AllowSmiles   == 'yes'                          ? TRUE : false; }
            
            //путь к кэш-файлу
			$PostIdentifier = $Label == 'edit' ? $IdPost : $LastPostId;
            $PathToCache = OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/post_' . $PostIdentifier;
                        
            //парсинг сообщения
            $bbcode->SetEnableSmileys($CacheAllowSmiles);
			$PostHTML = $bbcode->parse ($PostContentVar);
            $PostHTML = nl2br ($PostHTML);
            if (!OBB_Cache_PutCacheFile ($PathToCache, $PostHTML)) {
                $Transaction->RollbackTransaction ();
                OBB_Main_Log ('Error while craeting cache file', OBB_ERROR_LOG_FILE);
            }
        }
        
        //обновление закэшированной сериализованной строки с темами (если добавляется тема)
        if ($Label == 'theme') {
            $KeyValArray = array ($ThemeInsertId, $ThemeNameVar);
			$FilePath = OBB_CACHE_DIR . '/DBCache/goto_forum_' . $IdForum;
            OBB_Cache_PutCacheArray ($FilePath, $KeyValArray);
        }

		//обновление даты захода пользователя на данную тему
		Main_UpdateThemeView ($ThemeInsertId);

		//завершение транзакции
		$Transaction->CommitTransaction ();
		
		//письмо администратору
		if ($Config_Mail['AdminMail'] == '1') {
			$RegMailer      = new OwnBB_Mailer ($Config_Mail);
			$LetterTempPath = OBB_LANGUAGE_DIR . '/HTMLTemplates/';
		
			/* $AdmUserLogin = $RegLogin;
			$AdmUserMail  = $RegMail;
			$AdmUserID    = $LastUserID;
			$AdmUserDate  = Main_ConvertDate (time (), '', $Format = 'd.m.y, H:i');
			$AdmTheme     = $ForumLang['RegMailThemeAdmin'] . ' "' . $ForumName . '"';
			$AdminName    = $Config_Mail['FromName'];
			$AdminMail    = $Config_Mail['FromMail'];
			
			$AdminLetter = file_get_contents ($LetterTempPath . 'AdminMailReg.html');
			
			$AdminLetter = str_replace ('{username}', $AdmUserLogin, $AdminLetter);
			$AdminLetter = str_replace ('{usermail}', $AdmUserMail, $AdminLetter);
			$AdminLetter = str_replace ('{userid}', $AdmUserID, $AdminLetter);
			$AdminLetter = str_replace ('{userdate}', $AdmUserDate, $AdminLetter); */
			
			#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
			//file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_UserRegLog' . $LastUserID . '.html', $AdminLetter);
		}
		
		//если ошибок нет - перенаправление
		$CommonURL = '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $ThemeInsertId;
		switch ($Label) {
			case "theme":
			{
				$Redirect = $CommonURL;
				$Message  = 'add_theme';
				break;
			}
			
			case "post":
			{
				$Redirect = $CommonURL . '&last_post=' . $LastPostId . '#' . $LastPostId;
				$Message  = 'add_post';
				break;
			}
			
			case "edit":
			{
				$Redirect = $CommonURL . '&last_post=' . $IdPost . '#' . $IdPost;
				$Message  = 'edit_post';
				break;
			}
		}
		$_SESSION['Redirect'] = $Redirect;
		$_SESSION['Message']  = $Message;
		$URL = '?action=message';
		exit;//OBB_Main_Redirect ($URL);
	}
	
}
else {
    //избавление от нотисов	 
	$GuestLoginVar  = '';
	$GuestMailVar   = '';
	$ThemeNameVar   = '';
    $PostContentVar = '';
    $AllowSmiles    = '';
    
    if (isset ($_GET['id_quote'])) {
        if (!ctype_digit ($_GET['id_quote'])) {
            $IdQuote = '';
        }
        else {
            $IdQuote = intval ($_GET['id_quote']);
        }
    }
	else {
		$IdQuote = '';
	}
    
    if ($Label == 'edit') {
        $PostContentVar = $PText;
        $AllowSmiles    = $PSmilesAllow;
    }
    else if ($Label == 'post' && $IdQuote <> '') {
        $QuoteSQL = 'SELECT PostText AS PText, UserName AS PUser FROM posts WHERE PostID = \'' . $IdQuote . '\' AND ForumID = \'' . $IdForum . '\' AND ThemeID = \'' . $IdTheme . '\'';
        $QuoteQuery = DB_Query ($Config_DBType, $QuoteSQL, $ForumConnection);
        if (!$QuoteQuery) {
            OBB_Main_Log ($QuoteSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
        }
        $QuotePostExists = DB_NumRows ($Config_DBType, $QuoteQuery) == 1 ? TRUE : FALSE;
        if ($QuotePostExists) {
            $QuoteText = DB_Result ($Config_DBType, $QuoteQuery, 0, 'PText');
            $QuoteUser = DB_Result ($Config_DBType, $QuoteQuery, 0, 'PUser');
            $PostContentVar = '[QUOTE="' . $QuoteUser . '"] ' . $QuoteText . ' [/QUOTE]';
        }        
    }   
}

//старт
$MainOutput = '';
$NavTrabslate = $ForumLang['Navig'];

//массив гл. навигации
$NavigArray = array (array ($SelfName, $ForumLang['MainPage']), array($SelfName . '?action=forumview&id_forum=' . $IdForum, $ForumName));
if ($Label == 'post' || $Label == 'edit') {
	$ThemeHref = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
	$NavigArray[] = array ($ThemeHref, $ThemeName);
}
if ($Label == 'theme') {
	$ActionString = $ForumLang['Add'] . ' ' . $ForumLang['AddTheme'];
}
else if ($Label == 'post') {
	$ActionString = $ForumLang['Add'] . ' ' . $ForumLang['AddPost'];
}
else if ($Label == 'edit') {
	$ActionString = $ForumLang['AddEdit'] . ' ' . $ForumLang['AddPost'];
}
$NavigArray[] = array ('', $ActionString);

//верх
$MainOutput .= Echo_PrintHead ($IsJS='1', $NavTrabslate) . '<div class="MainNavigDiv">' . Echo_Navigation ($NavigArray, $Config_NavigDeleter) . '</div>';
$MainOutput .= '<div><b>' . $ActionString . '</b></div>';

//гостевые поля ввода
if ($_SESSION['UserData']['UserType'] == 'guest') {
	$GuestLogin = '<div>' . $ForumLang['AddGuestName'] . '<br /><input type="text" name="GuestLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="' . Defence_HTMLSpecials ($GuestLoginVar) . '"></div><br />';
	$GuestMail  = '<div>' . $ForumLang['AddGuestMail'] . '<br /><input type="text" name="GuestMail" maxlength="'  . OBB_MAX_MAIL_LENGTH  . '" value="' . Defence_HTMLSpecials ($GuestMailVar) . '"></div><br />';
}
else {
	$GuestLogin = $GuestMail = '';
}

//имя темы
if ($Label == 'theme') {
	$ThemeName = '<div>' . $ForumLang['AddThemeName'] . '<br /><input type="text" name="ThemeName" value="' . Defence_HTMLSpecials ($ThemeNameVar) . '"></div><br />';
}
else {
	$ThemeName = '';
}

//урл
$ThemeIDUrl = $Label == 'post' || $Label == 'edit' ? '&id_theme=' . $IdTheme : '';
$PostIDUrl  = $Label == 'edit'                     ? '&id_post='  . $IdPost  : '';
$ActionURL  = $SelfName . '?action=add&label=' . $Label . '&id_forum=' . $IdForum . $ThemeIDUrl . $PostIDUrl;

//вывод ошибок (если есть)
if (isset ($AddErrorArray) && sizeof ($AddErrorArray) > 0) {		
	$ErrorBlock = Echo_DisplayUserErrors ($AddErrorArray, $ForumLang['AddErrors']['ErrorBlockTitle']);
}
else {
	$ErrorBlock = '';
}
$MainOutput .= $ErrorBlock;

//форма добавления - начало
$Multipart   = $Attach == '1' ? ' enctype="multipart/form-data"' : '';
$MainOutput .= '<form action="' . $ActionURL . '" method="POST"' . $Multipart . '>';

//основное
$MainOutput .= '<fieldset style="width:80%;"><legend>' . $ForumLang['AddMainData'] . '</legend>';
$MainOutput .= $GuestLogin . $GuestMail . $ThemeName;
$MainOutput .= '<div>' . $ForumLang['AddMessage'] . '<br><textarea name="PostContent" cols="25" rows="10">' . Defence_HTMLSpecials ($PostContentVar) . '</textarea></div>';
$MainOutput .= '</fieldset>';

//прикрепление
if ($Attach == '1') {//$ForumLang['AddAttachNewDescr']
	$ExtenArray = array ();
	foreach ($FilesArray as $FilesKey=>$FilesValue) {
		$Extension = $FilesValue['extension'];
		$ExtenArray[] = $Extension;
	}

    //форма для добавления/изменения файла    
    if ($Label == 'edit') {
        if (!OBB_Main_IsEmpty ($PFileName) && !OBB_Main_IsEmpty ($PFileSize)) {		
			if ((int)$PWidth > 0 && (int)$PHeight > 0) {
				$IconPart = 'img';
				$Gabarits = ',&nbsp;(' . $PWidth . '<b>x</b>' . $PHeight . ')';
			}
			else {
				$FileNameArr = explode ('.', $PFileName);
				$AttachExt   = array_pop ($FileNameArr);
				$IconPart    = $AttachExt;
				$Gabarits    = '';
			}
			$AttachIcon = '<img src="' . OBB_IMAGE_DIR . '/' . $IconPart . '.png" border="0" />';
			$AttachName = Defence_HTMLSpecials ($PFileName);
			
			$SizeArr   = Main_ShowSize ($PFileSize);
			$FileUnit  = $SizeArr['Unit'];
			$FileSize  = $SizeArr['Size'];
			$AttachSize = ',&nbsp;' . $FileSize . ' ' . $ForumLang['AddAttach'.$FileUnit];
			
            $ExistingAttach = '<div>
                                   <span>' . $ForumLang['AddAttachCur'] . '</span>
								   <br />
								   <span>' . $AttachIcon . '</span><span>' . $AttachName . '</span><span>' . $AttachSize . '</span>' . $Gabarits . '
                               </div>
                               <br />';
                               
            $AddAttachDescr  = '<div>
                                    <span>' . $ForumLang['AddAttachEdit'] . '</span>
                                </div>                                
                                <div>
                                    <span><input type="radio" checked name="AttachAction" value="Save"></span>
                                    <span>' . $ForumLang['AddAttachSave'] . '</span>
                                    <br />
                                    <span><input type="radio" name="AttachAction" value="Delete"></span>
                                    <span>' . $ForumLang['AddAttachDelete'] . '</span>
                                    <br />
                                    <span><input type="radio" name="AttachAction" value="Replace"></span>
                                    <span>' . $ForumLang['AddAttachReplace'] . '</span>                                    
                                </div>
                                <br />
                                <div>                                    
                                    <input type="file" name="FileAttach">
                                </div>
                                <br />';
        }
        else {
            $ExistingAttach = '';
            $AddAttachDescr = '<div>
                                   <span>' . $ForumLang['AddAttachAdd'] . '</span>
                               </div>
                               <br />
                               <div>
                                   <input type="file" name="FileAttach">
								   <input type="hidden" name="AttachAction" value="Replace">
                               </div>';           
        }
		
		//объединение
		$AttachFile = $ExistingAttach . $AddAttachDescr;
    }
    else {
        $AttachFile = '<div>
                           <span>' . $ForumLang['AddAttachAdd'] . '</span>
                       </div>
                       <br />
                       <div>
                           <input type="file" name="FileAttach">
                       </div>';
    }
    
    //допустимые разрешения
    $ExstensionString = '<div>
                             <span>' . $ForumLang['AddAttachAllowed'] . ': ' . implode (', ', $ExtenArray) . '</span>
                        </div>';
    
    //блок добавления/редактирвоания прикрепления
    $MainOutput .= '<fieldset style="width:80%;">
						<legend>' . $ForumLang['AddAttach'] . '</legend>
                        ' . $AttachFile . '
                        <br />
                        ' . $ExstensionString . '
					</fieldset>';
}

//иконка темы				
if ($Label == 'theme') {
	$ThemeIcon  = isset ($_POST['ThemeIcon']) && ctype_digit ($_POST['ThemeIcon']) ? intval ($_POST['ThemeIcon']) : '0';
	$ThemeIcons = '';
		
	for ($i = 0; $i <= 11; $i++) {
		$CheckedIcon = (int) $ThemeIcon == $i ? ' checked' : '';
		$ThemeIcons .= '<input' . $CheckedIcon . ' type="radio" name="ThemeIcon" value="' . $i . '">';
		if ($i == 0) {
			$ThemeIcons .= $ForumLang['AddNoIcon'];
		}
		else {
			$ThemeIcons .= '<img src="' . OBB_IMAGE_DIR . '/ThemeIcons/' . $i . '.gif" border="0" />';
		}
		$ThemeIcons .= '&nbsp;';
	}

	$MainOutput .= '<fieldset style="width:80%;">
						<legend>' . $ForumLang['AddThemeIcon'] . '</legend>
						<div>'
							. $ThemeIcons . '
						</div>
	                </fieldset>';
}

//смайлы - разрешено ли
if ($AllowSmiles == 'yes' || $AllowSmiles == '') {
	$CheckedAllowSm = 'checked ';
}
else {
	$CheckedAllowSm = '';
}
$MainOutput .= '<fieldset style="width:80%;">
					<legend>'
						. $ForumLang['AddAOptions'] . '
					</legend>
					<div>
						<input type="checkbox" ' . $CheckedAllowSm . 'name="AllowSmiles">'
						. $ForumLang['AddAllowSmiles'] . '
					</div>
                </fieldset>';

if ($CaptchaFlag) {
	$CaptchaTitle = $ForumLang['CaptchaTitle'];
	$EnterCaptcha = $ForumLang['EnterCaptcha'];
	$MainOutput .= Echo_CaptchaBlock ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha']);
}

//submit
$SubmitButton = $Label == 'edit' ? $ForumLang['AddEdit'] : $ForumLang['AddSubmit'];
$MainOutput .= '<br>
                <div>
					<input type="hidden" name="add" value="1">
					<input type="submit" name="Adding"   value="' . $SubmitButton . '">
					<!--<input type="submit" name="ReAdding" value="' . $ForumLang['AddReAdd']  . '">-->
				</div>';

//конец формы
$MainOutput .= '</form>';
				
//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

echo microtime () - $a;
?>