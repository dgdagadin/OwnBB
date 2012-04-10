<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//гостевой доступ
if ($_SESSION['UserData']['UserType'] <> 'admin'&& !$UserGroups_Permissions['VisitThemes']) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}

//ид форума
if (!isset ($_GET['id_forum']) || !ctype_digit ($_GET['id_forum'])) {
	OBB_Main_ShowError ('themes_wrong_forum', 'STANDART', $ForumLang['Errors']);
}
$IdForum = intval ($_GET['id_forum']);

//если форум = 0, редирект на гл. стр.
if ($IdForum == intval (0)) {
	OBB_Main_Redirect ();
}

//инфо о форуме
$SQLForumInfo = 'SELECT ForumName, ForumGuestView, ForumMinStatus, ForumBlock
				FROM forums_list
				WHERE ForumID = \'' . $IdForum . '\'';
$ForumInfoQuery = DB_Query ($Config_DBType, $SQLForumInfo, $ForumConnection);
if (!$ForumInfoQuery) {
	OBB_Main_Log ($SQLForumInfo . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//если такого форума не существуeт
$ForumExists = DB_NumRows ($Config_DBType, $ForumInfoQuery);
if ($ForumExists == 0) {
	OBB_Main_ShowError ('themes_no_forum', 'STANDART', $ForumLang['Errors']);
}

//данные о форуме
$ForumRow = DB_FetchAssoc ($Config_DBType, $ForumInfoQuery);
$ForumName      = $ForumRow['ForumName'];
$ForumGuestView = $ForumRow['ForumGuestView'];
$ForumMinStatus = $ForumRow['ForumMinStatus'];
$ForumBlock     = $ForumRow['ForumBlock'];

//гостевой доступ ДАННОГО форума
if ($_SESSION['UserData']['UserType'] == 'guest' && $ForumGuestView == 'no') {	
	OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);	
}

//доступ по группе
if (Access_CheckUserGroup ($ForumMinStatus) == FALSE && $_SESSION['UserData']['UserType'] <> 'admin') {
	OBB_Main_ShowError ('common_no_group_access', 'STANDART', $ForumLang['Errors']);
}

//подключения
include (OBB_KERNEL_DIR . '/PageLib.php');
include (OBB_KERNEL_DIR . '/CacheFunctions.php');


//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Themes_' . $Config_Lang . '.php');

//Функция обновления онлайн-информации о пользователе/госте
$CurAction = 'forum_page';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//Главная переменнвя вывода
$MainOutput = '';

//Формируем массив главной навигации
$NavigArray = array (array ($SelfName, $ForumLang['MainPage']),
					 array ($SelfName . '?action=forumview&id_forum=' . $IdForum, $ForumName));

//JS-массив
$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', '')
					);

//залоговок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Themes'], array('forumname'=>$Config_ForumName,'underforumname'=>$ForumName));

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'ThemeDelimiterDiv');

//новая тема
$NewBlockButton = '<div class="BlockThemePost BlockTheme">
						<div class="BlockThemeIcon">
							<span>' . $ForumLang['ThemesBlockForum'] . '</span>
						</div>
					</div>';

$URL = $SelfName . '?action=add&label=theme&id_forum=' . $IdForum;
$URL = Defence_HTMLSpecials ($URL);
$NewThemeButton = '<div class="AddThemePost AddTheme">
						<div class="AddThemeIcon">
							<a href="' . $URL . '">
								<span>' . $ForumLang['ThemesAddTheme'] . '</span>
							</a>
						</div>
					</div>';

//Добавление новой темы
//  -если админ
//  -если разрешено в группе и в общем
if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_ADD_THEMES && $UserGroups_Permissions['AddThemes'])) {
	if ($ForumBlock == 'yes') {
		$NewThemeAdd = $NewBlockButton;
	}
	else {
		$NewThemeAdd = $NewThemeButton;
	}
}
else {
	$NewThemeAdd = '&nbsp;';
}

