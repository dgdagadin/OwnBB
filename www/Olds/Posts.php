<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//гостевой доступ
if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['VisitPosts']) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}

//проверка на корректность ид форума/темы
if (
	!isset ($_GET['id_forum'])       ||
	!ctype_digit ($_GET['id_forum']) ||
	!isset ($_GET['id_theme'])       ||
	!ctype_digit ($_GET['id_theme'])
)
{
	OBB_Main_ShowError ('posts_wrong_forum_or_theme', 'STANDART', $ForumLang['Errors']);
}

//проверка last_post - если есть
$LastPost = isset ($_GET['last_post']) && ctype_digit ($_GET['last_post']) && $_GET['last_post'] <> '' ? intval ($_GET['last_post']) : '';

//присвоение
$IdForum = intval ($_GET['id_forum']);
$IdTheme = intval ($_GET['id_theme']);

//проверка сущ-вание темы и/или в форуме + информация
if ($LastPost <> '') {
	$PostFrom   = ', posts ';
	$PostsWhere = ' AND themes.ThemeID = posts.ThemeID AND forums_list.ForumID = posts.ForumID AND posts.PostID = \'' . $LastPost . '\' ';
}
else {
	$PostFrom = $PostsWhere = '';
}
$CheckThemeSQL = 'SELECT
						forums_list.ForumName as FName,
						forums_list.ForumGuestView as GView,
						forums_list.ForumMinStatus as FMinStatus,
						forums_list.ForumBlock as FBlock,
						themes.ThemeName as TName,
						themes.ThemeAllowSmiles as TAllSmls,
						themes.ThemeBlock as TBlock
				FROM
						forums_list, themes' . $PostFrom . '
			    WHERE
					forums_list.ForumID = \'' . $IdForum . '\'
					AND
					themes.ThemeID = \'' . $IdTheme . '\'' . $PostsWhere;
$CheckThemeQuery = DB_Query ($Config_DBType, $CheckThemeSQL, $ForumConnection);
if (!$CheckThemeQuery) {
	OBB_Main_Log ($CheckThemeSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$IsGoodRequest = DB_NumRows ($Config_DBType, $CheckThemeQuery);
if ($IsGoodRequest < 1) {
	OBB_Main_Redirect ('?action=forumview&id_forum=' . $IdForum);
}

//присвоение
$InfoRow = DB_FetchAssoc ($Config_DBType, $CheckThemeQuery);
$FName      = $InfoRow['FName'];
$GView      = $InfoRow['GView'];
$FMinStatus = $InfoRow['FMinStatus'];
$FBlock     = $InfoRow['FBlock'];
$TAllSmls   = $InfoRow['TAllSmls'];
$TBlock     = $InfoRow['TBlock'];
$TName      = $InfoRow['TName'];

//проверка на гостевой просмотр форума
if ($_SESSION['UserData']['UserType'] == 'guest' && $GView == 'no') {
	OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
}

//проверка по группе
if (Access_CheckUserGroup ($FMinStatus) == FALSE && $_SESSION['UserData']['UserType'] <> 'admin') {
	OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
}

//обновление даты захода пользователя на данную тему
Main_UpdateThemeView ($IdTheme);

//увеличиваем к-во просмотров на 1
$UpdateNumViewsSQL = 'UPDATE themes SET ThemeNumViews=ThemeNumViews+1 WHERE ThemeID = \'' . $IdTheme . '\'';
$UpdateNumViewsQuery = DB_Query ($Config_DBType, $UpdateNumViewsSQL, $ForumConnection);
if (!$UpdateNumViewsQuery) {
	OBB_Main_Log ($UpdateNumViewsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//подключения
include (OBB_KERNEL_DIR . '/PageLib.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');
include (OBB_KERNEL_DIR . '/CacheFunctions.php');

//Подключение файлов языка
include (OBB_LANGUAGE_DIR . '/Posts_' . $Config_Lang . '.php');

//массив перевода даты
$DateArray = $ForumLang['DateArray'];

//переменная "сейчас"
$Now = time ();

//Функция обновления онлайн-информации о пользователе/госте
$CurAction = 'theme_page';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//Главная переменнвя вывода
$MainOutput = '';

//Формируем массив главной навигации
$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array ($SelfName . '?action=forumview&id_forum=' . $IdForum, $FName),
					array ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme, $TName)
				);

//Массив Javascript - НАЧАЛО
$JSIsCaptcha = $_SESSION['UserData']['UserType'] <> 'admin' && (OBB_CAPTCHA && $UserGroups_Permissions['CaptchaAddPost']);
$JSIsGuest   = $_SESSION['UserData']['UserType'] == 'guest';
$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'', 'IsCaptcha'=>$JSIsCaptcha, 'IsGuest'=>$JSIsGuest);
//Массив Javascript - КОНЕЦ

//JS-массив
$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Posts.js', '')
					);

