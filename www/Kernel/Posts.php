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
$NewThmButton = '  <div onclick="javascript:location.href=\'' . $URL . '\';" style="float:right;" class="AddThemePost AddTheme">
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
$NewAnswerButton = '<div onclick="javascript:location.href=\'' . $AnswerURL . '\';" style="float:right;" class="AddThemePost AddPost">
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
$URLsReplaceText = '' . OBB_Main_ReplaceSymbols ($ReplaceText, $ReplaceArray) . '';

//  --прикрепления
$ReplaceText = $_SESSION['UserData']['UserType'] == 'guest' ? $ForumLang['PostsAttachesLoginOrRegister'] : $ForumLang['PostsAttachesNoAccess'];
$AttachesReplaceText = '<span style="color:#AA0000;">' . OBB_Main_ReplaceSymbols ($ReplaceText, $ReplaceArray) . '</span>';

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
	$MainOutput .= '<table class="MainForumsTable PostsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td colspan="2" style="white-space: nowrap;" class="MainColumnName">
								<span>' . Defence_HTMLSpecials ($TName) . '</span>
							</td>
						</tr>
						<tr>
							<td class="ForumCharter" style="width:195px; border-top:1px solid #FFFFFF;">
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
		$PUserRegDate = Main_ConvertDate ($PUserRegDate, $DateArray, 'd.m.Y');

		$PostNum++;

		//ВЕРХ
		//1)Верх лево
		//  --1.Имя автора
		if (!$ThemeIsBlocked && ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHORT_ADD_FORM && $UserGroups_Permissions['AddPosts'] && $UserGroups_Permissions['ShowShortAnswer']))) {
			$Onclick = ' onclick="javascript:addUserBold(\'ShortAddForm\', \'PostContent\', \'' . $PUserName . '\');"';
			$ShortAddHref = '#Add';
		}
		else {
			$Onclick = '';
			$ShortAddHref = 'javascript:void(0);';
		}
		$UserName = '<a href="' . $ShortAddHref . '"' . $Onclick . '>' . Defence_HTMLSpecials ($PUserName) . '</a>';

		//  --2.Онлайн/оффлайн
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

		//  --3.Общая строка с именем
		$ComUserString = '<div class="PostsLeftTop">'
							. $UserIsOnline
							. $UserName    .
						'</div>';
		//1)Верх лево - КОНЕЦ

		//2)Верх право
		//  --1.Дата сообщения
		$PostDateSpan = '<div class="PostDate"><span class="PostDateTitle">' . $ForumLang['PostsDate'] . ':</span>' . Main_ConvertDate ($PDate, $DateArray, 'd.m.Y, H:i') . '</div>';

		//  --2.Номер сообщения
		$CurPostHref = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&last_post=' . $PID) . '#pid' . $PID;
		$PostNumberSpan = '<div class="PostLink">
								<a name="pid' . $PID . '" href="' . $CurPostHref . '">' . $ForumLang['PostsPost'] . ' #' . $PostNum . '</a>
						   </div>';

		//  --3.Кнопка "Свернуть сообщение"
		$PostHideButton = '<div class="PostHide">
								<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'Post' . $PID . '\',\'' . OBB_IMAGE_DIR . '\',\'PostHide' . $PID . '\');"><img style="vertical-align:middle;" id="PostHide' . $PID . '" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
							</div>';

		//  --4.Общая строка с датой, номером и кнопкой "Свернуть сообщение"
		$PostDateDiv = $PostDateSpan . $PostNumberSpan . $PostHideButton;
		//2)Верх право - КОНЕЦ
		//ВЕРХ - КОНЕЦ

		//СЕРЕДИНА
		//1)Середина лево
		//  --1.Аватар
		//    --если он не пустой
		//    --если он допустим
		if ($PUserID > '0') {
			$UserIsDeleted = '';

			$IsUserAdminGroup = OBB_Main_IsAdminGroup ($PGroupID);
			$CurrentUserAvatarPermissions = OBB_Main_GetSpecifiedPermission ($PGroupID, 'AvatarsAllowed');
			if ($IsUserAdminGroup || (!$IsUserAdminGroup && $CurrentUserAvatarPermissions)) {
				$AvatarPermissions = TRUE;
			}
			else {
				$AvatarPermissions = false;
			}
			if (OBB_ALLOW_AVATARS && !OBB_Main_IsEmpty ($PUserAvatar) && $AvatarPermissions) {
				$UserAvatar = '<img class="AvatarImg" alt="" title="" src="' . OBB_AVATAR_DIR . '/' . $PUserID . '.' . $PUserAvatar . '" />';
			}
			else {
				$UserAvatar = '';
			}
		}
		else {
			$UserAvatar = '';
		}

		//  --2.Статус
		if ($PUserID > '0') {
			$UserStatusPosts = $IsUserAdminGroup ? OBB_MAX_STATUS*OBB_NUM_POSTS_IN_STATUS : $PUserNumPosts;
			$StatusImage = Echo_GetUserStatus ($UserStatusPosts, OBB_NUM_POSTS_IN_STATUS, OBB_MAX_STATUS, OBB_IMAGE_DIR);
			$UserStatus  = '<div class="UserStatus">
								' . $StatusImage . '
							</div>';
		}
		else {
			$UserStatus = '';
		}

		//  --3.Группа пользователя
		//    --если зарегистрирован
		if ($PUserID > '0') {
			$UserGroup = '<div class="UserGroupName">
							<span class="UserGroupTitle">' . $ForumLang['PostsGroup'] . ':</span>
							<span class="UserGroup" style="color:#' . $UserGroups_Array[$PGroupID]['Color'] . ';">' . $UserGroups_Array[$PGroupID]['Description'] . '</span>
						  </div>';
		}
		else {
			$UserGroup = '';
		}
		//    --если не зарегистрирован
		if ($PUserID < 1) {
			if ($PUserID == 0) {
				$UserGroup = '<div class="NoRegistered">
								<span class="UserGroupTitle">' . $ForumLang['PostsGroup'] . ':</span>
								<span class="UserGroup" style="color:#' . $UserGroups_Array[$Config_GuestGroupID]['Color'] . ';">' . $UserGroups_Array[$Config_GuestGroupID]['Description'] . '</span>
							 </div>';
			}
			else {
				$UserGroup = '<div class="NoRegistered Deleted">
								<span class="UserGroupTitle">' . $ForumLang['PostsGroup'] . ':</span>
								<span class="UserGroup" style="color:#AA0000;">' . $ForumLang['PostsDeletedGroup'] . '</span>
							 </div>';
			}
		}

		//  --4.Количество сообщений
		if ($PUserID > '0') {
			$NumUserPosts = '<div class="UserNumPosts">
								<span class="NumPostsTitle">' . $ForumLang['PostsNumPosts'] . ':</span>
								<span class="NumPosts">' . $PUserNumPosts . '</span>
							 </div>';
		}
		else {
			$NumUserPosts = '';
		}

		//  --5.Дата регистрации
		if ($PUserID > '0') {
			$UserRegDate = '<div class="UserRegistration">
								<span class="UserRegTitle">' . $ForumLang['PostsRegDate'] . ':</span>
								<span class="UserReg">'      . $PUserRegDate              . '</span>
							</div>';
		}
		else {
			$UserRegDate = '';
		}

		//  --6.Пол
		if ($PUserID > '0') {
			$SexTitle = $PUserSex == 'male' ? $ForumLang['PostsMale'] : $ForumLang['PostsFemale'];
			$UserSex = '<div class="UserSex">
							<span class="UserSexTitleSpan">' . $ForumLang['PostsSex'] . ':</span>
							<span class="UserSexSpan">' . $SexTitle . '</span>
						</div>';
		}
		else {
			$UserSex = '';
		}
	
		//  --7.Объединение левой середины
		$AuthorPart = $UserStatus . $UserAvatar . $UserGroup . $NumUserPosts . $UserSex . $UserRegDate;
		//1)Середина лево - КОНЕЦ

		//2)Середина право
		//  --1.Прикрепление
		//    --если админ
		//    --если разрешено + есть права на скачивание
		if (
				!OBB_Main_IsEmpty ($PFileName) 
				&& 
				(
					$_SESSION['UserData']['UserType'] == 'admin' 
					|| 
					($_SESSION['UserData']['UserType'] <> 'admin' && OBB_ALLOW_ATTACHES)
				)
		) {
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
				$FileAttach .= '<div class="LoginOrRegister">' . $AttachesReplaceText . '</div>';
			}
			else {
				if ($AttachIsImage) {
					$AttachImgSize = '&nbsp;(<strong>' . $PWidth . '</strong>x<strong>' . $PHeight . '</strong>)';
				}
				else {
					$AttachImgSize = '';
				}

				$FileAttach .= '<div class="AttachIconDiv">
									<a title="' . $ForumLang['PostsDownload'] . '" class="GotoHref" href="' . Defence_HTMLSpecials ($SelfName . '?action=download&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID) . '">' . $FileName . '</a>&nbsp;&nbsp;
									<span class="AttachInfo"><strong>' . $FileSize . '</strong>'. '&nbsp;' . $UnitLoc . $AttachImgSize . ',&nbsp;<strong>' . $PFileNumViews . '</strong> ' . $ForumLang['PostsFileViews'] . '</span>
								</div>';
			}
			

			$AttachFile = '<div class="MainBlockAttach">
								<span class="FileAttachTitle">' . $ForumLang['PostsAttach'] . '</span>
								<div class="FileAttachContent">' .
									$FileAttach . '
								</div>
						   </div>';
		}
		else {
			$AttachFile = '';
		}

		//  --2.Сообщение
		$AllowSmiles = OBB_ALLOW_SMILES && $PSmilesAllow == 'yes' ? true : false;
		$PostArray   = array ('MsgID'=>$PID,'MsgText'=>$PText);
		$CachePath   = 'PostsCache/forum_' . $IdForum . '/theme_' . $IdTheme;
		$PostText    = OBB_Main_ShowPost ($PostArray, $CachePath, $AllowSmiles);
		if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['LinksAllowed']) {

			$PostText = OBB_Main_DisallowURLs ($PostText, $URLsReplaceText);
		}
		$PostDiv = '<div class="MainBlockPost">' . $PostText . '</div>';

		//  --3.Девиз
		if ($PUserID > '0') {
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

				$SloganDiv = '<div class="MainBlockSlogan">
								<span class="SpanHR">' . Echo_SignatureSeparator () . '</span><br />
								' . $UserSlogan . '
							  </div>';
			}
			else {
				$SloganDiv = '';
			}
		}
		else {
			$SloganDiv = '';
		}

		//  --4.Объединение блока сообщения, прикрепления и девиза
		$MainBlock = '<div class="MainBlockAllDiv">' . $PostDiv . $AttachFile . $SloganDiv . '</div>';
		//2)Середина право - КОНЕЦ
		//СЕРЕДИНА - КОНЕЦ

		//НИЗ
		//1)Низ лево
		//  --1.Кнопка "Написать сообщение по почте"
		//    --если админ
		//    --если разрешено, если разрешено в общем, если разрешено самим пользователем
		if ($PUserID > '0') {
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
				$MailButton = ' <div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsSendMail'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=tooling&label=mail&id_user=' . $PUserID) . '"><img title="' . $ForumLang['PostsSendMail'] . '" alt="" src="' . OBB_IMAGE_DIR . '/mail_small.png" /></a>
								</div>';
			}
			else {
				$MailButton = '';
			}
		}
		else {
			$MailButton = '';
		}

		//  --2.Просмотр профиля
		if (($_SESSION['UserData']['UserType'] == 'admin' || (OBB_WATCH_PROFILE && $UserGroups_Permissions['VisitUserProfile'])) && $PUserID > 0) {
			$ProfileButton = '  <div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsWatchProfile'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $PUserID) . '"><img title="' . $ForumLang['PostsWatchProfile'] . '" alt="" src="' . OBB_IMAGE_DIR . '/profile_icon.png" /></a>
								</div>';
		}
		else {
			$ProfileButton = '';
		}

		//  --3.Объединение кнопок отправки почты и просмотра профиля
		if (Main_Strlen ($MailButton) > 0 || Main_Strlen ($ProfileButton) > 0) {
			$MiddleLeftString = $MailButton . $ProfileButton;
		}
		else {
			$MiddleLeftString = '&nbsp;';
		}
		//1)Низ лево - КОНЕЦ

		//2)Низ право
		//  --1.Кнопка "Редактировать сообщение"
		//    --если админ
		//    --если разрешено, разрешено пользователю, его сообщение
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_EDIT_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['EditPosts'] &&
				$_SESSION['UserData']['UserID'] == intval ($PUserID)
			)
		) {
			$EditButton = '  <td class="RightBottomTD" style="width:32px;">
								<div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsEditPost'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=add&label=edit&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID) . '"><img title="' . $ForumLang['PostsEditPost'] . '" alt="" src="' . OBB_IMAGE_DIR . '/edit.png" /></a>
								</div>
							</td>';
		}
		else {
			$EditButton = '';
		}

		//  --2.Кнопка "Удалить сообщение"
		//    --если админ
		//    --если разрешено, разрешено пользователю, его сообщение
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_DELETE_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['DeletePosts'] &&
				$_SESSION['UserData']['UserID'] == intval ($PUserID)
			)
		) {
			$DeleteButton = '<td class="RightBottomTD" style="width:32px;">
								<div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsDeletePost'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=add&label=del&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_post=' . $PID) . '"><img title="' . $ForumLang['PostsDeletePost'] . '" alt="" src="' . OBB_IMAGE_DIR . '/delete.png" /></a>
								</div>
							</td>';
		}
		else {
			$DeleteButton = '';
		}

		//  --3.Кнопка "Цитировать сообщение"
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
			$QuoteButton = '<td class="RightBottomTD" style="width:32px;">
								<div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsQuotePost'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=add&label=post&id_forum=' . $IdForum . '&id_theme=' . $IdTheme . '&id_quote=' . $PID) . '"><img title="' . $ForumLang['PostsQuotePost'] . '" alt="" src="' . OBB_IMAGE_DIR . '/quote.png" /></a>
								</div>
							</td>';
		}
		else {
			$QuoteButton = '';
		}

		//  --4.Кнопка "Вверх"
		$ToTopButton = '<td class="RightBottomTD Top" style="width:35px;">
							<a href="javascript:void(0);" onclick="scrollToTop();">
								<img alt="" src="' . OBB_IMAGE_DIR . '/top.png" />
							</a>
						</td>';

		//  --5.Строка даты редактирования сообщения
		if (!OBB_Main_IsEmpty ($PEditDate)) {
			$EditedDate = ' <td class="RightBottomTD">
								<span class="EditDateTitleSpan">' . $ForumLang['PostsEdited'] . '</span>:<span class="EditDateSpan">' . Main_ConvertDate ($PEditDate, $DateArray, 'd.m.Y, H:i') . '</span>
							</td>';
		}
		else {
			$EditedDate = '<td class="RightBottomTD">&nbsp;</td>';
		}

		//  --6.Кнопка "Пожаловаться"
		//    --если админ
		//    --если разрешено, разрешено пользователю, его сообщение, если не гость
		if (
			$_SESSION['UserData']['UserType'] == 'admin' ||
			(
				OBB_REPORT_POSTS &&
				$_SESSION['UserData']['UserType'] <> 'guest' &&
				$UserGroups_Permissions['ReportPosts']
			)
		) {
			$ReportButton = '<td class="RightBottomTD" style="width:32px;">
								<div style="width:30px; height:22px;" class="PostButton">
									<a title="' . $ForumLang['PostsReportPost'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=tooling&label=report&id_post=' . $PID) . '"><img title="' . $ForumLang['PostsReportPost'] . '" alt="" src="' . OBB_IMAGE_DIR . '/report.png" /></a>
								</div>
							</td>';
		}
		else {
			$ReportButton = '';
		}

		//  --7.Объединение "Редактировать сообщение", "Удалить сообщение", "Цитировать сообщение", "Вверх", строки даты редактирования сообщения
		$ExtraString = '<table class="RightBottom" cellpadding="0" cellspacing="0" border="0">
							<tr>
							' . $EditedDate   . '
							' . $DeleteButton . '
							' . $EditButton   . '
							' . $ReportButton . '
							' . $QuoteButton  . '
							' . $ToTopButton  . '
							</tr>
						</table>';
		//2)Низ право - КОНЕЦ
		//НИЗ - КОНЕЦ

		//ОБЪЕДИНЕНИЕ СТРОКИ
		$MainOutput .= '<tr class="ForumMainTR">
							<td class="PostsUserName">'    . $ComUserString  . '</td>
							<td class="PostsDateLink">'    . $PostDateDiv    . '</td>
						</tr> 
						<tr class="Post' . $PID . '">
							<td class="PostsUserInfo">'    . $AuthorPart      . '</td>
							<td class="PostsMainBlock">'   . $MainBlock       . '</td>
						</tr>
						<tr>
							<td class="PostsUserProfile">' . $MiddleLeftString . '</td>
							<td class="PostsPanelString">' . $ExtraString      . '</td>
						</tr>
						<tr>
							<td colspan="2" class="ForumsTableBottom ForumsTableDivider">
								<div style="height:12px;"><!-- --></div>
							</td>
						</tr>';
		//ОБЪЕДИНЕНИЕ СТРОКИ - КОНЕЦ
	}

	$MainOutput .= '</table>';

	$MainOutput .= '';
}
else {
	$MainOutput .= $ForumLang['PostsNo'];
}

