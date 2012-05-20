<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//подключение
include (OBB_KERNEL_DIR . '/Service.php');
//include (OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.SendMail.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');
include (OBB_KERNEL_DIR . '/CacheFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Add_' . $Config_Lang . '.php');

//проверка бан пользователя (на добавление, редактирование, удаление)
if ($Banned_IDBannedAdd) {
	OBB_Main_ShowError ('common_id_banned_add', 'STANDART', $ForumLang['Errors'], array('time', $Banned_IDBannedTime));
}

//провекра идентификатора форума
if (!isset ($_GET['id_forum']) || !ctype_digit ($_GET['id_forum'])) {
	OBB_Main_ShowError ('themes_wrong_forum', 'STANDART', $ForumLang['Errors']);
}
$IdForum = intval ($_GET['id_forum']);

//Проверка ФОРУМА
//  --получение свойств форума
$SQL = 'SELECT ForumName AS FNm,
			ForumGuestView AS FGuestV,
			ForumBlock AS FBlock,
			ForumMinStatus AS FGroups
		FROM forums_list
		WHERE ForumID = \'' . $IdForum . '\'';
$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
if (!$Query) {
	OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//  --проверка на существование
if (DB_NumRows ($Config_DBType, $Query) < 1) {
	OBB_Main_ShowError ('add_no_forum_exist', 'STANDART', $ForumLang['Errors']);
}

//  --присвоение
$InfoRow = DB_FetchAssoc ($Config_DBType, $Query);
$ForumGroups = $InfoRow['FGroups'];
$ForumName   = $InfoRow['FNm'];
$GuestView   = $InfoRow['FGuestV'];
$ForumBlock  = $InfoRow['FBlock'];

//  --проверка на гостевой доступ форума
if ($_SESSION['UserData']['UserType'] == 'guest' && $GuestView <> 'yes') {
	OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
}

//  --проверка на доступ по группе
if ($_SESSION['UserData']['UserType'] <> 'admin' && !Access_CheckUserGroup ($ForumGroups)) {
	OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
}
//Проверка ФОРУМА - КОНЕЦ

//определение главного действия скрипта
$LabelArray = array ('add', 'edit', 'del');
$Label = isset ($_GET['label']) && in_array ($_GET['label'], $LabelArray) ? $_GET['label'] : 'add';

//общие операции для редактирования и удаления
if ($Label == 'edit' || $Label == 'del') {
	//гостям выполнять удаление или редактирование
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
	}

	//идентификаторы
	if (
		!isset ($_GET['id_theme'])       ||
		!ctype_digit ($_GET['id_theme']) ||
		!isset ($_GET['id_post'])        ||
		!ctype_digit ($_GET['id_post'])
	) {
		OBB_Main_ShowError ('add_wrong_theme_or_post', 'STANDART', $ForumLang['Errors']);
	}
	$IdTheme = intval ($_GET['id_theme']);
	$IdPost  = intval ($_GET['id_post']);
}

//главная переменная вывода
$MainOutput = '';

//массив гл. навигации
$NavigArray = array (array ($SelfName, $ForumLang['MainPage']), array($SelfName . '?action=forumview&id_forum=' . $IdForum, $ForumName));

//массив ощибок
$AddErrorArray = array ();

//флаг, если разрешен аттач
$Attach = TRUE;
if ($_SESSION['UserData']['UserType'] <> 'admin') {
	//  --если не разрешено в общем
	if (!OBB_ALLOW_ATTACHES) {
		$Attach = false;
	}
	//  --если не разрешено по группе
	if (!$UserGroups_Permissions['AttachesAdd']) {
		$Attach = false;
	}
}
$Multipart = $Attach ? ' enctype="multipart/form-data"' : '';
$ExtenArray = array ();
foreach ($FilesArray as $FilesKey=>$FilesValue) {
	$Extension = $FilesValue['extension'];
	$ExtenArray[] = $Extension;
}
$ExstensionString = '<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowed'] . '</span>:&nbsp;<span style="color:#444;">' . implode (', ', $ExtenArray) . '</span>';
//флаг, если разрешен аттач - КОНЕЦ

//флаг, нужно ли выводить капчу
$CaptchaFlag = false;
if ($_SESSION['UserData']['UserType'] <> 'admin' && $Label <> 'del') {
	//  --капча на добавление сообщения или темы
	if ($Label == 'add') {
		if (isset ($_GET['id_theme'])) {
			$CaptchaPermissions = 'CaptchaAddPost';
		}
		else {
			$CaptchaPermissions = 'CaptchaAddTheme';
		}
	}
	//  --капча на редактирование сообщения
	else if ($Label == 'edit') {
		$CaptchaPermissions = 'CaptchaEditPost';
	}
	//выводить ли капчу
	if (OBB_CAPTCHA && $UserGroups_Permissions[$CaptchaPermissions]) {
		$CaptchaFlag = TRUE;
	}
}
//флаг, разрешена ли капча - КОНЕЦ

//если пользователь не гость - получаем инфу о нем
//ПРИМЕЧАНИЕ - в данный момент это не нужно
$MailUserSend = false;
if ($_SESSION['UserData']['UserType'] <> 'guest') {
	$SQL = 'SELECT UserAdminMail, UserMail FROM users WHERE UserID = ' . intval ($_SESSION['UserData']['UserID']);
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	if (DB_NumRows ($Config_DBType, $Query) > 0) {
		$CurUserMail 	  = DB_Result ($Config_DBType, $Query, 0, 'UserMail');
		$CurUserAdminSend = DB_Result ($Config_DBType, $Query, 0, 'UserAdminMail');
		if ($CurUserAdminSend == 'yes') {
			$MailUserSend = TRUE;
		}
	}
}

