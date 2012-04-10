<?php

$a = microtime();

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//Проверка на доступ к главной странице
if ($_SESSION['UserData']['UserType'] <> 'admin'&& !$UserGroups_Permissions['VisitIndex']) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}

//подключения
include (OBB_KERNEL_DIR . '/CacheFunctions.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Main_' . $Config_Lang . '.php');

//переменная "сейчас"
$Now = time ();

//Функция обновления онлайн-информации о пользователе/госте
$CurAction = 'main_page';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//выбор групп
$GroupsArray = OBB_Main_GetUserGroups ();

//Главная переменнвя вывода
$MainOutput = '';

//Формируем массив главной навигации
$NavigArray = array (
				array ($SelfName, $ForumLang['MainPage'])
);

//Инициализируем переменную-ид текущ. категории
$CurrentChart = 0;

//Переменные статистики
$Stat_NumForums = 0;
$Stat_NumThemes = 0;
$Stat_NumPosts  = 0;

//JS-массив
$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', '')
					);

//заголовок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Main'], array('forumname'=>$Config_ForumName));

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

//Начало вывода форумов
$MainOutput .= '<table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">';

if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
	$AdminPanelHeaders = '<td class="MainColumnName" style="width:16px;">&nbsp;</td>
						  <td class="MainColumnName" style="width:16px;">&nbsp;</td>';
}
else {
	$AdminPanelHeaders = '';
}

//получение colspan
$CharterColspan = OBB_SHOW_ADMIN_ELEMENTS ? Echo_GetColspan (8, 6) : 6;

//Вывод заголовков таблицы форумов
$MainOutput .= '<tr class="MainColumnRow">
					<td colspan="3"   class="MainColumnName"><span>'  . $ForumLang['ForumsTitle']    . '</span></td>
					<td style="width:53px;"  class="MainColumnName"><span>'  . $ForumLang['NumThemesTitle'] . '</span></td>
					<td style="width:70px;" class="MainColumnName"><span>'   . $ForumLang['NumPostsTitle']  . '</span></td>
					<td style="width:350px;" class="MainColumnName"><span>'  . $ForumLang['UpdateTitle']    . '</span></td>
					' . $AdminPanelHeaders . '
				</tr>';

//массив разделов
$ChartersArray = array();
$SQL = 'SELECT CharterID, CharterName FROM charters';
$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
if (!$Query) {
	OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
	$ChartersID   = $Row['CharterID'];
	$ChartersName = $Row['CharterName'];
	$ChartersArray[$ChartersID] = $ChartersName;
}

//Если ошибок нет, делаем великий запрос форумов :)
$SQL = 'SELECT forums_list.ForumID AS IdForum,
			forums_list.ForumName AS NameForum,
			forums_list.ForumDescription AS Description,
			forums_list.ForumNumThemes AS NumTh,
			forums_list.ForumNumPosts AS NumP,
			forums_list.ForumLastThemeID AS LastThID,
			forums_list.ForumLastPostID AS LastPID,
			forums_list.ForumLastUserID AS LastUID,
			forums_list.ForumLastUserName AS LastUName,
			forums_list.ForumLastUpDate AS LastUpDate,
			forums_list.ForumBlock AS Block,
			forums_list.CharterID AS IDCharter,
			themes.ThemeName AS THName 
		FROM forums_list
		LEFT JOIN themes ON forums_list.ForumLastThemeID = themes.ThemeID
		LEFT JOIN charters ON forums_list.CharterID = charters.CharterID
		ORDER BY charters.CharterPosition, charters.CharterID, forums_list.ForumID';
$ForumListQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
if (!$ForumListQuery) {
	OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}