//разделитель
$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

$MainOutput .= $ThemePostPage;

//быстрое добавление ответа
//  --если админ
//  --если разрешено, есть полномочия
if (!$ThemeIsBlocked && ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHORT_ADD_FORM && $UserGroups_Permissions['AddPosts'] && $UserGroups_Permissions['ShowShortAnswer']))) {
	//Разделительный див
	$MainOutput .= '<div class="BlockDelimiterDiv"><!-- --></div>';

	//капча добавления ответа
	if ($_SESSION['UserData']['UserType'] <> 'admin' && (OBB_CAPTCHA && $UserGroups_Permissions['CaptchaAddPost'])) {
		$CaptchaTitle = $ForumLang['CaptchaTitle'];
		$EnterCaptcha = $ForumLang['EnterCaptcha'];
		$CaptchaBlock = Echo_CaptchaBlock2 ($CaptchaTitle, $EnterCaptcha, 'ShortAddCaptcha');
	}
	else {
		$CaptchaBlock = '';
	}

	//Гостевые поля
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		//  --логин
		$GuestLogin = '<tr>
							<td style="width:160px;" class="FormTitleTD">
								<div class="InputTitle">
									' . $ForumLang['PostsGuestName'] . '<span class="Important">*</span>
								</div>
								<div class="InputDescr">
									(' . OBB_Main_ReplaceSymbols ($ForumLang['PostsGuestNameLimit'], array('min'=>OBB_MIN_LOGIN_LENGTH, 'max'=>OBB_MAX_LOGIN_LENGTH)) . ')
								</div>
							</td>
							<td class="FormInputTD">
									<input style="width:280px;" class="InpEl InpText" id="GuestLoginID" type="text" name="GuestLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="" />
							</td>
						</tr>';

		//  --электронная почта
		$GuestMail  = '<tr>
							<td style="width:160px;" class="FormTitleTD">
								<div class="InputTitle">
									' . $ForumLang['PostsGuestMail'] . '<span class="Important">*</span>
								</div>
								<div class="InputDescr">
									(' . OBB_Main_ReplaceSymbols ($ForumLang['PostsGuestMailLimit'], array('min'=>OBB_MIN_MAIL_LENGTH, 'max'=>OBB_MAX_MAIL_LENGTH)) . ')
								</div>
							</td>
							<td class="FormInputTD">
								<input style="width:280px;" class="InpEl InpText" id="GuestMailID"  type="text" name="GuestMail" maxlength="'  . OBB_MAX_MAIL_LENGTH  . '" value="" />
							</td>
						</tr>';
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
									array ('BBType'=>'simple', 'BBSubType'=>'quote'),
									array ('BBType'=>'simple', 'BBSubType'=>'code'),
									array ('BBType'=>'simple', 'BBSubType'=>'url'),
									array ('BBType'=>'simple', 'BBSubType'=>'img'),
									array ('BBType'=>'simple', 'BBSubType'=>'list'),
									array ('BBType'=>'simple', 'BBSubType'=>'listitem'),
									array ('BBType'=>'simple', 'BBSubType'=>'smile'),
									array ('BBType'=>'simple', 'BBSubType'=>'color')
								),
						'ColorsArray'=>array ('#000000', '#610B38', '#DF0101', '#8A4B08',
											  '#FF8000', '#0B610B', '#01DF01', '#01DFD7',
											  '#08088A', '#2E2EFE', '#7401DF', '#DF01D7', 
											  '#585858', '#BDBDBD', '#D0A9F5', '#A9D0F5'),
						'TextareaName'=>'PostContent',
						'TextareaID'=>'PostContent',
						'FormID'=>'ShortAddForm',
						'NumberCols'=>'100',
						'NumberRows'=>'15',
						'BorderColor'=>'#000000',
						'SmilesLink'=>Defence_HTMLSpecials ($SelfName . '/index.php?action=tooling&label=smile&f_id=ShortAddForm&t_id=PostContent'),
						'ImagesPath'=>OBB_IMAGE_DIR . '/bbcodes');

	//Заголовок таблицы формы быстрого ответа
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td colspan="1" class="MainColumnName"><span>' . $ForumLang['PostsFastPost'] . '</span></td>
						</tr>
						<tr class="ForumMainTR">
							<td style="padding:0;">
								<form style="padding:0; margin:0;" id="ShortAddForm" action="' . Defence_HTMLSpecials ($SelfName . '?action=add&label=post&id_forum=' . $IdForum . '&id_theme=' . $IdTheme) . '" method="post">
									<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
										' . $GuestLogin . '
										' . $GuestMail  . '
										<tr>
											<td style="width:160px; vertical-align:top;" class="FormTitleTD">
												<br /><br />
												<div class="InputTitle">
													' . $ForumLang['PostsMessage'] . '<span class="Important">*</span>
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['PostsGuestPostLimit'], array('max'=>OBB_MAX_POST_LENGTH)) . ')
												</div>
											</td>
											<td class="FormInputTD TextareaTD">
												<a name="Add"></a>
												' . Echo_BBEditor ($Parameters) . '
												<div>
													<textarea style="width:95%;" class="InpEl InpTextarea" id="PostContent" name="PostContent" onfocus="initInsertions(\'ShortAddForm\', \'PostContent\');" onkeyup="storeCaret(this);" onclick="storeCaret(this);" onselect="storeCaret(this);" cols="87" rows="10"></textarea>
												</div>
											</td>
										</tr>
										<tr>
											<td style="width:160px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['PostsAddOptions'] . '
												</div>
											</td>
											<td class="FormInputTD">
												<input id="Label_AllowSmiles" class="InpCheckbox" type="checkbox" checked="checked" name="AllowSmiles" />
												<span class="Usual"><label for="Label_AllowSmiles">' . $ForumLang['PostsAllowSmiles'] . '</label></span>
											</td>
										</tr>
										' . $CaptchaBlock . '
										<tr>
											<td style="border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
												<span class="Important">*</span> - ' . $ForumLang['PostsImportantFields'] . '
											</td>
										</tr>
										<tr>
											<td colspan="2" class="FormInputTD CenterTD">
												<input type="hidden" name="add" value="1" />
												<div id="SubmitButtonDiv">
													<input class="InpButton" id="AddSubmit" type="submit" name="Adding" value="' . $ForumLang['PostsSubmit'] . '" />
													<input class="InpButton" type="reset" name="Adding" value="' . $ForumLang['PostsReset'] . '" />
												</div>
											</td>
										</tr>
									</table>
								</form>
							</td>
						</tr>';
						
	//Вывод конца таблицы формы быстрого ответа
	$MainOutput .= '<tr>
						<td class="ForumsTableBottom" colspan="1">
							<div><!-- --></div>
						</td>
					</tr>';
	$MainOutput .= '</table>';
}