//определение страницы
$GetNumThemesSQL = 'SELECT COUNT(*) FROM themes WHERE ForumID = \'' . $IdForum . '\'';
$GetNumThemesQuery = DB_Query ($Config_DBType, $GetNumThemesSQL, $ForumConnection);
if (!$GetNumThemesQuery) {
	OBB_Main_Log ($GetNumThemesSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$ForumNumThemes = DB_Result ($Config_DBType, $GetNumThemesQuery, 0, 0);
$NumPages    = $ForumNumThemes > 0 ? ceil ($ForumNumThemes / OBB_NUM_THEMES_PER_PAGE) : 1;
$CurrentPage = !isset ($_GET['page']) || !ctype_digit ($_GET['page']) ? 1 : intval ($_GET['page']);
if ($CurrentPage < 1) {
	$CurrentPage = 1;
}
else if ($CurrentPage > $NumPages) {
	$CurrentPage = $NumPages;
}

//постраничная навигация
if ($ForumNumThemes > 0) {
	$PageURL = $SelfName . '?action=forumview&id_forum=' . $IdForum;
	$PageList = PL_PageList ($NumPages, $CurrentPage, $PageURL, 'page', '1', '1', '2', '2');
}
else {
	$PageList = '&nbsp;';
}

//объединение кнопки темы и страниц
$MainOutput .= '<table class="AddAndPagesTable" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td class="Left">
							<div>' . $PageList . '</div>
						</td>
						<td class="Right" style="width:150px;">
							<div style="float:right;">' . $NewThemeAdd . '</div>
						</td>
					</tr>
				</table>';

//получение ширины колонки
//заголовки админских эл-тов
if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
	$ThemesAdminHeaders = ' <td class="ForumCharter" style="width:16px;">&nbsp;</td>
							<td class="ForumCharter" style="width:16px;">&nbsp;</td>';
}
else {
	$ThemesAdminHeaders = '';
}

if ($ForumNumThemes > 0) {
	//великий запрос тем :)
	$ThemesListSQL = 'SELECT themes.ThemeID AS ThID, 
							themes.UserID AS UID, 
							themes.UserName AS UNm, 
							themes.ThemeName AS ThName, 
							themes.ThemeDate AS ThDate, 
							themes.ThemeSmile AS ThSmile, 
							themes.ThemeBlock AS ThBlock, 
							themes.ThemeNumPosts AS ThNmP, 
							themes.ThemeNumViews AS ThNmV, 
							themes.ThemeNumAttaches AS ThNmAtt, 
							themes.ThemeImportant AS ThImp, 
							themes.ThemeQuiz AS ThQuiz, 
							themes.UpdatePostID AS ThLastPID, 
							themes.ThemeUpDate AS ThUPD, 
							themes.UpdateUserID AS ThUID, 
							themes.ThemeUpdateUserName AS ThUpUNm
							' . ($_SESSION['UserData']['UserType'] <> 'guest' ? ', users_to_themes.ViewDate AS ViewDate' : '') . '
					  FROM themes '
					  . ($_SESSION['UserData']['UserType'] <> 'guest' ? 'LEFT JOIN users_to_themes ON themes.ThemeID = users_to_themes.ThemeID AND users_to_themes.UserID = \'' . intval ($_SESSION['UserData']['UserID']) . '\' ' : '') . 
					 'WHERE themes.ForumID = \'' . $IdForum . '\'
					  ORDER BY themes.ThemeImportant ASC, themes.ThemeUpDate DESC, themes.ThemeID DESC
					  LIMIT ' . OBB_NUM_THEMES_PER_PAGE * ($CurrentPage - 1) . ', ' . OBB_NUM_THEMES_PER_PAGE;
	$ThemesListQuery = DB_Query ($Config_DBType, $ThemesListSQL, $ForumConnection);
	if (!$ThemesListQuery) {
		OBB_Main_Log ($ThemesListSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	//colspan
	$Colspan = OBB_SHOW_ADMIN_ELEMENTS ? Echo_GetColspan (8, 6) : 6;

	//разделитель
	$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

	//главная таблица
	$MainOutput .= '<table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">';

	//темы форума - заголовки
	$MainOutput .= '<tr class="MainColumnRow">
						<td colspan="' . $Colspan . '" class="MainColumnName">
							<span>' . $ForumName . '</span>
						</td>
					</tr>
					<tr>
						<td style="border-top:1px solid #FFFFFF; width:40px;" class="ForumCharter">&nbsp;</td>
						<td style="border-top:1px solid #FFFFFF; width:16px;" class="ForumCharter">&nbsp;</td>
						<td class="ForumCharter" style="border-top:1px solid #FFFFFF;">
							<span class="ThemesTitle">' . $ForumLang['ThemesTitle']      . '</span>
						</td>
						<td class="ForumCharter" style="border-top:1px solid #FFFFFF; width:70px;">
							<span class="ThemesTitle">' . $ForumLang['ThemesNumWatches'] . '</span>
						</td>
						<td class="ForumCharter" style="border-top:1px solid #FFFFFF; width:64px;">
							<span class="ThemesPosts">' . $ForumLang['ThemesReports']    . '</span>
						</td>
						<td class="ForumCharter"  style="border-top:1px solid #FFFFFF; width:350px;">
							<span class="ThemesTitle">' . $ForumLang['ThemesUpdate']     . '</span>
						</td>
						' . $ThemesAdminHeaders . '
					</tr>';

while ($ThemesRow = DB_FetchAssoc ($Config_DBType, $ThemesListQuery)) {
		//присвоение - для удобства
		$ThID    = $ThemesRow['ThID'];
		$ThName  = $ThemesRow['ThName'];
		$ThDate  = $ThemesRow['ThDate'];
		$ThSmile = $ThemesRow['ThSmile'];
		$UID     = $ThemesRow['UID'];
		$UNm     = $ThemesRow['UNm'];
		$ThBlock = $ThemesRow['ThBlock'];
		$ThNmP   = $ThemesRow['ThNmP'];
		$ThNmV   = $ThemesRow['ThNmV'];
		$ThNmAtt = $ThemesRow['ThNmAtt'];
		$ThImp   = $ThemesRow['ThImp'];
		$ThQuiz  = $ThemesRow['ThQuiz'];
		$ThLPID  = $ThemesRow['ThLastPID'];
		$ThUPD   = $ThemesRow['ThUPD'];
		$ThUID   = $ThemesRow['ThUID'];
		$ThUpUNm = $ThemesRow['ThUpUNm'];
		if ($_SESSION['UserData']['UserType'] <> 'guest') {
			$ThmView = $ThemesRow['ViewDate'];
		}
		else {
			$ThmView = '';
		}

		//1. Иконка состояния темы
		$ThemeStatus = Echo_GetThemeIcon ($ThNmP, $ThmView, $ThUPD, $ThBlock);

		//2.Иконка темы
		//$ThemeEmotion = $ThSmile <> '0' ? '<img alt="" src="' . OBB_IMAGE_DIR . '/ThemeIcons/' . $ThSmile . '.gif" />' : '';
		if ($ThSmile <> '0') {
			$ThemeEmotIcon = $ThSmile . '.gif';
		}
		else {
			$ThemeEmotIcon = 'default.png';
		}
		$ThemeEmotion = '<img alt="" src="' . OBB_IMAGE_DIR . '/ThemeIcons/' . $ThemeEmotIcon . '" />';

		//3.Название темы
		$ThemeName = Defence_HTMLSpecials ($ThName);
		$ThemeLink = '<a class="ThemeLink" href="' . Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $ThID) . '">' . $ThemeName . '</a>';

		//4.К-во вложений
		$NumAttaches = $ThNmAtt > 0 ? '<img alt="" class="NumAttaches" title="' . $ForumLang['ThemesNumAttaches'] . ':' . $ThNmAtt . '" src="' . OBB_IMAGE_DIR . '/attach.png" />' : '';

		//5.Страницы темы (если больше одной)
		$NumPagesInTheme = ceil ($ThNmP/OBB_NUM_POSTS_PER_PAGE);
		if ($NumPagesInTheme > 1) {
			$PagesURL = $SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $ThID;
			$ThemePages = '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($PagesURL . '&page=1') . '">1</a></span><span class="SmallLink"><a href="' . Defence_HTMLSpecials ($PagesURL . '&page=2') . '">2</a></span>';
			if ($NumPagesInTheme >= 3) {
				$ThemePages .= '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($PagesURL . '&page=3') . '">3</a></span>';
				if ($NumPagesInTheme == 4) {
					$ThemePages .= '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($PagesURL . '&page=4') . '">4</a></span>';
				}
				else if ($NumPagesInTheme > 4) {
					$ThemePages .= '<span class="Dottes">...</span><span class="SmallLink"><a href="' . Defence_HTMLSpecials ($PagesURL . '&page=' . $NumPagesInTheme) . '">' . $NumPagesInTheme . '</span>';
				}
			}
			$ThemePages = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<a class="ImgHref" href="' . Defence_HTMLSpecials ($PagesURL) . '">
								<img alt="" title="' . $ForumLang['ThemesManyPages'] . '" src="' . OBB_IMAGE_DIR . '/pages_icon.gif" />
							</a>' . $ThemePages;
		}
		else {
			$ThemePages = '';
		}

		//6.Если тема вaжная
		$IsImportant = $ThImp == 'yes' ? '<img alt="" title="' . $ForumLang['ThemesImportantTheme'] . '" class="ThemeIsImportantImg" src="' . OBB_IMAGE_DIR . '/theme_important.png" />' : '';

		//7.Автор темы и время ее создания
		//  --7.1 Автор
		$UID = intval ($UID);
		if ($UID > 0) {
			$Astart = '<a title="' . $ForumLang['ThemesThemeAuthor'] . '" class="UserNameHref" href="' . Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $UID) . '">';
			$Aend = '</a>';
		}
		else {
			$Astart = '<span title="' . $ForumLang['ThemesThemeAuthor'] . '" class="UserNameSpan">';
			$Aend   = '</span>';
		}
		$ThemeAuthor = $Astart . Defence_HTMLSpecials ($UNm) . $Aend;

		//  --7.2 Время создания темы
		$ThemeCreationDate = Main_ConvertDate ($ThDate, $ForumLang['DateArray'], 'd.m.Y, H:i');
		$ThemeCreateDate = '<acronym class="CreateAcr" title="' . Main_ConvertDate ($ThDate, $ForumLang['DateArray']) . '">' . $ThemeCreationDate . '</acronym>';

		//  --7.3 Объединение автора, даты и постр. нав.
		$ThemeAuthorDatePageDiv = ' <div class="ThemeADPDDiv">
										<div class="ThemeAuthorDate">' . $ThemeAuthor . '&nbsp;-&nbsp;' . $ThemeCreateDate . $ThemePages . '</div>
									</div>';

		//Объединение компонентов названия
		$ThemeNameCell = '<div class="ThemeNameDiv">' . $IsImportant . $NumAttaches . $ThemeLink . '</div>' . $ThemeAuthorDatePageDiv;

		//Админские элементы управления
		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
			$AdminThemesPanel ='<td class="AdminShowFirst">
									<a title="' . $ForumLang['ThemesEditTheme']   . '" href="' . Defence_HTMLSpecials ('index.php?action=admin&adm=theme&tact=edit&tid=' . $ThID) . '"><img alt="" title="' . $ForumLang['ThemesEditTheme']   . '" src="' . OBB_IMAGE_DIR . '/admin_edit.png" /></a>
								</td>
								<td class="AdminShow">
									<a title="' . $ForumLang['ThemesDeleteTheme'] . '" href="' . Defence_HTMLSpecials ('index.php?action=admin&adm=theme&tact=del&tid='  . $ThID) . '"><img alt="" title="' . $ForumLang['ThemesDeleteTheme'] . '" src="' . OBB_IMAGE_DIR . '/admin_delete.png" /></a>
								</td>';
		}
		else {
			$AdminThemesPanel = '';
		}

		//8.Обновление
		$ThemeUpDateTime = Main_ConvertDate ($ThUPD, $ForumLang['DateArray']);
		$ThemeUpDate = '<acronym class="UpdateAcr" title="' . $ThemeUpDateTime . '">' . $ThemeUpDateTime . '</acronym>';
		$UpdateUser = ($ThUID > 0 ? '<a class="UpdateUserLink" title="' . $ThUpUNm . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $ThUID) . '">' : '<span class="UpdateUserText">') . $ThUpUNm . ($ThUID > 0 ? '</a>' : '</span>');
		$UpdateUser = '<span class="UpdateAuthorTitle">' . $ForumLang['ThemesAuthor'] . ':</span>&nbsp;' . $UpdateUser;
		$GoToPost = '<a title="' . $ForumLang['ThemesGotoLastPost'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $ThID . '&last_post=' . $ThLPID) . '#pid' . $ThLPID  . '"><img alt="" title="' . $ForumLang['ThemesGotoLastPost'] . '" src="' . OBB_IMAGE_DIR . '/last_post.gif" /></a>';
		$ThemeLastUpdate = '<div class="UpdateTime">
								' . $GoToPost . '&nbsp;' . $ThemeUpDate . '
							</div>
							<div class="UpdateAuthor">
								' . $UpdateUser . '
							</div>';

		//объед. в одну строку св-в темы
		$ThNmV = intval ($ThNmV);
		$ThNmP = intval ($ThNmP);
		$ThemeRow = '<tr class="ForumMainTR">
						<td class="ForumIconTD">'             . $ThemeStatus     . '</td>
						<td class="ForumIconTD">'             . $ThemeEmotion    . '</td>
						<td class="ForumNameTD ThemeNameTD">' . $ThemeNameCell   . '</td>
						<td class="ForumNumThemesTD">'        . $ThNmV           . '</td>
						<td class="ForumNumThemesTD">'        . ($ThNmP)         . '</td>
						<td class="ForumUpdateTD">'           . $ThemeLastUpdate . '</td>
						' . $AdminThemesPanel . '
					</tr>';
					
		$MainOutput .= $ThemeRow;
	}

	$MainOutput .= '<tr>
						<td class="ForumsTableBottom" colspan="' . $Colspan . '">
							<div><!-- --></div>
						</td>
					</tr>';

	//таблица тем - закрытие
	$MainOutput .= '</table>';

	//разделитель
	$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';
}
else {
	$MainOutput .= '<div class="NoThemes">' . $ForumLang['ThemesNo'] . '</div>';
}

//объединение кнопки темы и страниц - ПОВТОРНО
$MainOutput .= '<table class="AddAndPagesTable" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td class="Left">
							<div>' . $PageList . '</div>
						</td>
						<td class="Right" style="width:150px;">
							<div style="float:right;">' . $NewThemeAdd . '</div>
						</td>
					</tr>
				</table>';

//ФЛАГ
//  - если разрешен вывод "Ваших возможностей"
//  - если разрешен быстрый переход по форумам в темах
if (
		(OBB_SHOW_YOUR_ABILITIES)
		||
		($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_THEME_FASTGO && $UserGroups_Permissions['ShowThemesFastGoto']))
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
	if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_THEME_FASTGO && $UserGroups_Permissions['ShowThemesFastGoto'])) {
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

//echo microtime() - $a; 
?>