//Если есть хоть один форум в таблице, цикл запроса форума
if (DB_NumRows ($Config_DBType, $ForumListQuery) > 0) {
	while ($ForumListRow = DB_FetchAssoc ($Config_DBType, $ForumListQuery)) {
		//для облегчения
		$IDCharter   = $ForumListRow['IDCharter'];
		$NameCharter = $ChartersArray[$IDCharter];
		$IdForum     = $ForumListRow['IdForum'];
		$NameForum   = $ForumListRow['NameForum'];
		$Description = $ForumListRow['Description'];
		$NumTh       = $ForumListRow['NumTh'];
		$NumP        = $ForumListRow['NumP'];
		$LastThID    = $ForumListRow['LastThID'];
		$LastPID     = $ForumListRow['LastPID'];
		$LastUID     = $ForumListRow['LastUID'];
		$LastUName   = $ForumListRow['LastUName'];
		$LastUpDate  = $ForumListRow['LastUpDate'];
		$Block       = $ForumListRow['Block'];
		$ThemeName   = $ForumListRow['THName'];

		//Если ид категории не равен предыдущему, меняем флаг смены категории
		if (!isset ($ChartChange) || $CurrentChart <> $IDCharter) {
			$ChartChange = 1;
		}

		//1.Строка где написана новая категория
		if ($ChartChange == 1) {
			$ChartChange = 0;
			$NewCharter = '<tr>
								<td class="ForumCharter" colspan="' . $CharterColspan . '">
									<div style="float:left;" class="ForumCharterBullet">' . $NameCharter . '</div>
									<div style="float:right;">
										<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'Charter' . $IDCharter . '\',\'' . OBB_IMAGE_DIR . '\',\'Chrtr' . $IDCharter . '\');"><img id="Chrtr' . $IDCharter . '" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
									</div>
								</td>
							</tr>';
		}
		else {
			$NewCharter = '';
		}

		//2.Иконка форума
		$ForumIcon = Echo_GetCharterIcon ($IDCharter, $Description);
		
		//3.Иконка степени блокировки форума
		if ($Block == 'yes') {
			$ForumBlockDiv = '<div class="ForumBlockDiv">' . Defence_HTMLSpecials ($ForumLang['ForumsBlockTitle']) . '</div>';
		}
		else {
			$ForumBlockDiv = '';
		}

		//4.Иконка RSS
		$RSSIconHref = Defence_HTMLSpecials ($SelfName . '?action=rss&id_forum=' . $IdForum);
		$RSSIcon = '<a href="' . $RSSIconHref . '">
						<img alt="" title="RSS" src="' . OBB_IMAGE_DIR . '/rss_small.png" />
					</a>';

		//5.Ячейка с именем/описанием форума
		$RSSForumCur = '<img class="RSSForumIcon" alt="RSS" title="RSS" src="' . OBB_IMAGE_DIR . '/rss_small.png" /><a class="CurRSSHref" href="' . $RSSIconHref . '">' . $ForumLang['RSSOfCurForum'] . '</a>';
		$ForumNameHref = Defence_HTMLSpecials ($SelfName . '?action=forumview&id_forum=' . $IdForum);
		$ForumName = '<div class="ForumNameDiv">
						<a href="' . $ForumNameHref . '">' . $NameForum . '</a>' . $RSSForumCur . '
					 </div>
					  <div class="ForumDescriptionDiv">
						' . $Description . '
					  </div>' . $ForumBlockDiv;

		//6.Ячейка с обновлением форума
		//если к-во постов и тем не равно 0 (хотя это пока, после что-нить другое придумаю)
		if ($ForumListRow['NumP'] > '0' && $ForumListRow['NumTh'] > '0') {
			//6.1 Время обновления
			if ($LastUpDate <> '') {
				$UpdateTimeConvert = Main_ConvertDate ($LastUpDate, $ForumLang['DateArray']);
				$UpdateTime = '<div class="UpdateTime">
									<acronym class="UpdateAcr" title="' . $UpdateTimeConvert . '">' . $UpdateTimeConvert . '</acronym>
								</div>';
			}
			else {
				$UpdateTime = '';
			}

			//6.2 Пользователь-обновитель
			if ($LastUName <> '') {
				if ($LastUID > 0) {
					$AuthorNameHref = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $LastUID);
					$UpdateAuthorName = '<a title="' . $LastUName . '" class="UpdateUserLink" href="' . $AuthorNameHref . '">' . Defence_HTMLSpecials ($LastUName) . '</a>';
				}
				else {
					$UpdateAuthorName = '<span class="UpdateUserText" >' . Defence_HTMLSpecials ($LastUName) . '</span>';
				}
				$UserUpdate = '<div class="UpdateAuthor">
									<span class="UpdateAuthorTitle">' . $ForumLang['UpdateAuthor'] . ':</span>&nbsp;' . $UpdateAuthorName . '
								</div>';
			}
			else {
				$UserUpdate = '';
			}

			//6.3 Тема обновления
			if (Main_Strlen ($ThemeName) > 25) {
				$LastThemeName = mb_substr ($ThemeName, 0, 24, 'UTF-8') . '...';
			}
			else {
				$LastThemeName = $ThemeName;
			}

			//6.4 Основные ссылки
			$LastPostURLHref = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $LastThID . '&last_post=' . $LastPID);
			$LastPostURL  = $LastPostURLHref . '#pid' . $LastPID;
			$LastThemeURLHref = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $LastThID);
			$LastThemeURL = $LastThemeURLHref;
			$UpdateLastPost  = '<a title="' . $ForumLang['GotoLastPost'] . '" href="' . $LastPostURL  . '"><img alt="' . $ForumLang['GotoLastPost'] . '" src="' . OBB_IMAGE_DIR . '/last_post.gif" /></a>';
			$ThemeNameHref = '<div class="UpdateThemeName">
								' . $UpdateLastPost . '&nbsp;<a href="' . Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $IdForum . '&id_theme=' . $LastThID) . '">' . Defence_HTMLSpecials ($LastThemeName) . '</a>
							</div>';
			
			$UpdateForum = $ThemeNameHref . $UpdateTime . $UserUpdate;
		}
		else {
			$UpdateForum = '<div class="ForumNoUpdates">' . $ForumLang['NoUpdates'] . '</div>';
		}

		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
			$AdminEditHref = Defence_HTMLSpecials ($SelfName . '?action=admin&adm=forum&fact=edit&fid=' . $IdForum);
			$AdminDelHref  = Defence_HTMLSpecials ($SelfName . '?action=admin&adm=forum&fact=del&fid='  . $IdForum);
			$AdminForumsPanel ='<td class="AdminShowFirst">
									<a title="' . $ForumLang['ForumsEditForum']   . '" href="' . $AdminEditHref . '"><img title="' . $ForumLang['ForumsEditForum']   . '" alt="" src="' . OBB_IMAGE_DIR . '/admin_edit.png" /></a>
								</td>
								<td class="AdminShow">
									<a title="' . $ForumLang['ForumsDeleteForum'] . '" href="' . $AdminDelHref  . '"><img title="' . $ForumLang['ForumsDeleteForum'] . '" alt="" src="' . OBB_IMAGE_DIR .  '/admin_delete.png" /></a>
								</td>';
		}
		else {
			$AdminForumsPanel = '';
		}

		//Вывод данных форума
		$MainOutput .= $NewCharter .
						'<tr class="ForumMainTR Charter' . $IDCharter . '">
							<td class="ForumIconTD" style="width:40px;">
								' . $ForumIcon . '
							</td>
							<td class="ForumNameTD InForumList">
								' . $ForumName . '
							</td>
							<td style="width:26px;" class="RSSIconTD">
								<!--' . $RSSIcon . '-->
							</td>
							<td class="ForumNumThemesTD">' . $NumTh . '</td>
							<td class="ForumNumPostsTD">' . $NumP . '</td>
							<td class="ForumUpdateTD">' . $UpdateForum . '</td>
							' . $AdminForumsPanel . '
						</tr>';

		//Смена ид категории (для сравнения в след. итерации)
		$CurrentChart = $IDCharter;

		//Приплюсовывание статистических данных
		$Stat_NumForums += 1;
		$Stat_NumThemes += $NumTh;
		$Stat_NumPosts  += $NumP;
	}
	$MainOutput .= '<tr>
						<td class="ForumsTableBottom" colspan="' . $CharterColspan . '">
							<div><!-- --></div>
						</td>
					</tr>';
}
else {
	$MainOutput .= '<tr>
						<td colspan="' . $CharterColspan . '">' . $ForumLang['NoForums'] . '</td>
					</tr>';
}