//ФЛАГ
//  - если разрешен вывод "Ваших возможностей"
//  - если разрешен быстрый переход по форумам в темах
if (
		(OBB_SHOW_YOUR_ABILITIES)
		||
		($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_POST_FASTGO && $UserGroups_Permissions['ShowPostsFastGoto']))
	) {
	$OtherFlag = TRUE;
}
else {
	$OtherFlag = false;
}

if ($OtherFlag) {
	//Разделительный див
	$MainOutput .= '<div class="OtherShowDelimiterDiv"><!-- --></div>';
	$MainOutput .= '<div class="OtherShowDiv">';

	//Вывод "Ваших возможностей"
	if (OBB_SHOW_YOUR_ABILITIES) {
		$MainOutput .= '<div class="OtherLeftDiv">
							' . Echo_ShowGroupAbilities () . '
						</div>';
	}

	//Вывод быстрого перехода по форумам
	//  -если админ
	//  -если разрешено в общем и в группе
	if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_POST_FASTGO && $UserGroups_Permissions['ShowPostsFastGoto'])) {
		$MainOutput .= '<div class="OtherRightDiv">
							<div class="ForumJump">' . $ForumLang['ForumFastJump'] . '</div>
							<div class="ForumJumpSelect">' . Echo_GotoForum ($ForumConnection, $SelfName, $ForumLang) . '</div>
						</div>';
	}

	$MainOutput .= '</div><div style="clear:both;"></div>';
}

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;

$b = microtime ();
//echo ($b-$a);

?>