//действия
switch ($Label) {
	case"add":
	default: {
		//проверка, заблокирован ли форум
		if ($ForumBlock == 'yes') {
			OBB_Main_ShowError ('add_forum_block', 'STANDART', $ForumLang['Errors']);
		}

		//добавляем ли сообщение или тему
		$IsPostAdding = isset ($_GET['id_theme']) && ctype_digit ($_GET['id_theme']);

		//проверка, есть ли разрешения для добавления темы/сообщения
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			//флаг вывода сообщения об ошибке
			$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';

			//если добавление темы
			if (!$IsPostAdding) {
				//  --проверка на полный запрет темы
				if (!OBB_ADD_THEMES) {
					OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
				}

				//  --проверка на разрешения группы
				if (!$UserGroups_Permissions['AddThemes']) {
					OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
				}
			}
			//если добавление поста
			else {
				//  --проверка на полный запрет сообщения
				if (!OBB_ADD_POSTS) {
					OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
				}

				//  --проверка на разрешения группы
				if (!$UserGroups_Permissions['AddPosts']) {
					OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
				}
			}
		}

		//если добавление поста - дополнительные проверки
		if ($IsPostAdding) {
			//  --ид темы
			$IdTheme = intval ($_GET['id_theme']);

			//  --запрос
			$SQL = 'SELECT ThemeName AS TName,
						ThemeBlock AS TBlock,
						ThemeAllowSmiles AS T_AllowSmiles
					FROM themes
					WHERE ThemeID = \'' . $IdTheme . '\' AND ForumID = \'' . $IdForum . '\'';
			$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//  --если темы не существует
			if (DB_NumRows ($Config_DBType, $Query) < 1) {
				OBB_Main_ShowError ('add_no_theme_exist', 'STANDART', $ForumLang['Errors']);
			}

			//  --присвоение
			$ThemeName        = DB_Result ($Config_DBType, $Query, 0, 'TName');
			$ThemeBlock       = DB_Result ($Config_DBType, $Query, 0, 'TBlock');
			$ThemeAllowSmiles = DB_Result ($Config_DBType, $Query, 0, 'T_AllowSmiles');
			$ThemeTitleName   = $ThemeName;

			//  --проверка на блокировку темы
			if ($ThemeBlock == 'yes') {
				OBB_Main_ShowError ('add_theme_block', 'STANDART', $ForumLang['Errors']);
			}
		}
		else {
			$ThemeName        = '';
			$ThemeBlock       = '';
			$ThemeAllowSmiles = '';
			$ThemeTitleName   = '';
		}
		//дополнительные проверки - КОНЕЦ

		//определение переменных
		//  --иконка темы + разрешать ли смайлы
		$ThemeIconArray = array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'); //ПЕРЕДЕЛАТЬ ВПОСЛЕДСТВИЕ!!!
		$AllowSmiles    = isset ($_POST['AllowSmiles']) && $_POST['AllowSmiles'] == TRUE  ? 'yes' : 'no';
		$ThemeIcon      = isset ($_POST['ThemeIcon']) && in_array ($_POST['ThemeIcon'], $ThemeIconArray) ? intval ($_POST['ThemeIcon']) : '0';

		//  --важная ли тема
		$IsThemeImportant = 'no';
		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
			if (isset ($_POST['ThemeIsImportant']) && $_POST['ThemeIsImportant'] == TRUE) {
				$IsThemeImportant = 'yes';
			}
		}

		//  --содержимое переменной $PostContent - PostText
		$PostContentVar = '';
		if (isset ($_POST['PostContent'])) {
			$PostContentVar = trim ($_POST['PostContent']);
		}
		else {
			if (isset ($_GET['id_quote']) && ctype_digit ($_GET['id_quote']) && $IsPostAdding) {
				$IdQuote = intval ($_GET['id_quote']);
				$SQL = 'SELECT PostText AS PText,
							UserName AS PUser,
							PostDate AS PDate
						FROM posts WHERE PostID = \'' . $IdQuote . '\' AND ThemeID = \'' . $IdTheme . '\' AND ForumID = \'' . $IdForum . '\'';
				$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				if (DB_NumRows ($Config_DBType, $Query) > 0) {
					$QuoteText = DB_Result ($Config_DBType, $Query, 0, 'PText');
					$QuoteUser = DB_Result ($Config_DBType, $Query, 0, 'PUser');
					$QuoteDate = DB_Result ($Config_DBType, $Query, 0, 'PDate');
					$PostContentVar = '[quote="' . $QuoteUser . '"]' . $QuoteText . '[/quote]';
				}
			}
			else {
				$PostContentVar = '';
			}
		}

		//  --если гость - имя и почта
		if ($_SESSION['UserData']['UserType'] == 'guest') {
			$GuestLoginVar = isset ($_POST['GuestLogin']) ? trim ($_POST['GuestLogin']) : '';
			$GuestMailVar  = isset ($_POST['GuestMail'])  ? trim ($_POST['GuestMail'])  : '';
		}
		else {
			$GuestLoginVar = '';
			$GuestMailVar  = '';
		}

		//  --если тема - название темы
		if (!$IsPostAdding) {
			$ThemeNameVar = isset ($_POST['ThemeName'])  ? trim ($_POST['ThemeName'])  : '';
		}
		else {
			$ThemeNameVar = '';
		}
		//определение переменных - КОНЕЦ

		//обновление инфо о пользователе
		if (!$IsPostAdding) {
			$CurAction = 'add_theme';
		}
		else {
			$CurAction = 'add_post';
		}
		OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

		//отмечен ли флаг "Разрешить смайлы в теме/сообщении"
		if ($AllowSmiles == 'yes' || !isset ($_POST['add'])) {
			$CheckedAllowSm = ' checked="checked"';
		}
		else {
			$CheckedAllowSm = '';
		}

		//отмечен ли флаг "Важная тема"
		if ($IsThemeImportant == 'yes') {
			$CheckedThemeIsImportant = ' checked="checked"';
		}
		else {
			$CheckedThemeIsImportant = '';
		}

		//если нажата кнопка "Отправить запрос"
		if (isset ($_POST['add'])) {
			//ПРОВЕРКА
			//  --1)капча
			if ($CaptchaFlag) {
				$CaptchaChech = Defence_CheckCaptcha ('CaptchaImg');
				if (is_string ($CaptchaChech)) {
					$AddErrorArray[] = $ForumLang['AddErrors'][$CaptchaChech];
				}
			}

			//  --2)Антифлуд

			//  --3)Проверка логина и почты - если гость
			if ($_SESSION['UserData']['UserType'] == 'guest') {
				//  -проверка логина
				$CheckGuestLogin = OBB_Defence_CheckLogin ($GuestLoginVar);
				$GuestLoginCorrect = $CheckGuestLogin[0];
				if (!$GuestLoginCorrect) {
					$GuestLoginError = $CheckGuestLogin[1];
					$GuestLoginErrorText = OBB_Main_ReplaceSymbols ($ForumLang['AddErrors'][$GuestLoginError], array('min'=>OBB_MIN_LOGIN_LENGTH,'max'=>OBB_MAX_LOGIN_LENGTH));
					$AddErrorArray[] = $GuestLoginErrorText;
				}

				//  -проверка почты
				$CheckGuestMail = OBB_Defence_CheckMail ($GuestMailVar);
				$GuestMailCorrect = $CheckGuestMail[0];
				if (!$GuestMailCorrect) {
					$GuestMailError = $CheckGuestMail[1];
					$GuestMailErrorText = OBB_Main_ReplaceSymbols ($ForumLang['AddErrors'][$GuestMailError],array('min'=>OBB_MIN_MAIL_LENGTH,'max'=>OBB_MAX_MAIL_LENGTH));
					$AddErrorArray[] = $GuestMailErrorText;
				}
			}

			//  --4)Название темы - если добавляем тему
			if (!$IsPostAdding) {
				if ($ThemeNameVar == '') {
					$AddErrorArray[] = $ForumLang['AddErrors']['EmptyThemeNameLength'];
				}
				else {
					$ThemeNameLength = Main_Strlen ($ThemeNameVar);
					if ($ThemeNameLength < OBB_MIN_THEME_NAME_LENGTH || $ThemeNameLength > OBB_MAX_THEME_NAME_LENGTH) {
						$BadThemeNameLengthText = OBB_Main_ReplaceSymbols ($ForumLang['AddErrors']['BadThemeNameLength'], array('min'=>OBB_MIN_THEME_NAME_LENGTH, 'max'=>OBB_MAX_THEME_NAME_LENGTH));
						$AddErrorArray[] = $BadThemeNameLengthText;
					}
					else {
						$SameThemeSQL = 'SELECT 1 FROM themes WHERE themes.ForumID=\'' . $IdForum . '\' AND themes.ThemeName=\'' . Defence_EscapeString ($Config_DBType, $ThemeNameVar) . '\'';
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

			//  --4)Содержимое сообщения
			if ($PostContentVar == '') {
				$AddErrorArray[] = $ForumLang['AddErrors']['EmptyPostField'];
			}
			else {
				//для администратора размер сообщения - ВНЕ ОГРАНИЧЕНИЯ!!!
				if (Main_Strlen ($PostContentVar) > OBB_MAX_POST_LENGTH && $_SESSION['UserData']['UserType'] <> 'admin') {
					$BadPostLengthError = OBB_Main_ReplaceSymbols ($ForumLang['AddErrors']['BadPostLength'], array ('max'=>OBB_MAX_POST_LENGTH));
					$AddErrorArray[] = $BadPostLengthError;
				}
			}

			//  --5)Проверка загруженного файла - если он есть и разрешено
			if ($Attach) {
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

				//Подготовка контента для поиска - включение смайлов для их уничтожения
				$bbcode->SetEnableSmileys(TRUE);
				$SearchContentPost = $bbcode->parse ($PostContentVar);
				//$SearchContentPost = nl2br ($SearchContentPost);
				$SearchContentPost = OBB_Search_PrepareToSearch ($SearchContentPost);
				//парсинг для поиска - КОНЕЦ

				//старт транзакций
				$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
				$Transaction->StartTransaction ();

				//(1)Добавление темы - если оно
				if (!$IsPostAdding) {
					//  --запрос
					$ThemeInsertSQL = 'INSERT INTO themes
									   (ThemeName,
									   ThemeDate,
									   ThemeSmile,
									   ThemeBlock,
									   ThemeNumPosts,
									   ThemeNumViews,
									   ThemeNumAttaches,
									   ThemeImportant,
									   ThemeQuiz,
									   ThemeAllowSmiles,
									   ThemeUpDate,
									   UpdateUserID,
									   UserID,
									   UserName,
									   ThemeUpdateUserName,
									   ForumID)
									   VALUES
									   (\'' . Defence_EscapeString ($Config_DBType, $ThemeNameVar) . '\',
										\'' . $Now . '\',
										\'' . $ThemeIcon . '\',
										\'no\',
										\'0\',
										\'0\',
										\'0\',
										\'' . $IsThemeImportant . '\',
										\'no\',
										\'' . $AllowSmiles . '\',
										\'' . $Now . '\',
										\'' . $UpdateUserID . '\',
										\'' . $UserID . '\',
										\'' . $UserName . '\',
										\'' . $UpdateUserName . '\',
										\'' . $IdForum . '\')';
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

					//  --создание папкок темы - для прикреплений и кэша
					OBB_Main_CreateDir (OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId);
					OBB_Main_CreateDir (OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $ThemeInsertId);
				}
				else {
					$ThemeInsertId = $IdTheme;
				}

				//(3)добавление сообщения
				$InsertPostSQL = 'INSERT INTO posts
								(PostText,
								PostDate,
								PostSmilesAllow,
								UserID,
								UserName,
								ForumID,
								ThemeID)
								VALUES
								(\'' . Defence_EscapeString ($Config_DBType, $PostContentVar) . '\',
								\'' . $Now . '\',
								\'' . $AllowSmiles . '\',
								\'' . $UserID . '\',
								\'' . $UserName . '\',
								\'' . $IdForum . '\',
								\'' . $ThemeInsertId . '\')';
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

				//(4)добавление контента для поиска
				$SearchSQL = 'INSERT INTO search_content
							(PostID,
							SearchPostContent,
							ForumID)
							VALUES
							(\'' . $LastPostId . '\',
							\'' . Defence_EscapeString ($Config_DBType, $SearchContentPost) . '\',
							\'' . $IdForum . '\')';
				$SearchQuery = DB_Query($Config_DBType, $SearchSQL, $ForumConnection);
				if (!$SearchQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($SearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$SearchPostArray = array (
										'Operation'  => 'd',
										'FieldArray' => array (),
										'WhereArray' => array('PostID'=>array ('Value'=>$LastPostId, 'Type'=>'int')),
										'TableName'  => 'search_content'
									);
				$Transaction->PrepareQueryToRollback ($SearchPostArray);

				//(5)Увеличение статистики пользователя - если не гость
				if ($_SESSION['UserData']['UserType'] <> 'guest') {
					//  --если добавление сообщения
					if ($IsPostAdding) {
						//запрос
						$UserStatisticsSQL = 'UPDATE users
											SET UserNumPosts = (SELECT COUNT(*) FROM posts WHERE UserID = \'' . $UserID . '\')
											WHERE UserID = \'' . $UserID . '\'';

						//транзакционный массив
						$UserStatisticsArray = array (
													'Operation'  => 'u',
													'FieldArray' => array ('UserNumPosts' => array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'string')),
													'WhereArray' => array ('UserID'=>array ('Value'=>$UserID, 'Type'=>'int')),
													'TableName'  => 'users'
												);
					}
					//  --если добавление темы
					else {
						//запрос
						$UserStatisticsSQL = 'UPDATE users
											SET UserNumPosts = (SELECT COUNT(*) FROM posts WHERE UserID = \'' . $UserID . '\'),
												UserNumThemes = (SELECT COUNT(*) FROM themes WHERE UserID = \'' . $UserID . '\')
											WHERE UserID = \'' . $UserID . '\'';

						//транзакционный массив
						$UserStatisticsArray = array (
													'Operation'  => 'u',
													'FieldArray' => array ('UserNumPosts' => array ('Value'=>'', 'Flag'=>'decr','Type'=>'string'),
																		   'UserNumThemes' => array ('Value'=>'','Flag'=>'decr','Type'=>'string')),
													'WhereArray' => array ('UserID'=>array ('Value'=>$UserID, 'Type'=>'int')),
													'TableName'  => 'users'
													);
					}
					$UserStatisticsQuery = DB_Query ($Config_DBType, $UserStatisticsSQL, $ForumConnection);
					if (!$UserStatisticsQuery) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ($UserStatisticsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
					}

					$Transaction->PrepareQueryToRollback ($UserStatisticsArray);
				}

				//{6} Прикрепление (добавление/удаление/заменение) - ОПИСАНИЕ
				//  --определение флага, выбран ли аттач
				if ($Attach && isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '') {
					//  --присвоение данных файла (удобство)
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

					//  --увеличение статистики прикреплений в теме
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
										(\'' . intval ($LastPostId) . '\',
										\'' . Defence_EscapeString ($Config_DBType, $AttachName) . '\',
										\'' . intval ($AttachSize) . '\',
										\'' . Defence_EscapeString ($Config_DBType, $AttachType) . '\',
										\'' . Defence_EscapeString ($Config_DBType, $AttachExt) . '\',
										\'' . intval ($AttachWidth) . '\',
										\'' . intval ($AttachHeight) . '\',
										\'0\')';
					$DescrInsertQuery = DB_Query ($Config_DBType, $DescrInsertSQL, $ForumConnection);
					if (!$DescrInsertQuery) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ($DescrInsertSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
					}

					$DescrInsertArray = array (
										   'Operation'  => 'd',
										   'FieldArray' => array (),
										   'WhereArray' => array('PostID'=>array ('Value'=>$LastPostId, 'Type'=>'int')),
										   'TableName'  => 'post_files'
										);
					$Transaction->PrepareQueryToRollback ($DescrInsertArray);
				}

				//(7)Создание кэша поста
				if (OBB_PUT_MESSAGE_CACHE) {
					//  --разрешены ли смайлы
					if ($IsPostAdding) {
						$CacheAllowSmiles = $ThemeAllowSmiles == 'yes' && $AllowSmiles == 'yes';
					}
					else {
						$CacheAllowSmiles = $AllowSmiles == 'yes';
					}

					//  --путь к кэш-файлу
					$PathToCache = OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/post_' . $LastPostId;

					//парсинг сообщения
					$bbcode->SetEnableSmileys($CacheAllowSmiles);
					$PostHTML = $bbcode->parse ($PostContentVar);
					//$PostHTML = nl2br ($PostHTML);
					if (!OBB_Cache_PutCacheFile ($PathToCache, $PostHTML)) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ('Error while creating cache file', OBB_ERROR_LOG_FILE);
					}
				}

				//(8)Загрузка файла в директорию - если загружен
				if ($Attach && isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '') {
					$PostIdUpload = $LastPostId;
					$UploadFileName = sha1 ($PostIdUpload . $Config_AttachSalt) . '.' . $AttachExt;
					if (!move_uploaded_file ($AttachData, OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $ThemeInsertId . '/' . $UploadFileName)) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ('Error while moving attach data', OBB_ERROR_LOG_FILE);
					}
				}

				//(8)Обновление закэшированной сериализованной строки с темами (если добавляется тема)
				if (!$IsPostAdding) {
					$KeyValArray = array ($ThemeInsertId, $ThemeNameVar);
					$FilePath = OBB_CACHE_DIR . '/DBCache/goto_forum_' . $IdForum;
					OBB_Cache_PutCacheArray ($FilePath, $KeyValArray);
				}

				//(9)Обновление даты захода пользователя на данную тему
				Main_UpdateThemeView ($ThemeInsertId);

				//(10)Завершение транзакции
				$Transaction->CommitTransaction ();

				//(11)Изменение статистики темы
				OBB_Main_UpdateThemeStatistics ($ThemeInsertId);

				//(12)Изменение статистики форума
				if ($IsPostAdding) {
					OBB_Main_UpdateForumStatistics ($IdForum);
				}
				else {
					OBB_Main_UpdateForumStatistics ($IdForum, TRUE);
				}

				//(13)Письмо администратору
				if ($Config_Mail['AdminMail'] == TRUE) {
					//$AddMailer = new OwnBB_Mailer ($Config_Mail);
					$LetterAdminName = $Config_Mail['FromName'];
					$LetterAdminMail = $Config_Mail['FromMail'];

					//МАССИВ ОТПРАВИТЕЛЯ ПИСЬМА
					$LetterSenderArray = array('address'=>$LetterAdminMail,'name'=>$LetterAdminName);

					//МАССИВ ПОЛУЧАТЕЛЯ ПИСЬМА
					$LetterGetterArray = array('address'=>$LetterAdminMail,'name'=>$LetterAdminName);

					//ТЕМА ПИСЬМА
					if (!$IsPostAdding) {
						$LetterSubject = $ForumLang['AddMail']['AddMailTheme'];
					}
					else {
						$LetterSubject = $ForumLang['AddMail']['AddMailPost'];
					}

					//ТЕЛО ПИСЬМА
					//  --дата
					$LetterDate = Main_ConvertDate ($Now, array(), 'd.m.Y, H:i');

					//  --имя форума
					$LetterForumName = $ForumName;

					//  --ID и логин пользователя
					if ($_SESSION['UserData']['UserType'] <> 'guest') {
						$LetterUserName = $_SESSION['UserData']['UserName'];
						$LetterUserID = $_SESSION['UserData']['UserID'];
					}
					else {
						$LetterUserName = $GuestLoginVar;
						$LetterUserID = intval (0);
					}

					//  --статус пользователя
					$LetterUserStatus = $_SESSION['UserData']['UserType'];

					//  --ссылка на сайт форума
					$SiteAddress = 'http://' . $HostName . $SelfName;

					//  --детали тела письма
					//  --1)если добавляем тему
					if (!$IsPostAdding) {
						$FileGetContents = 'AdminMailAddTheme';
						$LetterThemeName = $ThemeNameVar;
						$LetterThemeID = $ThemeInsertId;
						$FilePutContents = 'Admin_ThemeAdd' . $LetterThemeID;
					}
					//  --2)если добавляем сообщение
					else {
						$FileGetContents = 'AdminMailAddPost';
						$LetterThemeName = $ThemeName;
						$LetterThemeID = $IdTheme;
						$FilePutContents = 'Admin_PostAdd' . $LastPostId;
					}

					//  --генерация тела письма
					$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/' . $FileGetContents . '.html');
					$AdminLetter = str_replace ('{forumname}'  , $LetterForumName, $AdminLetter);
					$AdminLetter = str_replace ('{username}'   , $LetterUserName, $AdminLetter);
					$AdminLetter = str_replace ('{userid}'     , $LetterUserID, $AdminLetter);
					$AdminLetter = str_replace ('{userstatus}' , $LetterUserStatus, $AdminLetter);
					$AdminLetter = str_replace ('{userdate}'   , $LetterDate, $AdminLetter);
					$AdminLetter = str_replace ('{themeid}'    , $LetterThemeID, $AdminLetter);
					$AdminLetter = str_replace ('{themename}'  , $LetterThemeName, $AdminLetter);
					if ($IsPostAdding) {
						$AdminLetter = str_replace ('{postid}', $LastPostId, $AdminLetter);
					}
					//ТЕЛО ПИСЬМА - КОНЕЦ

					//если отладочный режим - ложим в файл, иначе - отправляем письмо на ящик
					if (OBB_MAIL_DEBUG == false) {
						//$AddMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
						OBB_Mail_Send ($LetterSenderArray, $LetterGetterArray, $LetterSubject, $AdminLetter);
					}
					else {
						file_put_contents (OBB_ERROR_MAIL_DIR . '/' . $FilePutContents . '.html', $AdminLetter);
					}
				}

				//(14Редирект
				$CommonURL = '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $ThemeInsertId;
				if (!$IsPostAdding) {
					$Redirect = $CommonURL;
					$Message  = 'add_theme';
				}
				else {
					$Redirect = $CommonURL . '&last_post=' . $LastPostId . '#pid' . $LastPostId;
					$Message  = 'add_post';
				}
				$_SESSION['Redirect'] = $Redirect;
				$_SESSION['Message']  = $Message;
				$URL = '?action=message';
				OBB_Main_Redirect ($URL);
			}
		}

		//дополнение главной навигации
		if ($IsPostAdding) {
			$ThemeHref = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
			$NavigArray[] = array ($ThemeHref, $ThemeName);
			$NavigArray[] = array('', $ForumLang['Add'] . ' ' . $ForumLang['AddPost']);
		}
		else {
			$NavigArray[] = array ('', $ForumLang['Add'] . ' ' . $ForumLang['AddTheme']);
		}

		//гостевые поля ввода
		if ($_SESSION['UserData']['UserType'] == 'guest') {
			$GuestLogin = '<tr>
								<td style="width:160px;" class="FormTitleTD">
									<div class="InputTitle">
										' . $ForumLang['AddGuestName'] . '<span class="Important">*</span>
									</div>
									<div class="InputDescr">
										(' . OBB_Main_ReplaceSymbols ($ForumLang['AddGuestNameLimit'], array('min'=>OBB_MIN_LOGIN_LENGTH, 'max'=>OBB_MAX_LOGIN_LENGTH)) . ')
									</div>
								</td>
								<td class="FormInputTD">
									<input style="width:280px;" class="InpEl InpText" id="AddGuestLogin" type="text" name="GuestLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="' . Defence_HTMLSpecials ($GuestLoginVar) . '" />
							  </td>
							</tr>';

			$GuestMail  = '<tr>
							<td style="width:160px;" class="FormTitleTD">
								<div class="InputTitle">
								' . $ForumLang['AddGuestMail'] . '<span class="Important">*</span>
								</div>
								<div class="InputDescr">
									(' . OBB_Main_ReplaceSymbols ($ForumLang['AddGuestMailLimit'], array('min'=>OBB_MIN_MAIL_LENGTH, 'max'=>OBB_MAX_MAIL_LENGTH)) . ')
								</div>
							</td>
							<td class="FormInputTD">
								<input style="width:280px;" class="InpEl InpText" id="AddGuestMail" type="text" name="GuestMail" maxlength="'  . OBB_MAX_MAIL_LENGTH  . '" value="' . Defence_HTMLSpecials ($GuestMailVar)  . '" />
							</td>
						</tr>';
		}
		else {
			$GuestLogin = '';
			$GuestMail = '';
		}

		//прикрепление
		if ($Attach) {
			$AttachBlock = '<tr>
								<td style="width:160px;" class="FormTitleTD AttachTitleTD">
									<div class="InputTitle">
										' . $ForumLang['AddAttach'] . '
									</div>
								</td>
								<td class="FormInputTD">
									<div class="MainBlockAttach">
										<div class="AttachAddAction">
											' . $ForumLang['AddAttachAdd'] . '
										</div>
										<div class="AttachFileField">
											<input type="file" name="FileAttach" />
										</div>
										<div class="AttachExtensions">
											' . $ExstensionString . '

										</div>
										<div class="AttachExtensions">
											<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedSize'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_ATTACH_SIZE . '&nbsp;' . $ForumLang['AddAttachb'] . '</span>
										</div>
										<div class="AttachExtensions">
											<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedGabarits'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_IMAGE_WIDTH . '(' . $ForumLang['AddAttachWidth'] . ')&nbsp;<strong>X</strong>&nbsp;' . OBB_MAX_IMAGE_HEIGHT . '(' . $ForumLang['AddAttachHeight'] . ')</span>
										</div>
									</div>
								</td>
							</tr>';
		}
		else {
			$AttachBlock = '';
		}

		//важная ли тема
		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS && !$IsPostAdding) {
			$ThemeIsImportant = '<div>
									<input class="InpCheckbox" type="checkbox"' . $CheckedThemeIsImportant . ' name="ThemeIsImportant" />
									<span class="Usual">' . $ForumLang['AddThemeIsImportant'] . '</span>
								</div>';

		}
		else {
			$ThemeIsImportant = '';
		}

		//иконка темы - ПЕРЕДЕЛАТЬ ВПОСЛЕДСТВИЕ!!!
		if (!$IsPostAdding) {
			$ThemeIcons = '';
			for ($i = 0; $i <= 11; $i++) {
				$CheckedIcon = (int) $ThemeIcon == $i ? ' checked="checked"' : '';
				$ThemeIcons .= '<input' . $CheckedIcon . ' type="radio" name="ThemeIcon" value="' . $i . '" />';
				if ($i == 0) {
					$ThemeIcons .= $ForumLang['AddNoIcon'] . '&nbsp;';
				}
				else {
					$ThemeIcons .= '<img style="margin-right:5px;" alt="" src="' . OBB_IMAGE_DIR . '/ThemeIcons/' . $i . '.gif" />';
				}
			}

			$ThemeSmiles = '<tr>
								<td style="width:160px;" class="FormTitleTD">
									<div class="InputTitle">
										' . $ForumLang['AddThemeIcon'] . '<span class="Important">*</span>
									</div>
								</td>
								<td class="FormInputTD">
									<div>
										' . $ThemeIcons . '
									</div>
									<div style="height:30px;"><!-- --></div>
								</td>
							</tr>';
		}
		else {
			$ThemeSmiles = '';
		}

		//Капча
		$CaptchaBlock = $CaptchaFlag ? Echo_CaptchaBlock2 ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'AddCaptcha') : '';

		//имя темы
		if (!$IsPostAdding) {
			$ThemeName = '<tr>
							<td style="width:160px;" class="FormTitleTD">
								<div class="InputTitle">
									' . $ForumLang['AddThemeName'] . '<span class="Important">*</span>
								</div>
								<div class="InputDescr">
									(' . OBB_Main_ReplaceSymbols ($ForumLang['AddThemeNameLimit'], array('min'=>OBB_MIN_THEME_NAME_LENGTH, 'max'=>OBB_MAX_THEME_NAME_LENGTH)) . ')
								</div>
							</td>
							<td class="FormInputTD">
								<input style="width:280px;" class="InpEl InpText" id="AddThemeName" type="text" name="ThemeName" value="' . Defence_HTMLSpecials ($ThemeNameVar) . '" />
							</td>
						</tr>';
		}
		else {
			$ThemeName = '';
		}

		//URL
		$ThemeIDURL = $IsPostAdding ? '&id_theme=' . $IdTheme : '';
		$ActionURL  = $SelfName . '?action=add&label=' . $Label . '&id_forum=' . $IdForum . $ThemeIDURL;
		$ActionURL = Defence_HTMLSpecials ($ActionURL);

		//$ActionString
		$ActionString = !$IsPostAdding ? $ForumLang['Add'] . ' ' . $ForumLang['AddTheme'] : $ForumLang['Add'] . ' ' . $ForumLang['AddPost'];

		//Массив Javascript - НАЧАЛО
		$JSCaptchaFlag   = $CaptchaFlag;
		$JSIsGuestFlag   = $_SESSION['UserData']['UserType'] == 'guest';
		$JSIsThemeAdding = !$IsPostAdding;
		$JSIsAdminFlag   = $_SESSION['UserData']['UserType'] == 'admin';
		$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>$Label, 'IsCaptcha'=>$JSCaptchaFlag, 'IsGuest'=>$JSIsGuestFlag, 'IsTheme'=>$JSIsThemeAdding, 'IsAdmin'=>$JSIsAdminFlag);
		//Массив Javascript - КОНЕЦ

		//JS-массив
		$JavaScriptArray = array (
								array ('jquery.js', ''),
								array ('functions.js', ''),
								array ('gui.js', ''),
								array ('parameters', $JSParametersArray),
								array ('Add.js', '')
							);

		//заголовок страницы
		if (!$IsPostAdding) {
			$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['AddTheme'], array('forumname'=>$Config_ForumName, 'underforumname'=>$ForumName));
		}
		else {
			$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['AddPost'], array('forumname'=>$Config_ForumName, 'underforumname'=>$ForumName, 'themename'=>$ThemeTitleName));
		}

		//верх
		$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'AddDelimiterDiv');

		//вывод ошибок (если есть)
		if (sizeof ($AddErrorArray) > 0) {
			$ErrorListBlock = '<div class="ErrorBlockDiv">
									<div class="ErrorBlockTitle">' . $ForumLang['AddErrors']['ErrorBlockTitle'] . ':</div>';
			foreach ($AddErrorArray as $Key=>$Value) {
				$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
			}
			$ErrorListBlock .= ' </div>
								<div style="height:15px;"><!-- --></div>';
		}
		else {
			$ErrorListBlock = '';
		}
		$MainOutput .= $ErrorListBlock;

		//массив BB-редактора
		$Parameters = array ('BBArray'=>array(
										array ('BBType'=>'simple', 'BBSubType'=>'b'),
										array ('BBType'=>'simple', 'BBSubType'=>'i'),
										array ('BBType'=>'simple', 'BBSubType'=>'u'),
										array ('BBType'=>'simple', 'BBSubType'=>'s'),
										array ('BBType'=>'simple', 'BBSubType'=>'quote'),
										array ('BBType'=>'simple', 'BBSubType'=>'code'),
										array ('BBType'=>'simple', 'BBSubType'=>'url'),
										array ('BBType'=>'simple', 'BBSubType'=>'img'),
										array ('BBType'=>'simple', 'BBSubType'=>'list'),
										array ('BBType'=>'simple', 'BBSubType'=>'smile'),
										array ('BBType'=>'simple', 'BBSubType'=>'color')
									),
							'ColorsArray'=>array ('#000000', '#610B38', '#DF0101', '#8A4B08',
												  '#FF8000', '#0B610B', '#01DF01', '#01DFD7',
												  '#08088A', '#2E2EFE', '#7401DF', '#DF01D7',
												  '#585858', '#BDBDBD', '#D0A9F5', '#A9D0F5'),
							'TextareaName'=>'PostContent',
							'TextareaID'=>'PostContent',
							'FormID'=>'AddForm',
							'NumberCols'=>'100',
							'NumberRows'=>'15',
							'BorderColor'=>'#000000',
							'SmilesLink'=>Defence_HTMLSpecials ($SelfName . '/index.php?action=tooling&label=smile&f_id=AddForm&t_id=PostContent'),
							'ImagesPath'=>OBB_IMAGE_DIR . '/bbcodes',
							'TextValue'=>Defence_HTMLSpecials ($PostContentVar));

		if ($IsPostAdding) {
			$AdSubmitValue = $ForumLang['AddSubmit'];
		}
		else {
			$AdSubmitValue = $ForumLang['AddSubmitTheme'];
		}
							
		//форма добавления
		$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName"><span>' . $ActionString . '</span></td>
							</tr>
							<tr class="ForumMainTR">
								<td style="padding:0;">
									<form style="padding:0; margin:0;" id="AddForm" action="' . $ActionURL . '" method="post"' . $Multipart . '>
										<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
											' . $GuestLogin . '
											' . $GuestMail  . '
											' . $ThemeName  . '
											<tr>
												<td style="width:160px; vertical-align:top;" class="FormTitleTD">
													<br /><br />
													<div class="InputTitle">
														' . $ForumLang['AddMessage'] . '<span class="Important">*</span>
													</div>
													<div class="InputDescr">
														(' . OBB_Main_ReplaceSymbols ($ForumLang['AddPostLimit'], array('max'=>OBB_MAX_POST_LENGTH)) . ')
													</div>
												</td>
												<td class="FormInputTD TextareaTD">
													' . Echo_BBEditor ($Parameters) . '
													<div>
														<textarea style="width:95%;" class="InpEl InpTextarea" id="PostContent" name="PostContent" onfocus="initInsertions(\'AddForm\', \'PostContent\');" onkeyup="storeCaret(this);" onclick="storeCaret(this);" onselect="storeCaret(this);" cols="87" rows="17">' . Defence_HTMLSpecials ($PostContentVar) . '</textarea>
													</div>
													<div style="height:15px;"><!-- --></div>
												</td>
											</tr>
											<tr>
												<td style="width:160px;" class="FormTitleTD">
													<div class="InputTitle">
														' . $ForumLang['AddAOptions'] . '
													</div>
												</td>
												<td class="FormInputTD">
													<div>
														<input class="InpCheckbox" type="checkbox"' . $CheckedAllowSm . ' name="AllowSmiles" />
														<span class="Usual">' . $ForumLang['AddAllowSmiles'] . '</span>
													</div>
													' . $ThemeIsImportant . '
													<div style="height:15px;"><!-- --></div>
												</td>
											</tr>
											' . $AttachBlock  . '
											' . $ThemeSmiles  . '
											' . $CaptchaBlock . '
											<tr>
												<td style="border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
													<span class="Important">*</span> - ' . $ForumLang['AddImportantFields'] . '
												</td>
											</tr>
											<tr>
												<td colspan="2" class="FormInputTD CenterTD">
													<input type="hidden" name="add" value="1" />
													<div id="SubmitButtonDiv">
														<input class="InpButton" id="AddSubmit" type="submit" name="Adding"   value="' . $AdSubmitValue . '" />
														<input class="InpButton" type="reset" name="Adding" value="' . $ForumLang['AddReset'] . '" />
													</div>
												</td>
											</tr>
										</table>
									</form>
								</td>
							</tr>
							<tr>
								<td class="ForumsTableBottom" colspan="1">
									<div><!-- --></div>
								</td>
							</tr>
						</table>';

		//Футер форума
		$MainOutput .= Echo_PrintFoot ();

		//вывод
		Main_ShowStandartHeader ();
		echo $MainOutput;

		break;
	}

	case"edit": {
		//проверка, есть ли разрешения для редактирования сообщения
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			//  --флаг вывода сообщения об ошибке
			$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';

			//  --проверка на полный запрет редактирования сообщений
			if (!OBB_EDIT_POSTS) {
				OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}

			//  --проверка на разрешения группы
			if (!$UserGroups_Permissions['EditPosts']) {
				OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}
		}

		//Дополнительные проверки
		//  --запрос
		$SQL = 'SELECT themes.ThemeName AS TName,
					themes.ThemeAllowSmiles AS T_AllowSmiles,
					posts.UserID AS PUserID,
					posts.PostDate AS PDate,
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
				LEFT JOIN themes ON posts.ThemeID = themes.ThemeID
				LEFT JOIN post_files ON posts.PostID = post_files.PostID
				LEFT JOIN search_content ON posts.PostID = search_content.PostID
				WHERE posts.ThemeID = \'' . $IdTheme . '\'
				AND posts.ForumID = \'' . $IdForum . '\'
				AND posts.PostID = \'' . $IdPost . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		//  --проверка на существование поста
		if (DB_NumRows ($Config_DBType, $Query) < 1) {
			OBB_Main_ShowError ('add_no_post_exist', 'STANDART', $ForumLang['Errors']);
		}

		//  --присвоение
		$EditThemeName      = DB_Result ($Config_DBType, $Query, 0, 'TName');
		$EditThemeSmiles    = DB_Result ($Config_DBType, $Query, 0, 'T_AllowSmiles');
		$EditPostUserID     = DB_Result ($Config_DBType, $Query, 0, 'PUserID');
		$EditPostDate       = DB_Result ($Config_DBType, $Query, 0, 'PDate');
		$EditPostText       = DB_Result ($Config_DBType, $Query, 0, 'PText');
		$EditPostEditDate   = DB_Result ($Config_DBType, $Query, 0, 'PEditDate');
		$EditPostSmiles     = DB_Result ($Config_DBType, $Query, 0, 'PSmilesAllow');
		$EditPostFileName   = DB_Result ($Config_DBType, $Query, 0, 'PFileName');
		$EditPostFileSize   = DB_Result ($Config_DBType, $Query, 0, 'PFileSize');
		$EditPostFileType   = DB_Result ($Config_DBType, $Query, 0, 'PFileType');
		$EditPostFileHeight = DB_Result ($Config_DBType, $Query, 0, 'PHeight');
		$EditPostFileWidth  = DB_Result ($Config_DBType, $Query, 0, 'PWidth');
		$EditPostFileViews  = DB_Result ($Config_DBType, $Query, 0, 'PNumViews');
		$EditPostSearch     = DB_Result ($Config_DBType, $Query, 0, 'PSearch');

		//  --расширение старого аттача
		$OldAttachNameArray = explode ('.', $EditPostFileName);
		$EditPostFileExt = !OBB_Main_IsEmpty ($EditPostFileName) ? $OldAttachNameArray[1] : NULL;

		//  --приведение типов
		$EditPostUserID = intval ($EditPostUserID);

		//  --проверка на то, свое ли сообщение редактирует пользователь
		//  --(АДМИНИСТРАТОР МОЖЕТ РЕДАКТИРОВАТЬ ВСЕ)
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			if ($EditPostUserID <> $_SESSION['UserData']['UserID']) {
				OBB_Main_ShowError ('add_bad_editer', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}
		}

		//определение переменных
		$PostContentVar = isset ($_POST['PostContent']) ? trim ($_POST['PostContent']) : $EditPostText;
		if (isset ($_POST['AllowSmiles'])) {
			$AllowSmiles = 'yes';
		}
		else {
			if (!isset ($_POST['edit'])) {
				$AllowSmiles = Main_Strtolower ($EditPostSmiles);
			}
			else {
				$AllowSmiles = 'no';
			}
		}
		//определение переменных - КОНЕЦ

		//обновление информации о текущем пользователе
		OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'edit_post');

		//отмечен ли флаг "Разрешить смайлы в теме/сообщении"
		$CheckedAllowSm = $AllowSmiles == 'yes' ? ' checked="checked"' : '';

		//если нажата кнопка "Редактировать"
		if (isset ($_POST['edit'])) {
			//прикреплен ли к сообщению файл
			$OldAttachExists = !OBB_Main_IsEmpty ($EditPostFileName) && !OBB_Main_IsEmpty ($EditPostFileSize) && !OBB_Main_IsEmpty ($EditPostFileType);

			//проводить ли операции с загруженными файлами
			$DoAttachOperations = $Attach && isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '';

			//Действие с прикреплением
			$AttachActionArray = array ('Save', 'Delete', 'Replace');
			$AttachActionVar = isset ($_POST['AttachAction']) && in_array ($_POST['AttachAction'], $AttachActionArray) ? $_POST['AttachAction'] : 'Save';

			//ПРОВЕРКА
			//  --1)капча
			if ($CaptchaFlag) {
				$CaptchaChech = Defence_CheckCaptcha ('CaptchaImg');
				if (is_string ($CaptchaChech)) {
					$AddErrorArray[] = $ForumLang['AddErrors'][$CaptchaChech];
				}
			}

			//  --2)Содержимое сообщения
			if ($PostContentVar == '') {
				$AddErrorArray[] = $ForumLang['AddErrors']['EmptyPostField'];
			}
			else {
				//для администратора размер сообщения - ВНЕ ОГРАНИЧЕНИЯ!!!
				if (Main_Strlen ($PostContentVar) > OBB_MAX_POST_LENGTH && $_SESSION['UserData']['UserType'] <> 'admin') {
					$BadPostLengthError = OBB_Main_ReplaceSymbols ($ForumLang['AddErrors']['BadPostLength'], array ('max'=>OBB_MAX_POST_LENGTH));
					$AddErrorArray[] = $BadPostLengthError;
				}
			}

			//  --3)Прикрепление
			if ($DoAttachOperations && $AttachActionVar == 'Replace') {
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
			//ПРОВЕРКА - КОНЕЦ

			//если ошибок не обнаружено
			if (sizeof ($AddErrorArray) == 0) {
				//получение ресурса парсинга
				$bbcode = OBB_BBCode_GetParseStandart ();

				//Подготовка контента для поиска - отключение смайлов для их уничтожения
				$bbcode->SetEnableSmileys(TRUE);
				$SearchContentPost = $bbcode->parse ($PostContentVar);
				//$SearchContentPost = nl2br ($SearchContentPost);
				$SearchContentPost = OBB_Search_PrepareToSearch ($SearchContentPost);
				//парсинг для поиска - КОНЕЦ

				//старт транзакций
				$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
				$Transaction->StartTransaction ();

				//(1)Редактирование тела сообщения
				$UpdatePostSQL = 'UPDATE posts
								SET PostText=\'' . Defence_EscapeString ($Config_DBType, $PostContentVar) . '\',
									PostSmilesAllow = \'' . $AllowSmiles . '\',
									PostEditDate = \'' . time () . '\'
								WHERE PostID = \'' . $IdPost . '\'';
				$UpdatePostQuery = DB_Query($Config_DBType, $UpdatePostSQL, $ForumConnection);
				if (!$UpdatePostQuery) {
					OBB_Main_Log ($UpdatePostSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				$UpdatePostArray = array (
										  'Operation'  => 'u',
										  'FieldArray' => array ('PostText'=> array('Value'=>$EditPostText,'Flag'=>'none','Type'=>'string'),
																'PostSmilesAllow'=>array ('Value'=>$EditPostSmiles,'Flag'=>'none','Type'=>'string'),
																'PostEditDate'=>array ('Value'=>$EditPostEditDate,'Flag'=>'none','Type'=>'string')),
										  'WhereArray' => array ('PostID'=>array ('Value'=>$IdPost, 'Type'=>'int')),
										  'TableName'  => 'posts'
										 );
				$Transaction->PrepareQueryToRollback ($UpdatePostArray);

				//(2)Редактирование поисковой информации
				$SearchSQL = 'UPDATE search_content SET SearchPostContent = \'' . Defence_EscapeString ($Config_DBType, $SearchContentPost) . '\' WHERE PostID = \'' . $IdPost . '\'';
				$SearchQuery = DB_Query($Config_DBType, $SearchSQL, $ForumConnection);
				if (!$SearchQuery) {
					OBB_Main_Log ($SearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$SearchPostArray = array (
										'Operation'  => 'u',
										'FieldArray' => array ('SearchPostText' => array ('Value'=>$EditPostSearch, 'Flag'=>'none', 'Type'=>'string')),
										'WhereArray' => array('PostID'=>array ('Value'=>$IdPost, 'Type'=>'int')),
										'TableName'  => 'search_content'
									);
				$Transaction->PrepareQueryToRollback ($SearchPostArray);

				//(3)Прикрепления и статистика тем
				if ($Attach && $AttachActionVar <> 'Save') {
					if ($DoAttachOperations && $AttachActionVar == 'Replace') {
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
					}
					else {
						$AttachName   = '';
						$AttachType   = '';
						$AttachData   = '';
						$AttachExt    = '';
						$AttachSize   = '';
						$AttachImage  = '';
						$AttachWidth  = '';
						$AttachHeight = '';
					}

					//флаг действий с файлом
					$AttachActionFlag = NULL;

					//массив транзакций
					$PostFileArray = array ();

					//действия
					if ($AttachActionVar == 'Delete') {
						if ($OldAttachExists) {
							//удаление описания прикрепления
							$PostFileSQL = 'DELETE FROM post_files WHERE PostID = \'' . $IdPost . '\'';

							//массив транзакций
							$PostFileArray = array (
											   'Operation'   => 'i',
											   'FieldArray'  => array ('PostID'           => array ('Value'=>$IdPost, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileName'     => array ('Value'=>$EditPostFileName, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileSize'     => array ('Value'=>$EditPostFileSize, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileType'     => array ('Value'=>$EditPostFileType, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileExt'      => array ('Value'=>$EditPostFileExt, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileWidth'    => array ('Value'=>$EditPostFileWidth, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileHeight'   => array ('Value'=>$EditPostFileHeight, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileNumViews' => array ('Value'=>$EditPostFileViews, 'Flag'=>'none', 'Type'=>'int')),
											   'WhereArray'  => array(),
											   'TableName'   => 'post_files'
											);

							//флаг
							$AttachActionFlag = 'delete';
						}
					}
					else if ($AttachActionVar == 'Replace' && isset ($_FILES['FileAttach']['name']) && $_FILES['FileAttach']['name'] <> '') {
						if ($OldAttachExists) {
							$PostFileSQL = 'UPDATE post_files
											SET
											PostFileName = \'' . Defence_EscapeString ($Config_DBType, $AttachName) . '\',
											PostFileSize = \'' . intval ($AttachSize) . '\',
											PostFileType = \'' . Defence_EscapeString ($Config_DBType, $AttachType) . '\',
											PostFileExt = \'' . Defence_EscapeString ($Config_DBType, $AttachExt) . '\',
											PostFileWidth = \'' . intval ($AttachWidth) . '\',
											PostFileHeight = \'' . intval ($AttachHeight) . '\',
											PostFileNumViews = \'0\'
											WHERE PostID = \'' . $IdPost . '\'';

							//массив транзакций
							$PostFileArray = array (
												'Operation'   => 'u',
												'FieldArray'  => array ('PostFileName'    => array ('Value'=>$EditPostFileName, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileSize'     => array ('Value'=>$EditPostFileSize, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileType'     => array ('Value'=>$EditPostFileType, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileExt'      => array ('Value'=>$EditPostFileExt, 'Flag'=>'none', 'Type'=>'string'),
																	   'PostFileWidth'    => array ('Value'=>$EditPostFileWidth, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileHeight'   => array ('Value'=>$EditPostFileHeight, 'Flag'=>'none', 'Type'=>'int'),
																	   'PostFileNumViews' => array ('Value'=>$EditPostFileViews, 'Flag'=>'none', 'Type'=>'int')),
												'WhereArray' => array ('PostID'=>array ('Value'=>$IdPost, 'Type'=>'int')),
												'TableName'  => 'post_files'
												);

							//флаг
							$AttachActionFlag = 'update';
						}
						else {
							$PostFileSQL = 'INSERT INTO post_files
											(PostID,
											PostFileName,
											PostFileSize,
											PostFileType,
											PostFileExt,
											PostFileWidth,
											PostFileHeight,
											PostFileNumViews)
											VALUES
											(\'' . intval ($IdPost) . '\',
											\'' . Defence_EscapeString ($Config_DBType, $AttachName) . '\',
											\'' . intval ($AttachSize) . '\',
											\'' . Defence_EscapeString ($Config_DBType, $AttachType) . '\',
											\'' . Defence_EscapeString ($Config_DBType, $AttachExt) . '\',
											\'' . intval ($AttachWidth) . '\',
											\'' . intval ($AttachHeight) . '\',
											\'0\')';

							//флаг
							$AttachActionFlag = 'insert';
						}
					}

					//выполнение запроса
					if ($AttachActionFlag == 'insert' || $AttachActionFlag == 'update' || $AttachActionFlag == 'delete') {
						$PostFileQuery = DB_Query($Config_DBType, $PostFileSQL, $ForumConnection);
						if (!$PostFileQuery) {
							OBB_Main_Log ($PostFileSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
						}
					}

					//добавление в транзакционный массив
					$Transaction->PrepareQueryToRollback ($PostFileArray);

					//статистика темы
					if ($AttachActionFlag == 'insert' || $AttachActionFlag == 'delete') {
						$SQL = 'UPDATE themes
								SET ThemeNumAttaches = (SELECT COUNT(*)
														FROM post_files
														LEFT JOIN posts ON posts.PostID = post_files.PostID
														WHERE posts.ThemeID = \'' . $IdTheme . '\')
								WHERE ThemeID = \'' . $IdTheme . '\'';
						$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
						if (!$Query) {
							OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
						}

						//флаг
						$ThemeIncrFlag = $AttachActionFlag == 'insert' ? 'decr' : 'incr';
						$DecrAttachesArray =  array (
													'Operation'   => 'u',
													'FieldArray'  => array ('ThemeNumAttaches' => array ('Value'=>'', 'Flag'=>$ThemeIncrFlag, 'Type'=>'int')),
													'WhereArray'  => array ('ThemeID' => array ('Value'=>$IdTheme, 'Type'=>'int')),
													'TableName'   => 'themes'
												);

						//добавление в транзакционный массив
						$Transaction->PrepareQueryToRollback ($DecrAttachesArray);
					}
				}

				//(4)Операции с загруженным файлом
				if ($Attach && $AttachActionVar <> 'Save') {
					if (!is_null ($AttachActionFlag)) {
						if ($AttachActionFlag == 'insert' || $AttachActionFlag == 'update') {
							$UploadFileName = sha1 ($IdPost . $Config_AttachSalt) . '.' . $AttachExt;
							$UploadFilePath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $IdTheme . '/' . $UploadFileName;
							//  --если заменить файл - удалить предыдущий (если он существует)
							if ($AttachActionFlag == 'update') {
								$DeletingFile = sha1 ($IdPost . $Config_AttachSalt)  . '.' . $EditPostFileExt;
								$FullDelFilePath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $IdTheme . '/' . $DeletingFile;
								if (file_exists ($FullDelFilePath)) {
									unlink ($FullDelFilePath);
								}
							}

							//  --загрузка файла
							if (!move_uploaded_file ($AttachData, $UploadFilePath)) {
								$Transaction->RollbackTransaction ();
								OBB_Main_Log ('Error while moving attach data', OBB_ERROR_LOG_FILE);
							}
						}
						else if ($AttachActionFlag == 'delete') {
							$DeletingFile = sha1 ($IdPost . $Config_AttachSalt)  . '.' . $EditPostFileExt;
							$FullDelFilePath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $IdTheme . '/' . $DeletingFile;
							if (file_exists ($FullDelFilePath)) {
								unlink ($FullDelFilePath);
							}
						}
					}
				}

				//(5)Обновление даты захода пользователя на данную тему
				Main_UpdateThemeView ($IdTheme);

				//(6)Завершение транзакции
				$Transaction->CommitTransaction ();

				//(7)Кэширование файла
				if (OBB_PUT_MESSAGE_CACHE) {
					//  --разрешены ли смайлы
					$CacheAllowSmiles = $EditThemeSmiles == 'yes' && $AllowSmiles == 'yes';

					//  --путь к кэш-файлу
					$PathToCache = OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $IdTheme . '/post_' . $IdPost;

					//парсинг сообщения
					$bbcode->SetEnableSmileys($CacheAllowSmiles);
					$PostHTML = $bbcode->parse ($PostContentVar);
					//$PostHTML = nl2br ($PostHTML);
					OBB_Cache_PutCacheFile ($PathToCache, $PostHTML);
				}

				//(8)Письмо администратору
				if ($Config_Mail['AdminMail'] == TRUE) {
					//$AddMailer = new OwnBB_Mailer ($Config_Mail);
					$LetterAdminName = $Config_Mail['FromName'];
					$LetterAdminMail = $Config_Mail['FromMail'];

					//МАССИВ ОТПРАВИТЕЛЯ
					$LetterSenderArray = array('address'=>$LetterAdminMail, 'name'=>$LetterAdminName);

					//МАССИВ ПОЛУЧАТЕЛЯ
					$LetterGetterArray = array('address'=>$LetterAdminMail, 'name'=>$LetterAdminName);

					//ТЕМА ПИСЬМА
					$LetterSubject = $ForumLang['AddMail']['EditMailPost'];

					//ТЕЛО ПИСЬМА
					//  --дата письма
					$LetterDate = Main_ConvertDate ($Now, array(), 'd.m.Y, H:i');

					//  --имя форума
					$LetterForumName = $ForumName;

					// --логин пользователя, редактировавшего пост
					$LetterUserName = $_SESSION['UserData']['UserName'];

					//  --ИД ользователя, редактировавшего пост
					$LetterUserID = $_SESSION['UserData']['UserID'];

					//  --тип пользователя, редактировавшего пост
					$LetterUserStatus = $_SESSION['UserData']['UserType'];

					//  --ссылка на файт форума
					$SiteAddress = 'http://' . $HostName . $SelfName;

					//  --имя редактируемой темы
					$LetterThemeName = $EditThemeName;

					//  --ИД редактируемой темы
					$LetterThemeID = $IdTheme;

					//  --получение тела письма
					$FileGetContents = 'AdminMailEditPost';
					$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/' . $FileGetContents . '.html');

					//  --замена элементов тела письма
					$AdminLetter = str_replace ('{forumname}'  , $LetterForumName, $AdminLetter);
					$AdminLetter = str_replace ('{username}'   , $LetterUserName, $AdminLetter);
					$AdminLetter = str_replace ('{userid}'     , $LetterUserID, $AdminLetter);
					$AdminLetter = str_replace ('{userstatus}' , $LetterUserStatus, $AdminLetter);
					$AdminLetter = str_replace ('{userdate}'   , $LetterDate, $AdminLetter);
					$AdminLetter = str_replace ('{themeid}'    , $LetterThemeID, $AdminLetter);
					$AdminLetter = str_replace ('{themename}'  , $LetterThemeName, $AdminLetter);
					$AdminLetter = str_replace ('{postid}'     , $IdPost, $AdminLetter);
					//ТЕЛО ПИСЬМА - КОНЕЦ

					//если отладочный режим - ложим в файл, иначе - отправляем письмо на ящик
					if (OBB_MAIL_DEBUG == false) {
						//$AddMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);/
						OBB_Mail_Send ($LetterSenderArray, $LetterGetterArray, $LetterSubject, $AdminLetter);
					}
					else {
						$FilePutContents = 'Admin_PostEdit' . $IdPost;
						file_put_contents (OBB_ERROR_MAIL_DIR . '/' . $FilePutContents . '.html', $AdminLetter);
					}
				}

				//(9)Редирект
				$_SESSION['Redirect'] = '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&last_post=' . $IdPost . '#pid' . $IdPost;
				$_SESSION['Message']  = 'edit_post';
				$URL = '?action=message';
				OBB_Main_Redirect ($URL);
			}
		}

		//дополнение главной навигации
		$EditAction   = $ForumLang['AddEditMessage'];
		$ThemeHref    = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
		$NavigArray[] = array ($ThemeHref, $EditThemeName);
		$NavigArray[] = array ('', $EditAction);

		//Массив Javascript - НАЧАЛО
		$IsCaptchaFlag = $CaptchaFlag;
		$IsAdminFlag  = $_SESSION['UserData']['UserType'] == 'admin';
		$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>$Label, 'IsCaptcha'=>$IsCaptchaFlag, 'IsAdmin'=>$IsAdminFlag);
		//Массив JavaScript - КОНЕЦ

		//JS-массив
		$JavaScriptArray = array (
								array ('jquery.js', ''),
								array ('functions.js', ''),
								array ('gui.js', ''),
								array ('parameters', $JSParametersArray),
								array ('Edit.js', '')
							);

		//массив BB-редактора
		$Parameters = array ('BBArray'=>array(
										array ('BBType'=>'simple', 'BBSubType'=>'b'),
										array ('BBType'=>'simple', 'BBSubType'=>'i'),
										array ('BBType'=>'simple', 'BBSubType'=>'u'),
										array ('BBType'=>'simple', 'BBSubType'=>'s'),
										array ('BBType'=>'simple', 'BBSubType'=>'quote'),
										array ('BBType'=>'simple', 'BBSubType'=>'code'),
										array ('BBType'=>'simple', 'BBSubType'=>'url'),
										array ('BBType'=>'simple', 'BBSubType'=>'img'),
										array ('BBType'=>'simple', 'BBSubType'=>'list'),
										array ('BBType'=>'simple', 'BBSubType'=>'smile'),
										array ('BBType'=>'simple', 'BBSubType'=>'color')
									),
							'ColorsArray'=>array ('#000000', '#610B38', '#DF0101', '#8A4B08',
												  '#FF8000', '#0B610B', '#01DF01', '#01DFD7',
												  '#08088A', '#2E2EFE', '#7401DF', '#DF01D7',
												  '#585858', '#BDBDBD', '#D0A9F5', '#A9D0F5'),
							'TextareaName'=>'PostContent',
							'TextareaID'=>'PostContent',
							'FormID'=>'EditForm',
							'NumberCols'=>'100',
							'NumberRows'=>'15',
							'BorderColor'=>'#000000',
							'SmilesLink'=>Defence_HTMLSpecials ($SelfName . '/index.php?action=tooling&label=smile&f_id=EditForm&t_id=PostContent'),
							'ImagesPath'=>OBB_IMAGE_DIR . '/bbcodes',
							'TextValue'=>Defence_HTMLSpecials ($PostContentVar));

		//заголовок страницы
		$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['EditPost'], array('forumname'=>$Config_ForumName, 'underforumname'=>$ForumName, 'themename'=>$EditThemeName));

		//верх
		$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

		//вывод ошибок
		if (sizeof ($AddErrorArray) > 0) {
			$ErrorListBlock = '<div class="ErrorBlockDiv">
									<div class="ErrorBlockTitle">' . $ForumLang['AddErrors']['ErrorEditBlockTitle'] . ':</div>';
			foreach ($AddErrorArray as $Key=>$Value) {
				$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
			}
			$ErrorListBlock .= ' </div>
								<div style="height:15px;"><!-- --></div>';
		}
		else {
			$ErrorListBlock = '';
		}
		$MainOutput .= $ErrorListBlock;

		//Капча
		$CaptchaBlock = $CaptchaFlag ? Echo_CaptchaBlock2 ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'EditCapcha') : '';

		//прикрепление
		if ($Attach) {
			//если к сообщению уже присоединен аттач
			if (!OBB_Main_IsEmpty ($EditPostFileName) && !OBB_Main_IsEmpty ($EditPostFileSize)) {
				if ((int)$EditPostFileWidth > 0 && (int)$EditPostFileHeight > 0) {
					$Gabarits = '&nbsp;(<strong>' . $EditPostFileWidth . '</strong>x<strong>' . $EditPostFileHeight . '</strong>)';
				}
				else {
					$FileNameArr = explode ('.', $EditPostFileName);
					$AttachExt   = array_pop ($FileNameArr);
					$Gabarits    = '';
				}
				$IconPart = 'post_atatch.png';
				$AttachIcon = '<img alt="" src="' . OBB_IMAGE_DIR . '/' . $IconPart . '.png" />';
				$AttachName = Defence_HTMLSpecials ($EditPostFileName);

				$SizeArr   = Main_ShowSize ($EditPostFileSize);
				$FileUnit  = $SizeArr['Unit'];
				$FileSize  = $SizeArr['Size'];
				$UnitLoc = $ForumLang['AddAttach'.$FileUnit];
				$AttachSize = '<strong>' . $FileSize . '</strong>&nbsp;' . $UnitLoc;

				$ExistingAttach = ' <div class="ExistingAttachDiv">
										<div class="FileAttachTitle">
											' . $ForumLang['AddAttachCur'] . '
										</div>
										<div class="AttachIconDiv">
											<span style="color:#222; border-bottom:1px dashed #222;">' . $AttachName . '</span>
											&nbsp;
											<span class="AttachInfo">' . $AttachSize . '&nbsp;' . $Gabarits . '</span>
										</div>
									</div>';

				$AttachBlock = '<tr>
									<td style="width:160px;" class="FormTitleTD AttachTitleTD">
										<div class="InputTitle">
											' . $ForumLang['AddAttach'] . '
										</div>
									</td>
									<td class="FormInputTD">
										<div class="MainBlockAttach">
											' . $ExistingAttach . '
											<div style="margin-top:19px;">
												<div class="FileAttachTitle">
													' . $ForumLang['AddAttachEdit'] . '
												</div>
												<div>
													<div class="RadioClass">
														<span><input class="RadioButtonClass" type="radio" checked="checked" name="AttachAction" value="Save" /></span>
														<span>' . $ForumLang['AddAttachSave'] . '</span>
													</div>
													<div class="RadioClass">
														<span><input class="RadioButtonClass" type="radio" name="AttachAction" value="Delete" /></span>
														<span>' . $ForumLang['AddAttachDelete'] . '</span>
													</div>
													<div class="RadioClass">
														<span><input class="RadioButtonClass" type="radio" name="AttachAction" value="Replace" /></span>
														<span>' . $ForumLang['AddAttachReplace'] . '</span>
													</div>
												</div>
											</div>
											<div style="margin-top:10px;" class="AttachFileField">
												<input type="file" name="FileAttach" />
											</div>
											<div class="AttachExtensions">
												' . $ExstensionString . '
											</div>
											<div class="AttachExtensions">
												<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedSize'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_ATTACH_SIZE . '&nbsp;' . $ForumLang['AddAttachb'] . '</span>
											</div>
											<div class="AttachExtensions">
												<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedGabarits'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_IMAGE_WIDTH . '(' . $ForumLang['AddAttachWidth'] . ')&nbsp;<strong>X</strong>&nbsp;' . OBB_MAX_IMAGE_HEIGHT . '(' . $ForumLang['AddAttachHeight'] . ')</span>
											</div>
										</div>
									</td>
								</tr>';
			}
			else {
				$AttachBlock = '<tr>
									<td style="width:160px;" class="FormTitleTD AttachTitleTD">
										<div class="InputTitle">
											' . $ForumLang['AddAttach'] . '
										</div>
									</td>
									<td class="FormInputTD">
										<div class="MainBlockAttach">
											<div style="margin-bottom:10px;" class="FileAttachTitle">
												' . $ForumLang['AddAttachEdit'] . '
											</div>
											<div class="AttachAddAction">
												' . $ForumLang['AddAttachAdd'] . '
											</div>
											<div class="AttachFileField">
												<input type="file" name="FileAttach" />
												<input type="hidden" name="AttachAction" value="Replace" />
											</div>
											<div class="AttachExtensions">
												' . $ExstensionString . '

											</div>
											<div class="AttachExtensions">
												<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedSize'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_ATTACH_SIZE . '&nbsp;' . $ForumLang['AddAttachb'] . '</span>
											</div>
											<div class="AttachExtensions">
												<span style="border-bottom:1px dotted #222;">' . $ForumLang['AddAttachAllowedGabarits'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_IMAGE_WIDTH . '(' . $ForumLang['AddAttachWidth'] . ')&nbsp;<strong>X</strong>&nbsp;' . OBB_MAX_IMAGE_HEIGHT . '(' . $ForumLang['AddAttachHeight'] . ')</span>
											</div>
										</div>
									</td>
								</tr>';
			}
		}
		else {
			$AttachBlock = '';
		}

		//форма редактирования
		$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=add&label=edit&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $IdPost);
		$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName"><span>' . $EditAction . '</span></td>
							</tr>
							<tr class="ForumMainTR">
								<td style="padding:0;">
									<form style="padding:0; margin:0;" id="EditForm" action="' . $ActionURL . '" method="post"' . $Multipart . '>
										<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
											<tr>
												<td style="width:160px; vertical-align:top;" class="FormTitleTD">
													<br /><br />
													<div class="InputTitle">
														' . $ForumLang['AddMessage'] . '<span class="Important">*</span>
													</div>
													<div class="InputDescr">
														(' . OBB_Main_ReplaceSymbols ($ForumLang['AddPostLimit'], array('max'=>OBB_MAX_POST_LENGTH)) . ')
													</div>
												</td>
												<td class="FormInputTD TextareaTD">
													' . Echo_BBEditor ($Parameters) . '
													<div>
														<textarea style="width:95%;" class="InpEl InpTextarea" id="PostContent" name="PostContent" onfocus="initInsertions(\'EditForm\', \'PostContent\');" onkeyup="storeCaret(this);" onclick="storeCaret(this);" onselect="storeCaret(this);" cols="87" rows="17">' . Defence_HTMLSpecials ($PostContentVar) . '</textarea>
													</div>
													<div style="height:15px;"><!-- --></div>
												</td>
											</tr>
											<tr>
												<td style="width:160px;" class="FormTitleTD">
													<div class="InputTitle">
														' . $ForumLang['AddAOptions'] . '
													</div>
												</td>
												<td class="FormInputTD">
													<div>
														<input class="InpCheckbox" type="checkbox" ' . $CheckedAllowSm . ' name="AllowSmiles" />
														<span class="Usual">' . $ForumLang['AddAllowSmiles'] . '</span>
													</div>
													<div style="height:15px;"><!-- --></div>
												</td>
											</tr>
											' . $AttachBlock  . '
											' . $CaptchaBlock . '
											<tr>
												<td style="border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
													<span class="Important">*</span> - ' . $ForumLang['AddImportantFields'] . '
												</td>
											</tr>
											<tr>
												<td colspan="2" class="FormInputTD CenterTD">
													<input type="hidden" name="edit" value="1" />
													<div id="SubmitButtonDiv">
														<input class="InpButton" id="EditSubmit" type="submit" name="Adding"   value="' . $ForumLang['AddEdit'] . '" />
														<input class="InpButton" type="button" name="Adding" value="' . $ForumLang['AddReset'] . '" />
													</div>
												</td>
											</tr>
										</table>
									</form>
								</td>
							</tr>
							<tr>
								<td class="ForumsTableBottom" colspan="1">
									<div><!-- --></div>
								</td>
							</tr>
						</table>';

		//Футер форума
		$MainOutput .= Echo_PrintFoot ();

		//вывод
		Main_ShowStandartHeader ();
		echo $MainOutput;

		break;
	}

	case"del": {
		//проверка, есть ли разрешения для удаления сообщения
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			//  --флаг вывода сообщения об ошибке
			$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
			//  --проверка на полный запрет удаления сообщений
			if (!OBB_DELETE_POSTS) {
				OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}
			//  --проверка на разрешения группы
			if (!$UserGroups_Permissions['DeletePosts']) {
				OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}
		}

		//дополнительные проверки
		//  --запрос на сообщение
		$SQL = 'SELECT
					themes.ThemeName AS TName,
					posts.UserID AS PUserID,
					post_files.PostFileExt AS PFileExt,
					post_files.PostFileName AS PFileName,
					post_files.PostFileSize AS PFileSize,
					post_files.PostFileType AS PFileType
				FROM
					posts
				LEFT JOIN
					post_files ON posts.PostID = post_files.PostID
				LEFT JOIN
					themes ON posts.ThemeID = themes.ThemeID
				WHERE
					posts.ThemeID = \'' . $IdTheme . '\'
					AND
					posts.ForumID = \'' . $IdForum . '\'
					AND
					posts.PostID = \'' . $IdPost . '\'';
		$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		//  --проверка на существование поста
		if (DB_NumRows ($Config_DBType, $Query) < 1) {
			OBB_Main_ShowError ('add_no_post_exist', 'STANDART', $ForumLang['Errors']);
		}

		//  --присвоение
		$DeleteThemeName  = DB_Result ($Config_DBType, $Query, 0, 'TName');
		$DeletePostUserID = intval (DB_Result ($Config_DBType, $Query, 0, 'PUserID'));
		$DeleteFileExt    = DB_Result ($Config_DBType, $Query, 0, 'PFileExt');
		$DeleteFileName   = DB_Result ($Config_DBType, $Query, 0, 'PFileName');
		$DeleteFileSize   = DB_Result ($Config_DBType, $Query, 0, 'PFileSize');

		//флаг, существует ли прикрепление к сообщению
		$AttachExistsFlag = !OBB_Main_IsEmpty ($DeleteFileExt) && !OBB_Main_IsEmpty ($DeleteFileName) && !OBB_Main_IsEmpty ($DeleteFileSize);

		//  --проверка на то, свое ли сообщение редактирует пользователь
		//  --(АДМИНИСТРАТОР МОЖЕТ РЕДАКТИРОВАТЬ ВСЕ)
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			if ($DeletePostUserID <> $_SESSION['UserData']['UserID']) {
				OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
			}
		}

		//  --проверка на то, является ли данное сообщение первым у этой темы
		$SQL = 'SELECT MIN(PostID) FROM posts WHERE ThemeID = \'' . $IdTheme . '\'';
		$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		if (intval(DB_Result($Config_DBType, $Query, 0, 0)) == $IdPost) {
			OBB_Main_ShowError ('add_delete_theme_post', 'STANDART', $ForumLang['Errors']);
		}
		//дополнительные проверки - конец

		//обновление информации о текущем пользователе
		OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'delete_post');

		//если нажато подтверждение удаления - удаление сообщения
		if (isset ($_POST['delete'])) {
			//массив возможных значений кнопки подтверждения удаления
			//$ConfirmDeleteArray = array ($OKDelete, $CancelDelete);
			$ConfirmDelete = isset ($_POST['ConfirmDelete']) && $_POST['ConfirmDelete'] == 'OK' ? $_POST['ConfirmDelete'] : 'NOT_OK';

			//Если нажата кнопка подтверждения удаления
			//if (strcmp($ConfirmDelete,$OKDelete) == 0) {
			if ($ConfirmDelete == 'OK') {
				//(1)Удаление сообщения из таблицы сообщений
				$SQL = 'DELETE FROM posts WHERE PostID = \'' . $IdPost . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				//(2)Удаление из таблицы поискового контента
				$SQL = 'DELETE FROM search_content WHERE PostID = \'' . $IdPost . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				//(3)Удаление из таблицы описания прикреплений
				$SQL = 'DELETE FROM post_files WHERE PostID = \'' . $IdPost . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				//(4)Изменение статистики темы
				$SQL = 'UPDATE themes
						SET ThemeNumPosts = (SELECT COUNT(*) FROM posts WHERE ThemeID = \'' . $IdTheme . '\'),
						ThemeNumAttaches  = (SELECT COUNT(*) FROM post_files LEFT JOIN posts ON posts.PostID = post_files.PostID WHERE posts.ThemeID = \'' . $IdTheme . '\')
						WHERE ThemeID = \'' . $IdTheme . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				//(5)Изменение статистики пользователя - если не гость
				if ($DeletePostUserID > 0) {
					$SQL = 'UPDATE users
							SET UserNumPosts = (SELECT COUNT(*) FROM posts WHERE UserID = \'' . $DeletePostUserID . '\')
							WHERE UserID = \'' . $DeletePostUserID . '\'';
					$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
					if (!$Query) {
						OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
					}
				}

				//(6)Удаление файла прикрепления
				$DeleteAttachPath = OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $IdTheme . '/' . sha1 ($IdPost . $Config_AttachSalt) . '.' . $DeleteFileExt;
				if (file_exists ($DeleteAttachPath)) {
					unlink ($DeleteAttachPath);
				}

				//(7)Удаление кэша поста
				$DeleteCachePath = OBB_CACHE_DIR . '/PostsCache/forum_' . $IdForum . '/theme_' . $IdTheme . '/post_' . $IdPost;
				if (file_exists ($DeleteCachePath)) {
					unlink ($DeleteCachePath);
				}

				//(9)Изменение статистики темы
				OBB_Main_UpdateThemeStatistics ($IdTheme);

				//(10)Изменение статистики форума
				OBB_Main_UpdateForumStatistics ($IdForum);

				//(11)Письмо администратору
				if ($Config_Mail['AdminMail'] == TRUE) {
					//$AddMailer = new OwnBB_Mailer ($Config_Mail);
					$LetterAdminName = $Config_Mail['FromName'];
					$LetterAdminMail = $Config_Mail['FromMail'];

					//массив отправителя
					$LetterSenderArray = array ('address'=>$LetterAdminMail, 'name'=>$LetterAdminName);

					//массив получателя
					$LetterGetterArray = array ('address'=>$LetterAdminMail, 'name'=>$LetterAdminName);

					//тема письма
					$LetterSubject = $ForumLang['AddMail']['DeleteMailPost'];

					//ТЕЛО ПИСЬМА
					//  --date
					$LetterDate = Main_ConvertDate ($Now, array(), 'd.m.Y, H:i');

					//  --имя форума
					$LetterForumName = $ForumName;

					//  --логин пользователя, удалившего сообщение
					$LetterUserName = $_SESSION['UserData']['UserName'];

					//  --ИД пользователя, удалившего сообщение
					$LetterUserID = $_SESSION['UserData']['UserID'];

					//  --статус пользователя, удалившего сообщение
					$LetterUserStatus = $_SESSION['UserData']['UserType'];

					//  ссылка на сайт форума
					$SiteAddress = 'http://' . $HostName . $SelfName;

					//  --название темы, в кот. производится удаление
					$LetterThemeName = $DeleteThemeName;

					//  --ИД темы, в кот. производится удаление
					$LetterThemeID = $IdTheme;

					//  --обработка тела письма
					$FileGetContents = 'AdminMailDeletePost';
					$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/' . $FileGetContents . '.html');
					$AdminLetter = str_replace ('{forumname}'  , $LetterForumName, $AdminLetter);
					$AdminLetter = str_replace ('{username}'   , $LetterUserName, $AdminLetter);
					$AdminLetter = str_replace ('{userid}'     , $LetterUserID, $AdminLetter);
					$AdminLetter = str_replace ('{userstatus}' , $LetterUserStatus, $AdminLetter);
					$AdminLetter = str_replace ('{userdate}'   , $LetterDate, $AdminLetter);
					$AdminLetter = str_replace ('{themeid}'    , $LetterThemeID, $AdminLetter);
					$AdminLetter = str_replace ('{themename}'  , $LetterThemeName, $AdminLetter);
					$AdminLetter = str_replace ('{postid}'     , $IdPost, $AdminLetter);
					//ТЕЛО ПИСЬМА - КОНЕЦ

					//если отладочный режим - ложим в файл, иначе - отправляем письмо на ящик
					if (OBB_MAIL_DEBUG == false) {
						//$AddMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
						OBB_Mail_Send ($LetterSenderArray, $LetterGetterArray, $LetterSubject, $AdminLetter);
					}
					else {
						$FilePutContents = 'Admin_PostDelete' . $IdPost;
						file_put_contents (OBB_ERROR_MAIL_DIR . '/' . $FilePutContents .'.html', $AdminLetter);
					}
				}

				//(12)Редирект
				$_SESSION['Redirect'] = '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
				$_SESSION['Message']  = 'delete_post';
				$URL = '?action=message';
				OBB_Main_Redirect ($URL);

			}
			//Если нажата кнопка отмены удаления - редирект к сообщению
			else {
				OBB_Main_Redirect ('?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&last_post=' . $IdPost . '#pid' . $IdPost);
			}
		}

		//дополнение главной навигации
		$DeleteAction = $ForumLang['AddDeleteMessage'];
		$ThemeHref    = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
		$NavigArray[] = array ($ThemeHref, $DeleteThemeName);
		$NavigArray[] = array ('', $DeleteAction);

		//JS-массив
		$JavaScriptArray = array (
								array ('jquery.js', ''),
								array ('functions.js', '')
							);

		$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['DeletePost'], array('forumname'=>$Config_ForumName, 'underforumname'=>$ForumName, 'themename'=>$DeleteThemeName));

		//верх
		$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);
		//$MainOutput .= '<div><b>' . $DeleteAction . '</b></div>';

		//форма подтверждения удаления сообщения
		$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=add&label=del&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $IdPost);
		$MainOutput .= '<table style="border-color:#f17e7e;" class="MainForumsTable PostsTable" cellpadding="0" cellspacing="0" border="0">
							<tr style="background:url(' . OBB_IMAGE_DIR . '/alert_bg.gif) repeat-x left top #FFF;" class="MainColumnRow">
								<td style="border-bottom:1px solid #FFFFFF;white-space: nowrap;" colspan="1" class="MainColumnName">
									<span>' . $DeleteAction . '</span>
								</td>
							</tr>
							<tr>
								<td style="padding:0;">
									<form style="padding:0; margin:0;" id="EditForm" action="' . $ActionURL . '" method="post">
										<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
											<tr>
												<td style="text-align:center; vertical-align:top; background:#FFCFC7; padding:20px 10px; border-right:1px solid #fff;" class="FormTitleTD">
													<strong>' . $ForumLang['AddDeletePermamently'] . '!</strong>
													<br />
													<strong>' . $ForumLang['AddDeleteConfirm'] . '?</strong>
												</td>
											</tr>
											<tr>
												<td style="border-top:1px solid #9A1600; background:#FFA090;" colspan="1" class="FormInputTD CenterTD">
													<input type="hidden" name="add" value="1" />
													<div id="SubmitButtonDiv">
														<input class="Alert" id="AddSubmit" type="submit" name="ConfirmDelete"   value="OK" />
														<input class="Alert" type="submit" name="ConfirmDelete" value="' . $ForumLang['AddDeleteCancel'] . '" />
														<input type="hidden" name="delete" value="1" />
													</div>
												</td>
											</tr>
										</table>
									</form>
								</td>
							</tr>
							<tr>
								<td class="ForumsTableBottom" colspan="1">
									<div style="background:#AA0000;"><!-- --></div>
								</td>
							</tr>
						</table>';
		/* $MainOutput .= '<br />
						<div>
							<form action="' . $SelfName . '?action=add&label=del&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $IdPost . '" method="POST">
								<div>
									<span style="color:red;">' . $ForumLang['AddDeleteConfirm'] . '?</span>
									<br />
									<input type="submit" name="ConfirmDelete" value="' . $ForumLang['AddDeleteOK'] . '" />
									&nbsp;
									<input type="submit" name="ConfirmDelete" value="' . $ForumLang['AddDeleteCancel'] . '" />
									<input type="hidden" name="delete" value="1" />
								</div>
							</form>
						</div>'; */

		//Футер форума
		$MainOutput .= Echo_PrintFoot ();

		//вывод
		Main_ShowStandartHeader ();
		echo $MainOutput;

		break;
	}
}

//echo microtime () - $a;

?>