//залоговок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Posts'], array('forumname'=>$Config_ForumName,'underforumname'=>$FName, 'themename'=>$TName));

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'ThemeDelimiterDiv');

//кнопки "Ответить" и "Новая тема"
//  --флаги
$AddThemeFlag = $_SESSION['UserData']['UserType'] == 'admin' || (OBB_ADD_THEMES && $UserGroups_Permissions['AddThemes']) ? TRUE : false;
$AddPostFlag  = $_SESSION['UserData']['UserType'] == 'admin' || (OBB_ADD_POSTS  && $UserGroups_Permissions['AddPosts'])  ? TRUE : false;
$ForumIsBlocked = $FBlock == 'yes' ? TRUE : false;
$ThemeIsBlocked = $FBlock == 'yes' || $TBlock == 'yes' ? TRUE : false;

//  --кнопка "Новая тема"
$NewBlckButton = ' <div class="BlockThemePost BlockTheme">
						<div class="BlockThemeIcon">
							<span>' . $ForumLang['PostsBlockForum'] . '</span>
						</div>
					</div>';

$URL = $SelfName . '?action=add&label=theme&id_forum=' . $IdForum;
$URL = Defence_HTMLSpecials ($URL);
$NewThmButton = '  <div style="float:right;" class="AddThemePost AddTheme">
						<div class="AddThemeIcon">
							<a href="' . $URL . '">
								<span>' . $ForumLang['PostsAddTheme'] . '</span>
							</a>
						</div>
					</div>';

if ($AddThemeFlag) {
	if ($ForumIsBlocked) {
		$ThemeButtonWidth = '135';
		$NewThemeButton = $NewBlckButton;
	}
	else {
		$ThemeButtonWidth = '126';
		$NewThemeButton = $NewThmButton;
	}
}
else {
	$NewThemeButton = '';
}

//  -- кнопка "Ответить"
$BlockAnswerButton = '<div style="float:right;" class="BlockThemePost BlockPost">
						<div class="BlockPostIcon">
							<span>' . $ForumLang['PostsBlockTheme'] . '</span>
						</div>
					  </div>';

$AnswerURL = $SelfName . '?action=add&label=add&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
$AnswerURL = Defence_HTMLSpecials ($AnswerURL);
$NewAnswerButton = '<div style="float:right;" class="AddThemePost AddPost">
						<div class="AddPostIcon">
							<a href="' . $AnswerURL . '">
								<span>' . $ForumLang['PostsAddPost'] . '</span>
							</a>
						</div>
					</div>';

if ($AddPostFlag) {
	if ($ThemeIsBlocked) {
		$PostButtonWidth = '138';
		$NewPostButton = $BlockAnswerButton;
	}
	else {
		$PostButtonWidth = '110';
		$NewPostButton = $NewAnswerButton;
	}
}
else {
	$NewPostButton = '';
}

// --объединение "Новая тема" и "Ответить"
if (Main_Strlen ($NewThemeButton) == 0 && Main_Strlen ($NewPostButton) == 0) {
	$AddThemePost = '<td style="width:5px;">&nbsp;</td>';
}
else {
	if (Main_Strlen ($NewThemeButton) > 0) {
		$AddThemeCell = '<td style="width:' . $ThemeButtonWidth . 'px;" class="Right">' . $NewThemeButton . '</td>';
	}
	else {
		$AddThemeCell = '';
	}
	if (Main_Strlen ($NewPostButton) > 0) {
		$AddPostCell = '<td style="width:' . $PostButtonWidth . 'px;" class="Right">'  . $NewPostButton  . '</td>';
	}
	else {
		$AddPostCell = '';
	}
	$AddThemePost = $AddThemeCell . $AddPostCell;
}
//кнопки "Ответить" и "Новая тема" - КОНЕЦ