//Конец вывода форумов 
$MainOutput .= '</table>';

//ФЛАГ:
//Если 
// --разрешен вывод основной статистика
// --разрешен вывод онлайн статистики
if (
		($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_MAIN_STATISTICS   && $UserGroups_Permissions['ShowMainStatistics']))
		||
		($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_ONLINE_STATISTICS && $UserGroups_Permissions['ShowMainOnline']))
) {
	$ShowStatisticHeaders = TRUE;
}
else {
	$ShowStatisticHeaders = false;
}

if ($ShowStatisticHeaders) {
	//Разделительный див
	$MainOutput .= '<div class="BlockDelimiterDiv"><!-- --></div>';

	//Заголовок таблицы статистики
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td colspan="2" class="MainColumnName"><span>' . $ForumLang['MainStatistics'] . '</span></td>
						</tr>';

	///Вывод основной статистики
	//  -если админ
	//  -если разрешено в общем и в данной группе
	if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_MAIN_STATISTICS && $UserGroups_Permissions['ShowMainStatistics'])) {
		//к-во пользователей
		$SQL = 'SELECT StatisticsValue FROM statistics WHERE StatisticsKey=\'1\'';
		$UserStatQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$UserStatQuery) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		$UserStatRow = DB_FetchAssoc ($Config_DBType, $UserStatQuery);
		$NumOfUsers  = $UserStatRow['StatisticsValue'];

		if ($NumOfUsers > 0) {
			$LastUserSQL = 'SELECT UserID, UserLogin, GroupID FROM users WHERE UserIsActivate = \'yes\' ORDER BY UserRegDate DESC LIMIT 1';
			$LastUserQuery = DB_Query ($Config_DBType, $LastUserSQL, $ForumConnection);
			if (!$LastUserQuery) {
				OBB_Main_Log ($LastUserSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
			$UserLastRow   = DB_FetchAssoc ($Config_DBType, $LastUserQuery);
			$LastUserID    = $UserLastRow['UserID'];
			$LastUser      = $UserLastRow['UserLogin'];
			$LastUserGroup = $UserLastRow['GroupID'];
		}

		//Присвоение (удобство)
		$NumOfForums = $Stat_NumForums;
		$NumOfThemes = $Stat_NumThemes;
		$NumOfPosts  = $Stat_NumPosts;

		//Статистика
		$MainOutput .= '
							<tr>
								<td class="ForumCharter" colspan="2">
									<div style="float:left;" class="ForumCharterBullet">' . $ForumLang['CommonMainStatistics'] . '</div>
									<div style="float:right;">
										<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'CommonStatisticsIdent\',\'' . OBB_IMAGE_DIR . '\',\'CommonStatHide\');"><img id="CommonStatHide" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
									</div>
								</td>
							</tr>
							<tr class="CommonStatisticsIdent">
								<td class="ForumStatTD" style="width:50px;">
									<img title="' . $ForumLang['CommonMainStatistics'] . '" alt="' . $ForumLang['CommonMainStatistics'] . '" class="ForumStats" src="' . OBB_IMAGE_DIR . '/statistics.png" />
								</td>
								<td class="ForumStatContent" style="text-align:left; vertical-align:top;">
									<div class="StatisticsDiv">
										' . $ForumLang['MainStat_NumForums']    . ':&nbsp;<span class="StatisticsNum">' . $NumOfForums . '</span>
									</div>
									<div class="StatisticsDiv">
										' . $ForumLang['MainStat_NumThemes']    . ':&nbsp;<span class="StatisticsNum">' . $NumOfThemes . '</span>
									</div>
									<div class="StatisticsDiv">
										' . $ForumLang['MainStat_NumPosts']     . ':&nbsp;<span class="StatisticsNum">' . $NumOfPosts  . '</span>
									</div>
									
								</td>
							</tr>
							<tr>
								<td class="ForumCharter" colspan="2">
									<div style="float:left;" class="ForumCharterBullet">' . $ForumLang['MainStatisticsUsers'] . '</div>
									<div style="float:right;">
										<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'CommonStatisticsUIdent\',\'' . OBB_IMAGE_DIR . '\',\'CommonStatUHide\');"><img id="CommonStatUHide" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
									</div>
								</td>
							</tr>
							<tr class="CommonStatisticsUIdent">
								<td class="ForumStatTD" style="width:50px;">
									<img title="' . $ForumLang['MainStatisticsUsers'] . '" alt="' . $ForumLang['MainStatisticsUsers'] . '" class="ForumStats" src="' . OBB_IMAGE_DIR . '/user_statistics.png" />
								</td>
								<td class="ForumStatContent" style="text-align:left; vertical-align:top;">
									<div class="StatisticsDiv">
										' . $ForumLang['MainStat_NumRegUsers']  . ':&nbsp;<span class="StatisticsNum">' . $NumOfUsers  . '</span>
									</div>';

		//вывод последнего зарегистрированного пользователя
		if ($NumOfUsers > 0) {
			$LastUserGroupColor = $GroupsArray[$LastUserGroup]['Color'];
			$MainOutput .= '<div class="StatisticsDiv">
								' . $ForumLang['MainStat_LastUser']  . ': 
								<a class="UserGroupHref" style="color:#' . $LastUserGroupColor . ';" href="' . Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $LastUserID) . '"><span class="UserGroupSpan" style="color:#' . $LastUserGroupColor . ';">' . $LastUser  . '</span></a>
							</div>';
		}

		$MainOutput .= '</td>
					</tr>';
	}

	//Вывод информации о гостях/пользователях онлайн
	//  -если админ
	//  -если в общем и группе разрешено
	if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_ONLINE_STATISTICS && $UserGroups_Permissions['ShowMainOnline'])) {
		//выборка из зареганных онлайн-пользователей
		$SQL = 'SELECT COUNT(*)	AS NumOfRegOnline
				FROM user_activity
				WHERE UserLastLogin > (' . $Now . ' - ' . OBB_USER_ONLINE_TIME . ')
				AND UserIsOnline = \'yes\'';

		$UserOnlineQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$UserOnlineQuery) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$UserOnlineRow = DB_FetchAssoc ($Config_DBType, $UserOnlineQuery);
		$NumOfRegOnline = $UserOnlineRow['NumOfRegOnline'];

		//выборка из гостей онлайн
		$SQL = 'SELECT COUNT(*)	AS NumOfGuestOnline	FROM guest_activity	WHERE GuestLastUpdate > (' . $Now . ' - ' . OBB_USER_ONLINE_TIME . ')';
		$GuestOnlineQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$GuestOnlineQuery) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$GuestOnlineRow = DB_FetchAssoc ($Config_DBType, $GuestOnlineQuery);
		$NumOfGuestOnline = $GuestOnlineRow['NumOfGuestOnline'];

		$CommonNumOfOnlineUsers = $NumOfGuestOnline + $NumOfRegOnline;

		//формирование списка онлайн-пользователей
		ob_start ();
		include (OBB_SHOW_DIR . '/UsersOnline.php');
		$UsersOnlineList = ob_get_contents ();
		ob_end_clean ();

		//Онлайн
		$NumOnlineMinutes = OBB_USER_ONLINE_TIME/60;
		$OnlineUsersInfoString = OBB_Main_ReplaceSymbols (
															$ForumLang['ForumsUsersInfoString'], 
															array(
																'common'=>$CommonNumOfOnlineUsers,
																'minutes'=>$NumOnlineMinutes,
																'users'=>$NumOfRegOnline,
																'guests'=>$NumOfGuestOnline)
														);
		$MainOutput .= '<tr>
							<td class="ForumCharter" colspan="2">
								<div style="float:left;" class="ForumCharterBullet">' . $ForumLang['MainOnline'] . '</div>
								<div style="float:right;">
									<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'CommonStatisticsUOnlineIdent\',\'' . OBB_IMAGE_DIR . '\',\'CommonStatOnlHide\');"><img id="CommonStatOnlHide" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
								</div>
							</td>
						</tr>
						<tr class="CommonStatisticsUOnlineIdent">
							<td class="ForumStatTD" style="width:50px;">
								<img title="' . $ForumLang['MainOnline'] . '" alt="' . $ForumLang['MainOnline'] . '" class="ForumStats" src="' . OBB_IMAGE_DIR . '/user_online.png" />
							</td>
							<td class="ForumStatContent" style="text-align:left; vertical-align:top;">
								<div>' . $OnlineUsersInfoString . '</div>
								<div>
									<strong>' . $ForumLang['ForumsOnlineUsersList'] . ':</strong>
									' . $UsersOnlineList . '
								</div>
							</td>
						</tr>';
	}

	//Вывод конца таблицы статистики
	$MainOutput .= '<tr>
						<td class="ForumsTableBottom" colspan="2">
							<div><!-- --></div>
						</td>
					</tr>';
	$MainOutput .= '</table>';
}

//ФЛАГ:
//Если разрешены
//  --вывод ваших возможностей
//  --быстрый переход по форумам
if (
		(OBB_SHOW_YOUR_ABILITIES)
		||
		($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_MAIN_FASTGO && $UserGroups_Permissions['ShowMainFastGoto']))
	) {
	$ShowOtherFlag = TRUE;
}
else {
	$ShowOtherFlag = false;
}

if ($ShowOtherFlag) {
	//Разделительный див
	$MainOutput .= '<div class="OtherShowDelimiterDiv"><!-- --></div>';

	$MainOutput .= '<div class="OtherShowDiv">';
	//Вывод "Ваших возможностей"
	if (OBB_SHOW_YOUR_ABILITIES) {
		$MainOutput .= '<div class="OtherLeftDiv">
							' . Echo_ShowGroupAbilities () . '
						</div>';
	}

	//быстрый переход по форумам
	//  -если админ
	//  -если разрешено в общем и в группе
	if ($_SESSION['UserData']['UserType'] == 'admin' || (OBB_SHOW_MAIN_FASTGO && $UserGroups_Permissions['ShowMainFastGoto'])) {
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