//постраничная навигация
$GetNumPostsSQL = 'SELECT COUNT(*) FROM posts WHERE ThemeID = \'' . $IdTheme . '\'';
$GetNumPostsQuery = DB_Query ($Config_DBType, $GetNumPostsSQL, $ForumConnection);
if (!$GetNumPostsQuery) {
	OBB_Main_Log ($GetNumPostsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$ThemeNumPosts = DB_Result ($Config_DBType, $GetNumPostsQuery, 0, 0);
$NumPages = $ThemeNumPosts > 0 ? ceil ($ThemeNumPosts / OBB_NUM_POSTS_PER_PAGE) : 1;
if (isset ($_GET['page'])) {
	$CurrentPage = !isset ($_GET['page']) || !ctype_digit ($_GET['page']) ? 1 : intval ($_GET['page']);
	if ($CurrentPage < 1) {
		$CurrentPage = 1;
	}
	else if ($CurrentPage > $NumPages) {
		$CurrentPage = $NumPages;
	}
}
else if ($LastPost <> '') {
	//ИДЕЯ ВЗЯТА ИЗ исходников fluxbb версии 1.4.2
	$SQL = 'SELECT PostID FROM posts WHERE ThemeID = \'' . $IdTheme . '\' ORDER BY PostDate';
	$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	$NumPosts = DB_NumRows ($Config_DBType, $Query);

	for ($i = 0; $i < $NumPosts; $i++) {
		if (DB_Result ($Config_DBType, $Query, $i, 0) == $LastPost) {
			break;
		}
	}
	$i++;
	$CurrentPage = ceil ($i/OBB_NUM_POSTS_PER_PAGE);
	//ИДЕЯ ВЗЯТА ИЗ исходников fluxbb версии 1.4.2 - окончание
}
else {
	$CurrentPage = 1;
}

if ($ThemeNumPosts > 0) {
	$PageURL = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme;
	$PageList = PL_PageList ($NumPages, $CurrentPage, $PageURL, 'page', '1', '1', '2', '2');
}
else {
	$PageList = '&nbsp;';
}

//объединение кнопки темы и страниц
$ThemePostPage = '<table class="AddAndPagesTable" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td class="Left">
							<div>' . $PageList . '</div>
						</td>
						' . $AddThemePost . '
					</tr>
				</table>';
$MainOutput .= $ThemePostPage;

//разделитель
$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

///////ФУНКЦИИ ПАРСИНГА//////
$bbcode = OBB_BBCode_GetParseStandart ();
$signature_bb = OBB_BBCode_GetParseSign ();
///////ФУНКЦИИ ПАРСИНГА//////

//текст в случае отсутствия доступа к ссылкам и аттачам
//  --массив замены
$ReplaceArray = array ('log'=>'http://' . $HostName . $SelfName . '?action=login',
					   'reg'=>'http://' . $HostName . $SelfName . '?action=registration');

//  --ссылки
$ReplaceText = $_SESSION['UserData']['UserType'] == 'guest' ? $ForumLang['PostsURLsLoginOrRegister'] : $ForumLang['PostsURLsNoAccess'];
$URLsReplaceText = '<font color="red"><small>' . OBB_Main_ReplaceSymbols ($ReplaceText, $ReplaceArray) . '</small></font>';

//  --прикрепления
$ReplaceText = $_SESSION['UserData']['UserType'] == 'guest' ? $ForumLang['PostsAttachesLoginOrRegister'] : $ForumLang['PostsAttachesNoAccess'];
$AttachesReplaceText = '<font color="red">' . OBB_Main_ReplaceSymbols ($ReplaceText, $ReplaceArray) . '</font>';

//массив подписей пользователей (во избежания парсинга одной и той же подписи)
$UsersSignatureCache = array ();

//основное
if ($ThemeNumPosts > 0) {
	//превеликий запрос сообщений )))))
	$ThemePostsSQL = 'SELECT
							posts.PostID AS PID,
							posts.PostText AS PText,
							posts.PostDate AS PDate,
							posts.PostEditDate AS PEditDate,
							posts.PostSmilesAllow AS PSmilesAllow,
							posts.UserID AS PUserID,
							posts.UserName AS PUserName,
							users.UserSex AS PUserSex,
							users.UserRegDate AS PUserRegDate,
							users.UserAvatar AS PUserAvatar,
							users.UserSlogan AS PUserSlogan,
							users.UserNumPosts AS PUserNumPosts,
							users.UserNumThemes AS PUserNumThemes,
							users.UserOtherMail AS PUserOtherMail,
							users.GroupID AS PGroupID,
							user_activity.UserIsOnline AS PUserIsOnline,
							post_files.PostFileName AS PFileName,
							post_files.PostFileSize AS PFileSize,
							post_files.PostFileType AS PFileType,
							post_files.PostFileExt AS PFileExt,
							post_files.PostFileHeight AS PWidth,
							post_files.PostFileWidth AS PHeight,
							post_files.PostFileNumViews as PFilesNumViews
					FROM
							posts
					LEFT JOIN
							users ON (posts.UserID = users.UserID)
					LEFT JOIN
							user_activity ON (posts.UserID = user_activity.UserID)
					LEFT JOIN
							post_files ON (posts.PostID = post_files.PostID)
					WHERE
							posts.ThemeID = \'' . $IdTheme . '\'
					ORDER BY
							posts.PostDate ASC, posts.PostID ASC
					LIMIT
							' . OBB_NUM_POSTS_PER_PAGE * ($CurrentPage - 1) . ', ' . OBB_NUM_POSTS_PER_PAGE;
	$ThemePostsQuery = DB_Query ($Config_DBType, $ThemePostsSQL, $ForumConnection);
	if (!$ThemePostsQuery) {
		OBB_Main_Log ($ThemePostsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	//Вычисление порядкового номера сообщений
	$PostNum = (($CurrentPage-1)*OBB_NUM_POSTS_PER_PAGE);

	//сообшения темы - заголовки
	$MainOutput .= '<table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td style="white-space: nowrap;" colspan="2" class="MainColumnName">
								<span>' . Defence_HTMLSpecials ($TName) . '</span>
							</td>
						</tr>
						<tr>
							<td class="ForumCharter" style="width:215px; border-top:1px solid #FFFFFF;">
								<span class="ThemesTitle">' . $ForumLang['PostsAuthorTitle'] . '</span>
							</td>
							<td class="ForumCharter" style="text-align:center; border-top:1px solid #FFFFFF;">
								' . $ForumLang['PostsPostTitle'] . '
							</td>
						</tr>';

	while ($PostRow = DB_FetchAssoc ($Config_DBType, $ThemePostsQuery)) {
		//присвоение
		$PID            = $PostRow['PID'];
		$PText          = $PostRow['PText'];
		$PDate          = $PostRow['PDate'];
		$PEditDate      = $PostRow['PEditDate'];
		$PSmilesAllow   = $PostRow['PSmilesAllow'];
		$PUserID        = $PostRow['PUserID'];
		$PUserName      = $PostRow['PUserName'];
		$PUserSex       = $PostRow['PUserSex'];
		$PUserRegDate   = $PostRow['PUserRegDate'];
		$PUserAvatar    = $PostRow['PUserAvatar'];
		$PUserSlogan    = $PostRow['PUserSlogan'];
		$PUserNumPosts  = $PostRow['PUserNumPosts'];
		$PUserNumThemes = $PostRow['PUserNumThemes'];
		$PGroupID       = $PostRow['PGroupID'];
		$PUserIsOnline  = $PostRow['PUserIsOnline'];
		$PUserOtherMail = $PostRow['PUserOtherMail'];
		$PFileName      = $PostRow['PFileName'];
		$PFileSize      = $PostRow['PFileSize'];
		$PFileType      = $PostRow['PFileType'];
		$PFileExt       = $PostRow['PFileExt'];
		$PWidth         = $PostRow['PWidth'];
		$PHeight        = $PostRow['PHeight'];
		$PFileNumViews  = $PostRow['PFilesNumViews'];

		//преобразование
		$PID      = intval ($PID);
		$PUserID  = intval ($PUserID);
		$PGroupID = intval ($PGroupID);

		$PostNum++;

		//1.Имя автора
		/* if ($PUserID > '0') {
			$UserAStart = '<a href="' . $SelfName . '?action=profile&user_id=' . $PUserID . '">';
			$UserAEnd   = '</a>';
		}
		else {
			$UserAStart = $UserAEnd = '';
		}
		$UserName = $UserAStart . Defence_HTMLSpecials ($PUserName) . $UserAEnd; */
		if (!$ThemeIsBlocked && ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHORT_ADD_FORM && $UserGroups_Permissions['AddPosts']))) {
			$Onclick = ' onclick="javascript:addUserBold(\'ShortAddForm\', \'PostContent\', \'' . $PUserName . '\');"';
			$ShortAddHref = '#Add';
		}
		else {
			$Onclick = '';
			$ShortAddHref = 'javascript:void(0);';
		}
		$UserName = '<a href="' . $ShortAddHref . '"' . $Onclick . '>' . Defence_HTMLSpecials ($PUserName) . '</a>';

		//3.Онлайн/оффлайн
		if ($PUserID > '0') {
			$OnlineLabel = $PUserIsOnline == 'yes' ? 'online.png' : 'offline.png';
			$OnlineTitle = $PUserIsOnline == 'yes' ? $ForumLang['PostsUserOnline'] : $ForumLang['PostsUserOffline'];
		}
		else {
			$OnlineLabel = 'online_dummy.gif';
			$OnlineTitle = '';
			$UserIsOnline = '';
		}
		$UserIsOnline = '<img alt="" class="PostsUserOnOff" title="' . $OnlineTitle . '" src="' . OBB_IMAGE_DIR . '/' . $OnlineLabel . '" />';

		//Общая строка с именем
		$ComUserString = '<div class="PostsLeftTop">'
							. $UserIsOnline
							. $UserName    .
						'</div>';

		//4.Дата сообщения
		$PostDateSpan = '<div class="PostDate">' . Main_ConvertDate ($PDate, $DateArray) . '</div>';

		//5.Номер сообщения
		$CurPostHref = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&last_post=' . $PID . '#pid' . $PID;
		$PostNumberSpan = '<div class="PostLink">
								<a name="pid' . $PID . '" href="' . $CurPostHref . '">' . $ForumLang['PostsPost'] . '#' . $PostNum . '</a>
						   </div>';

		//Объединение даты и номера сообщения
		$PostDateDiv = $PostDateSpan . $PostNumberSpan;

		if ($PUserID > '0') {
			$UserIsDeleted = '';

			//6.Аватар
			//  --если он не пустой
			//  --если он допустим
			$IsUserAdminGroup = OBB_Main_IsAdminGroup ($PGroupID);
			$CurrentUserAvatarPermissions = OBB_Main_GetSpecifiedPermission ($PGroupID, 'AvatarsAllowed');
			if ($IsUserAdminGroup || (!$IsUserAdminGroup && $CurrentUserAvatarPermissions)) {
				$AvatarPermissions = TRUE;
			}
			else {
				$AvatarPermissions = false;
			}
			if (OBB_ALLOW_AVATARS && !OBB_Main_IsEmpty ($PUserAvatar) && $AvatarPermissions) {
				$UserAvatar = '<img class="AvatarImg" alt="" title="" src="' . OBB_AVATAR_DIR . '/' . $PUserID . '.' . $PUserAvatar . '" border="0" />';
			}
			else {
				$UserAvatar = '';
			}

			//7.Статус
			$UserStatusPosts = $IsUserAdminGroup ? OBB_MAX_STATUS*OBB_NUM_POSTS_IN_STATUS : $PUserNumPosts;
			$StatusImage = Echo_GetUserStatus ($UserStatusPosts, OBB_NUM_POSTS_IN_STATUS, OBB_MAX_STATUS, OBB_IMAGE_DIR);
			$UserStatus  = '<div class="UserStatus">
								' . $StatusImage . '
							</div>';

			//8.Группа пользователя
			$UserGroup = '<div class="UserGroupName" style="color:#' . $UserGroups_Array[$PGroupID]['Color'] . ';">
							' . $UserGroups_Array[$PGroupID]['Description'] . '
						  </div>';

			//9.Количество сообщений
			$NumUserPosts = '<div class="UserNumPosts">
								<span class="NumPostsTitle">' . $ForumLang['PostsNumPosts'] . ':</span>
								<span class="NumPosts">' . $PUserNumPosts . '</span>
							 </div>';

			//10.Количество тем
			$NumUserThemes ='<div class="UserNumThemes">
								<span class="NumThemesTitle">' . $ForumLang['PostsNumThemes'] . ':</span>
								<span class="NumThemes">' . $PUserNumThemes . '</span>
							 </div>';

			//10.Пол
			$SexTitle = $PUserSex == 'male' ? $ForumLang['PostsMale'] : $ForumLang['PostsFemale'];
			$UserSex = '<div class="UserSex">
							<span class="UserSexTitleSpan">' . $ForumLang['PostsSex'] . ':</span>
							<span class="UserSexSpan">' . $SexTitle . '</span>
						</div>';

			//11.Кнопка "Написать сообщение по почте"
			//  --если админ
			//  --если разрешено, если разрешено в общем, если разрешено самим пользователем
			$MailFlag = false;
			if ($_SESSION['UserData']['UserType'] <> 'admin') {
				if (!OBB_MAIL_ALLOWED || !$UserGroups_Permissions['MailSendAllowed'] || $PUserOtherMail == 'no' || $_SESSION['UserData']['UserType'] == 'guest') {
					$MailFlag = false;
				}
				else {
					$MailFlag = TRUE;
				}
			}
			else {
				$MailFlag = TRUE;
			}
			if ($MailFlag) {
				$MailButton = '<a title="' . $ForumLang['PostsSendMail'] . '" href="' . $SelfName . '?action=tooling&label=mail&id_user=' . $PUserID . '">
									<img src="' . OBB_IMAGE_DIR . '/send_mail.png" border="0" />
								</a>';
			}
			else {
				$MailButton = '';
			}

			//12.Личное сообщение - В РАЗРАБОТКЕ
			if ($_SESSION['UserData']['UserType'] <> 'guest') {
				$PrivateButton = '<a title="' . $ForumLang['PostsSendMessage'] . '" href="' . $SelfName . '?action=privatemsg&id_user=' . $PUserID . '">
									  <img src="' . OBB_IMAGE_DIR . '/message.png" border="0" />
								  </a>';
			}
			else {
				$PrivateButton = '';
			}

			//объединение личного сообщения и почтового сообщения
			$MessageString = '<div>' . $MailButton . '&nbsp;' . $PrivateButton . '</div>';

			//13.Девиз
			if (!OBB_Main_IsEmpty ($PUserSlogan)) {
				if (!isset ($UsersSignatureCache[$PUserID])) {
					if (OBB_BB_PARSE_ALLOWED) {
						$UserSlogan = $signature_bb->parse ($PUserSlogan);
					}
					else {
						$UserSlogan = $PUserSlogan;
					}
					$UserSlogan = nl2br ($UserSlogan);
					$UsersSignatureCache[$PUserID] = $UserSlogan;
				}
				else {
					$UserSlogan = $UsersSignatureCache[$PUserID];
				}

				$SloganDiv = '<div>
								<div>' . Echo_SignatureSeparator () . '</div>
									<div>
										<span>
											<small>' . $UserSlogan . '</small>
										</span>
									</div>
							</div>';
			}
			else {
				$SloganDiv = '';
			}
		}
		else {
		    $UserAvatar    = '';
			$UserStatus    = '';
			$NumUserPosts  = '';
			$UserSexImg    = '';
			$MessageString = '';
			$SloganDiv     = '';
			if ($PUserID == 0) {
				$UserGroup = '<div style="" class="UserGroupName">' . $ForumLang['PostsGuest'] . '</div>';
			}
			else {
				$UserGroup = '<div><b>' . $ForumLang['PostsIsDeleted'] . '</b></div>';
			}
		}

        //14.Кнопка "Редактирвоать сообщение"
		//  --если админ
		//  --если разрешено, разрешено пользователю, его сообщение
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_EDIT_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['EditPosts'] &&
				$_SESSION['UserData']['UserID'] == intval ($PUserID)
			)
		) {
			$EditButton = '<a title="' . $ForumLang['PostsEditPost'] . '" href="' . $SelfName . '?action=add&label=edit&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID . '">
							   <img src="' . OBB_IMAGE_DIR . '/edit_post.png" border="0" />
						   </a>';
		}
		else {
			$EditButton = '';
		}

		//15.Кнопка "Удалить сообщение"
		//  --если админ
		//  --если разрешено, разрешено пользователю, его сообщение
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_DELETE_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['DeletePosts'] &&
				$_SESSION['UserData']['UserID'] == intval ($PUserID)
			)
		) {
			$DeleteButton = '<a title="' . $ForumLang['PostsDeletePost'] . '" href="' . $SelfName . '?action=add&label=del&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID . '">
								<img src="' . OBB_IMAGE_DIR . '/delete_post.png" border="0" />
							 </a>';
		}
		else {
			$DeleteButton = '';
		}

		//16.Цитирование - кнопка
		if (
				!$ThemeIsBlocked &&
				(
					$_SESSION['UserData']['UserType'] == 'admin' ||
					(
						OBB_ADD_POSTS &&
						$UserGroups_Permissions['AddPosts']
					)
				)
		) {
			$QuoteButton = '<a title="' . $ForumLang['PostsQuotePost'] . '" href="' . $SelfName . '?action=add&label=post&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_quote=' . $PID . '">
								<img src="' . OBB_IMAGE_DIR . '/quote.gif" border="0" />
							</a>';
		}
		else {
			$QuoteButton = '';
		}

		//17.Кнопка "Вверх"
		$ToTopButton = '<a href="javascript:void(0);" onclick="scrollToTop();">
							<img src="' . OBB_IMAGE_DIR . '/top.gif" border="0" />
						</a>';

		//18.Строка даты редактирования сообщения
		if (!OBB_Main_IsEmpty ($PEditDate)) {
			$EditedDate = '<span><i><b>' . $ForumLang['PostsEdited'] . '</b>: ' . Main_ConvertDate ($PEditDate, $DateArray, 'd.m.Y, H:i') . '</i></span>';
		}
		else {
			$EditedDate = '';
		}

		//19.Прикрепление
		//  --если админ
		//  --если разрешено + есть права на скачивание
		if (!OBB_Main_IsEmpty ($PFileName) && ($_SESSION['UserData']['UserType'] == 'admin' || ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_ALLOW_ATTACHES))) {
			$FileName = Defence_HTMLSpecials ($PFileName);
			$SizeArr  = Main_ShowSize ($PFileSize);
			$FileUnit = $SizeArr['Unit'];
			$FileSize = $SizeArr['Size'];
			$UnitLoc  = $ForumLang['Posts'.$FileUnit];
			$PWidth   = intval ($PWidth);
			$PHeight  = intval ($PHeight);

			$FileAttach = '';

			//если прикрепление - картинка
			$AttachIsImage = $PWidth > 0 && $PHeight > 0;

			//имеется ли доступ к прикреплению
			$AttachAllowFlag = $_SESSION['UserData']['UserType'] == 'admin' || ($UserGroups_Permissions['AttachesDownload']);

			if (!$AttachAllowFlag) {
				$AttachImage = $AttachIsImage ? 'img' : $PFileExt;
				$FileImage   = '<img title="' . $ForumLang['PostsAttach'] . '" src="' . OBB_IMAGE_DIR . '/' . $AttachImage . '.png" border="0" />';
				$FileAttach .= $FileImage . '&nbsp;' . $FileName . '&nbsp;[' . $AttachesReplaceText . ']';
			}
			else {
				if ($AttachIsImage) {
					$TrueImageName = sha1 ($PID . $Config_AttachSalt);
					$TrueImageName.= '.' . $PFileExt;
					$FileHref = '<div>
									<img src=' . OBB_ATTACH_DIR . '/forum_' . $IdForum . '/theme_' . $IdTheme . '/' . $TrueImageName . ' border="0" />
								</div>';

					$AttNumViews = '';
					$FileImage = '';
					$FSizeString = '';
				}
				else {
					$AttachImage = $PFileExt;
					$AttNumViews = '<span><b>' . $ForumLang['PostsFileViews'] . ':</b>&nbsp;' . $PFileNumViews . '</span>';
					$FileHref    = '<span><a target="_blank" href="' . $SelfName . '?action=download&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID . '">' . $FileName . '</a></span>';
					$FileImage   = '<span><img title="' . $ForumLang['PostsAttach'] . '" src="' . OBB_IMAGE_DIR . '/' . $AttachImage . '.png" border="0" /></span>';
					$FSizeString = '<span>(<span>' . $FileSize . '</span>&nbsp;<span>' . $UnitLoc . '</span>, ' . $AttNumViews . ')</span>';
				}

				$FileAttach = $FileImage . $FileHref . $FSizeString;
			}

			$AttachFile = '<fieldset>
								<legend>' . $ForumLang['PostsAttach'] . '</legend>
							   <div>' .
									$FileAttach . '
							   </div>
						   </fieldset>';
		}
		else {
			$AttachFile = '';
		}

		//21.Сообщение
		$AllowSmiles = OBB_ALLOW_SMILES && $PSmilesAllow == 'yes' ? true : false;
		$PostArray   = array ('MsgID'=>$PID,'MsgText'=>$PText);
		$CachePath   = 'PostsCache/forum_' . $IdForum . '/theme_' . $IdTheme;
		$PostText    = OBB_Main_ShowPost ($PostArray, $CachePath, $AllowSmiles);
		if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['LinksAllowed']) {

			$PostText = OBB_Main_DisallowURLs ($PostText, $URLsReplaceText);
		}
		$PostDiv     = '<div>' . $PostText . '</div>';

		//22.Кнопка "Пожаловаться"
		//  --если админ
		//  --если разрешено, разрешено пользователю, его сообщение, если не гость
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_REPORT_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['ReportPosts']
			)
		) {
			$ReportButton = '<a title="' . $ForumLang['PostsReportPost'] . '" href="' . $SelfName . '?action=tooling&label=report&id_post=' . $PID . '">
								<img src="' . OBB_IMAGE_DIR . '/report_post.png" border="0" />
							</a>';
		}
		else {
			$ReportButton = '';
		}

		//Строка низ право
		$ExtraString = '<div>' . $EditedDate . '<div style="float:right;">' . $DeleteButton . '&nbsp;' . $EditButton . '&nbsp;' . $ReportButton . '&nbsp;' . $QuoteButton . '&nbsp;' . $ToTopButton . '</div></div>';

		//Колонка сообщения
		$MainBlock = '<div style="margin:5px 0 0 5px;">' . $PostDiv . $AttachFile . $SloganDiv . '</div>';

		//Ячейка инфо об авторе
		$AuthorPart = $UserGroup . $UserAvatar  . $UserStatus . $UserSex . $NumUserThemes . $NumUserPosts;

		$MainOutput .= '<tr class="ForumMainTR">
							<td class="PostsUserName">' . $ComUserString . '</td>
							<td class="PostsDateLink">' . $PostDateDiv . '</td>
						</tr>
							<td class="PostsUserInfo">' . $AuthorPart . '</td>
							<td class="PostsMainBlock">' . $MainBlock . '</td>
						</tr>
						</tr>
							<td class="PostsUserProfile">' . $MessageString . '</td>
							<td class="PostsPanelString">' . $ExtraString . '</td>
						</tr>
						<tr>
							<td colspan=2 class="ForumsTableBottom">
								<div style="height:10px;"><!-- --></div>
							</td>
						</tr>';
	}

	/* $MainOutput .= '	<tr>
							<td class="ForumsTableBottom" colspan="2">
								<div><!-- --></div>
							</td>
						</tr> */
	$MainOutput .= '</table>';

	$MainOutput .= '';
}
else {
	$MainOutput .= $ForumLang['PostsNo'];
}

//разделитель
$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

$MainOutput .= $ThemePostPage;

/* //быстрое добавление ответа
//  --если админ
//  --если разрешено, есть полномочия
if (!$ThemeIsBlocked && ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHORT_ADD_FORM && $UserGroups_Permissions['AddPosts']))) {
	//капча добавления ответа
	if ($_SESSION['UserData']['UserType'] <> 'admin' && (OBB_CAPTCHA && $UserGroups_Permissions['CaptchaAddPost'])) {
		$CaptchaTitle = $ForumLang['CaptchaTitle'];
		$EnterCaptcha = $ForumLang['EnterCaptcha'];
		$CaptchaBlock = Echo_CaptchaBlock ($CaptchaTitle, $EnterCaptcha, 'ShortAddCaptcha');
	}
	else {
		$CaptchaBlock = '';
	}

	if ($_SESSION['UserData']['UserType'] == 'guest') {
		$GuestLogin = '<div>' . $ForumLang['PostsGuestName'] . '<br /><input id="GuestLoginID" type="text" name="GuestLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value=""></div><br />';
		$GuestMail  = '<div>' . $ForumLang['PostsGuestMail'] . '<br /><input id="GuestMailID"  type="text" name="GuestMail" maxlength="'  . OBB_MAX_MAIL_LENGTH  . '" value=""></div><br />';
	}
	else {
		$GuestLogin = '';
		$GuestMail  = '';
	}

	//массив BB-редактора
	$Parameters = array ('BBArray'=>array(
									array ('BBType'=>'simple', 'BBSubType'=>'b'),
									array ('BBType'=>'simple', 'BBSubType'=>'i'),
									array ('BBType'=>'simple', 'BBSubType'=>'u'),
									array ('BBType'=>'simple', 'BBSubType'=>'s'),
									array ('BBType'=>'simple', 'BBSubType'=>'smile')
								),
						'TextareaName'=>'PostContent',
						'TextareaID'=>'PostContent',
						'FormID'=>'ShortAddForm',
						'NumberCols'=>'100',
						'NumberRows'=>'15',
						'BorderColor'=>'#000000',
						'SmilesLink'=>$SelfName . '/index.php?action=tooling&label=smile&f_id=ShortAddForm&t_id=PostContent',
						'ImagesPath'=>OBB_IMAGE_DIR . '/bbcodes');

	$MainOutput .= '<tr>
						<td>
							<div>
								<b>' . $ForumLang['PostsFastPost'] . '</b>
							</div>
							<form id="ShortAddForm" name="ShortAddForm" action="' . $SelfName . '?action=add&label=post&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '" method="POST">
								<div style="position:relative;">
									<fieldset>
										<legend>' . $ForumLang['PostsPostTitle'] . '</legend>
										<br />
										' . $GuestLogin . '
										' . $GuestMail  . '
										<br />
											<a name="Add"></a>
											' . $ForumLang['PostsMessage'] . '
										' . Echo_BBEditor ($Parameters) . '
										<textarea id="PostContent" name="PostContent" cols="75" rows="10"></textarea>
									</fieldset>
								</div>
								<div>
									<fieldset>
										<legend>' . $ForumLang['PostsAddOptions'] . '</legend>
										<input type="checkbox" checked name="AllowSmiles">&nbsp;' . $ForumLang['PostsAllowSmiles'] . '
									</fieldset>
								</div>
								<div>
									<input type="hidden" name="add" value="1">
									<div id="SubmitButtonDiv">
										<input id="AddSubmit" type="submit" name="Adding" value="' . $ForumLang['PostsSubmit'] . '">
									</div>
								</div>
								' . $CaptchaBlock . '
							</form>
						</td>
					</tr>';
}

//быстрый переход по темам (форумам)
if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_POST_FASTGO && $UserGroups_Permissions['ShowPostsFastGoto'])) {
	$MainOutput .= '<tr><td style="padding-top:20px;" class="MainFastJump">';
	$MainOutput .= '<div><b>' . $ForumLang['ThemeFastJump'] . '</b></div>';
	$MainOutput .= '<div>'    . $ForumLang['JumpToTheme']   . Echo_GotoTheme ($ForumConnection, $SelfName, $ForumLang, $IdForum) . '</div>';
	$MainOutput .= '</td></tr>';
} */

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;

$b = microtime ();
//echo ($b-$a);

?>