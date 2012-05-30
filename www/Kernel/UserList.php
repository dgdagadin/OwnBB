<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//проверка на доступ
if ($_SESSION['UserData']['UserType'] <> 'admin' && (!$UserGroups_Permissions['VisitUserlist'] || !OBB_SHOW_USERLIST)) {
	$Permissions_ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	OBB_Main_ShowError ('common_no_access', $Permissions_ErrorFlag, $ForumLang['Errors']);
}

//подключения
include (OBB_KERNEL_DIR . '/PageLib.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/UserList_' . $Config_Lang . '.php');

//Определение переменных
$FieldArray  = array ('login', 'r_date', 'n_posts');
$MethodArray = array ('asc', 'desc');
if (isset ($_GET['u_num']) && ctype_digit ($_GET['u_num']) && $_GET['u_num'] <> '') {
	$UsersPerPage = intval ($_GET['u_num']);
	if ($UsersPerPage == 0) {
		$UsersPerPage = 1;
	}
	else if ($UsersPerPage > OBB_MAX_USERS_PER_PAGE) {
		$UsersPerPage = OBB_MAX_USERS_PER_PAGE;
	}
	$UserNumUrl = '&u_num=' . $UsersPerPage;
}
else {
	$UsersPerPage = OBB_NUM_USERS_PER_PAGE;
	$UserNumUrl = $UserNumHid = '';
}
$FilterSort = isset ($_GET['f_sort'])  && in_array ($_GET['f_sort'], $MethodArray) ? trim ($_GET['f_sort'])  : 'asc';
$SortMethod = $FilterSort == 'desc' ? 'DESC' : 'ASC';
if (isset ($_GET['f_field']) && in_array ($_GET['f_field'], $FieldArray)) {
	$Filter = trim ($_GET['f_field']);
	switch ($Filter) {
		case 'login':
		default : {
			if ($SortMethod == 'ASC') {
				$LoginSortURL = '&f_sort=desc';
				$SortURL = '';
				$LoginSortImage = 'up';
				$LoginSortTitle = $ForumLang['UserlistSortUp'];
			}
			else {
				$LoginSortURL = '';
				$SortURL = '&f_sort=desc';
				$LoginSortImage = 'down';
				$LoginSortTitle = $ForumLang['UserlistSortDown'];
			}
			$LoginSortArrow = '<span><img alt="" title="' . $LoginSortTitle . '" src="' . OBB_IMAGE_DIR . '/sort_' . $LoginSortImage . '.png" /></span>';
			$OrderField = 'UserLogin';
			$DateSortURL = '';
			$DateSortArrow = '';
			$PostSortURL = '';
			$PostSortArrow = '';
			break;
		}

		case 'r_date': {
			if ($SortMethod == 'ASC') {
				$DateSortURL = '&f_sort=desc';
				$SortURL = '';
				$DateSortImage = 'up';
				$DateSortTitle = $ForumLang['UserlistSortUp'];
			}
			else {
				$DateSortURL = '';
				$SortURL = '&f_sort=desc';
				$DateSortImage = 'down';
				$DateSortTitle = $ForumLang['UserlistSortDown'];
			}
			$DateSortArrow = '<span><img alt="" title="' . $DateSortTitle . '" src="' . OBB_IMAGE_DIR . '/sort_' . $DateSortImage . '.png" /></span>';
			$OrderField = 'UserRegDate';
			$LoginSortURL = '';
			$LoginSortArrow = '';
			$PostSortURL = '';
			$PostSortArrow = '';
			break;
		}

		case 'n_posts': {
			if ($SortMethod == 'ASC') {
				$PostSortURL = '&f_sort=desc';
				$SortURL = '';
				$PostSortImage = 'up';
				$PostSortTitle = $ForumLang['UserlistSortUp'];
			}
			else {
				$PostSortURL = '';
				$SortURL = '&f_sort=desc';
				$PostSortImage = 'down';
				$PostSortTitle = $ForumLang['UserlistSortDown'];
			}
			$PostSortArrow = '<span><img alt="" title="' . $PostSortTitle . '" src="' . OBB_IMAGE_DIR . '/sort_' . $PostSortImage . '.png" /></span>';
			$OrderField = 'UserNumPosts';
			$LoginSortURL = '';
			$LoginSortArrow = '';
			$DateSortURL = '';
			$DateSortArrow = '';
			break;
		}
	}
	$OrderField = 'users.' . $OrderField;
	$OrderURL = '&f_field=' . urlencode ($Filter);
	$OrderHid = '<input type="hidden" name="f_field" value="' . urlencode ($Filter) . '" />';
}
else {
	$Filter = 'login';
	$OrderField = 'users.UserLogin';
	if ($SortMethod == 'ASC') {
		$LoginSortURL = '&f_sort=desc';
		$SortURL = '';
		$LoginSortImage = 'up';
		$LoginSortTitle = $ForumLang['UserlistSortUp'];
	}
	else {
		$LoginSortURL = '';
		$SortURL = '&f_sort=desc';
		$LoginSortImage = 'down';
		$LoginSortTitle = $ForumLang['UserlistSortDown'];
	}
	$LoginSortArrow = '<span><img title="' . $LoginSortTitle . '" src="' . OBB_IMAGE_DIR . '/sort_' . $LoginSortImage . '.png" border="0" /></span>';

	$DateSortURL = '';
	$DateSortArrow = '';
	$PostSortURL = '';
	$PostSortArrow = '';
	$OrderURL = '';
	$OrderHid = '';
}

//фильтр по имени пользователя и онлайн
$FilterUserLogin  = isset ($_GET['user_name'])   && Main_Strlen ($_GET['user_name']) <> '' ? trim ($_GET['user_name']) : '';
$FilterUserOnline = isset ($_GET['user_online']) && $_GET['user_online'] == TRUE ? 'yes' : 'no';

//части запросов
$FilterArray = array ();

//имя пользователя
if (Main_Strlen ($FilterUserLogin) > 0) {
	$UserNameSQL = 'users.UserLogin LIKE \'%' . Defence_EscapeString ($Config_DBType, $FilterUserLogin) . '%\'';
	$FilterArray[] = $UserNameSQL;
	$URLName = '&user_name=' . urlencode ($_GET['user_name']);
}
else {
	$URLName = '';
}

//онлайн
if ($FilterUserOnline == 'yes') {
	$UserOnlineSQL = 'user_activity.UserIsOnline = \'yes\'';
	$FilterArray[] = $UserOnlineSQL;
	$URLOnline = '&user_online=on';
}
else {
	$URLOnline = '';
}

//активация пользователей
$SQLActivate = '';
if ($_SESSION['UserData']['UserType'] <> 'admin') {
	$SQLActivate = 'users.UserIsActivate = \'yes\'';
}
else {
	if (isset ($_GET['user_act'])) {
		$SQLActivate = 'users.UserIsActivate = \'yes\'';
	}
}
if (Main_Strlen ($SQLActivate) > 0) {
	$FilterArray[] = $SQLActivate;
}

//собираем в строку
if (sizeof ($FilterArray) > 0) {
	$FilterSQLString = ' WHERE ' . implode (' AND ', $FilterArray) . ' ';
}
else {
	$FilterSQLString = '';
}

//переменная "сейчас"
$Now = time ();

//Функция обновления онлайн-информации о пользователе/госте
$CurAction = 'user_list';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//Главная переменнвя вывода
$MainOutput = '';

//Формируем массив главной навигации
$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array ('', $ForumLang['UserlistTitle'])
				);

//JS-массив
$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', '')
					);

//заголовок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Userlist'], array('forumname'=>$Config_ForumName));

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

//информация о бане - только для администратора
if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
	$SQLBanFields = 'user_bans.UserBanTime AS UBanTime, user_bans.UserBanPeriod AS UBanPeriod,';
	$SQLBanJoin = ' LEFT JOIN user_bans ON user_bans.UserID = users.UserID ';
}
else {
	$SQLBanFields = '';
	$SQLBanJoin = '';
}

//Количество пользователей
$NumUsersSQL = 'SELECT COUNT(*) AS NumUsers FROM users LEFT JOIN user_activity ON users.UserID = user_activity.UserID ' . $SQLBanJoin . $FilterSQLString;
$NumUsersQuery = DB_Query ($Config_DBType, $NumUsersSQL, $ForumConnection);
if (!$NumUsersQuery) {
	OBB_Main_Log ($NumUsersSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$NumUsers = DB_Result ($Config_DBType, $NumUsersQuery, 0, 0);

//определение страницы
$NumUserPages = $NumUsers > 0 ? ceil ($NumUsers/$UsersPerPage) : 1;
$CurrentPage = !isset ($_GET['page']) || !ctype_digit ($_GET['page']) ? 1 : intval ($_GET['page']);
if ($CurrentPage < 1) {
	$CurrentPage = 1;
}
else if ($CurrentPage > $NumUserPages) {
	$CurrentPage = $NumUserPages;
}

//флаг "Только онлайн"
$OnlineChecked = $FilterUserOnline == 'yes' ? ' checked="checked"' : '';

//форма поиска по пользователям
//  --если админ - флаг "Только активированные пользователи"
if ($_SESSION['UserData']['UserType'] == 'admin') {
	$ActivatedChecked = isset ($_GET['user_act']) ? ' checked="checked"' : '';
	$OnlyActivatedFlag = '  <div>
								<input id="Labels_user_act"' . $ActivatedChecked . ' type="checkbox" name="user_act" />
								<span class="Usual"><label for="Labels_user_act">' . $ForumLang['UserlistFilterActivated'] . '</label></span>
							</div>';

	if (isset ($_GET['user_act'])) {
		$URLActivate = '&user_act=on';
	}
	else {
		$URLActivate = '';
	}
}
else {
	$OnlyActivatedFlag = '';
	$URLActivate = '';
}

//  --форма фильтра пользователей
$FilterForm = '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
					<tr class="MainColumnRow">
						<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName"><span>' . $ForumLang['UserlistFilterOption'] . '</span></td>
					</tr>
					<tr class="ForumMainTR">
						<td style="padding:0;">
							<form id="UserFilterForm" style="padding:0; margin:0;" action="' . $SelfName . '" method="get">
								<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="width:207px; vertical-align:top;" class="FormTitleTD">
											<input type="hidden" name="action" value="usersview" />
											<div class="InputTitle">
												' . $ForumLang['UserlistShowNumUsers'] . '
											</div>
										</td>
										<td class="FormInputTD">
											<input style="width:100px;" class="InpEl InpText" type="text" maxlength="5" name="u_num" value="' . intval ($UsersPerPage) . '" />
										</td>
									</tr>
									<tr>
										<td style="width:207px; vertical-align:top;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserlistFilterLogin'] . '
											</div>
										</td>
										<td class="FormInputTD">
											<input style="width:280px;" class="InpEl InpText" type="text" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" name="user_name" value="' . Defence_HTMLSpecials ($FilterUserLogin) . '" />
										</td>
									</tr>
									<tr>
										<td style="width:160px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserlistOtherOptions'] . '
											</div>
										</td>
										<td class="FormInputTD">
											<div>
												<input id="Labels_user_online"' . $OnlineChecked . ' type="checkbox" name="user_online" />
												<span class="Usual"><label for="Labels_user_online">' . $ForumLang['UserlistFilterOnline'] . '</label></span>
											</div>
											' . $OnlyActivatedFlag . '
										</td>
									</tr>
									<tr>
										<td colspan="2" class="FormInputTD CenterTD">
											<div id="SubmitButtonDiv">
												<input class="InpButton" id="EditSubmit" type="submit" value="' . $ForumLang['UserlistSubmit'] . '" />
												<input class="InpButton" type="button" name="Adding" value="' . $ForumLang['UserListReset'] . '" />
											</div>
											' . $OrderHid . '
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

$MainOutput .= $FilterForm;

//разделитель
$MainOutput .= '<div style="height:20px;"><!-- --></div>';

//постраничный вывод
$PageURL  = $SelfName . '?action=usersview' . $UserNumUrl . $OrderURL . $SortURL . $URLOnline . $URLActivate . $URLName;
$PageList = $PageList = PL_PageList ($NumUserPages, $CurrentPage, $PageURL, 'page', '5', '5', '5', '5');
$PageList = '<div>' . $PageList . '</div>';

//постраничная навигация
$MainOutput .= $PageList;

//разделительный div
$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

//общая часть ссылки
$HeaderURL = $SelfName . '?action=usersview' . $UserNumUrl . $URLOnline . $URLActivate . $URLName . '&f_field=';
$HeaderURL = Defence_HTMLSpecials ($HeaderURL);

//получение ширины колонки
$UserLoginColumn = OBB_SHOW_ADMIN_ELEMENTS ? Echo_GetColumnWidth (19, 23) : 23;

//colspan
$Colspan = OBB_SHOW_ADMIN_ELEMENTS ? Echo_GetColspan (8, 6) : 6;

//заголовки админской панели
if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
	$UserListAdminHeaders = '<td class="ForumCharter" style="width:16px;">
								&nbsp;
							</td>
							 <td class="ForumCharter" style="width:16px;">
								&nbsp;
							</td>';
}
else {
	$UserListAdminHeaders = '';
}

//экранирование частей ссылок
$LoginSortURL = Defence_HTMLSpecials ($LoginSortURL);
$DateSortURL  = Defence_HTMLSpecials ($DateSortURL);
$PostSortURL  = Defence_HTMLSpecials ($PostSortURL);

//заголвки таблицы пользователей
$MainOutput .= ' <table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
					<tr class="MainColumnRow">
						<td colspan="' . $Colspan . '" class="MainColumnName">
							<span>' . $ForumLang['UserlistTitle'] . '</span>
						</td>
					</tr>
					<tr>
						<td class="ForumCharter" style="text-align:left; border-top:1px solid #FFFFFF;">
							<a href="' . $HeaderURL . 'login' . $LoginSortURL . '"><span class="ThemesTitle">' . $ForumLang['UserlistMainLogin'] . '</span></a>' . $LoginSortArrow . '
						</td>
						<td class="ForumCharter" style="text-align:center; border-top:1px solid #FFFFFF; width:120px;">
							<a href="' . $HeaderURL . 'r_date' . $DateSortURL . '"><span class="ThemesTitle">' . $ForumLang['UserlistMainRDate'] . '</span></a>' . $DateSortArrow . '
						</td>
						<td class="ForumCharter" style="text-align:center; border-top:1px solid #FFFFFF; width:85px;">
							<a href="' . $HeaderURL . 'n_posts' . $PostSortURL . '"><span class="ThemesPosts">' . $ForumLang['UserlistMainNumPosts'] . '</span></a>' . $PostSortArrow . '
						</td>
						<td class="ForumCharter"  style="text-align:center; border-top:1px solid #FFFFFF; width:135px;">
							<span class="ThemesTitle">' . $ForumLang['UserlistMainGroup'] . '</span>
						</td>
						<td class="ForumCharter"  style="text-align:center; border-top:1px solid #FFFFFF; width:50px;">
							<span class="ThemesTitle">' . $ForumLang['UserlistMainMail'] . '</span>
						</td>
						<td class="ForumCharter"  style="border-top:1px solid #FFFFFF; width:330px;">
							<span class="ThemesTitle">&nbsp;&nbsp;&nbsp;' . $ForumLang['UserlistMainStatus'] . '</span>
						</td>
						' . $UserListAdminHeaders . '
					</tr>';

if ($NumUsers > 0) {
	//объединение формы для фильтра и страниц
	$PagesAndFilter = '<div>' . $PageList . '</div>';

	//великий запрос пользователей :)
	$GetUsersSQL = 'SELECT
						users.UserID AS UID,
						users.UserLogin AS ULogin,
						' . $SQLBanFields . '
						users.UserRegDate AS URegDate,
						users.UserSex AS USex,
						users.UserNumPosts AS UNumPosts,
						users.GroupID AS UGroupID,
						users.UserOtherMail AS UOtherMail,
						users.UserIsActivate AS UActivated,
						user_activity.UserIsOnline AS UIsOnline,
						user_activity.UserLastLogin AS ULastLogin,
						user_activity.UserLastAction AS ULastAction
					FROM
						users
					LEFT JOIN
						user_activity ON users.UserID = user_activity.UserID
					' . $SQLBanJoin      . '
					' . $FilterSQLString . '
					ORDER BY
						' . $OrderField . ' ' . $SortMethod . '
					LIMIT
						' . $UsersPerPage * ($CurrentPage - 1) . ', ' . $UsersPerPage;
	$GetUsersQuery = DB_Query ($Config_DBType, $GetUsersSQL, $ForumConnection);
	if (!$GetUsersQuery) {
		OBB_Main_Log ($GetUsersSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	//сейчас и неделя
	$NowTime = time();
	$Week = 60*60*24*7;

	//цикл
	while ($UserListRow = DB_FetchAssoc ($Config_DBType, $GetUsersQuery)) {
		//присвоение - для удобства
		$UID         = $UserListRow['UID'];
		$ULogin      = $UserListRow['ULogin'];
		$URegDate    = $UserListRow['URegDate'];
		$USex        = $UserListRow['USex'];
		$UNumPosts   = $UserListRow['UNumPosts'];
		$UGroupID    = $UserListRow['UGroupID'];
		$UIsOnline   = $UserListRow['UIsOnline'];
		$ULastLogin  = $UserListRow['ULastLogin'];
		$ULastAction = $UserListRow['ULastAction'];
		$UOtherMail  = $UserListRow['UOtherMail'];

		//1.логин
		$UserLogin = Defence_HTMLSpecials ($ULogin);
		$GotoProfile = OBB_Main_ReplaceSymbols ($ForumLang['UserlistGotoProfile'], array('user'=>$UserLogin));
		$UserLoginLink = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . intval ($UID));
		$LoginHref = '<a title="' . $GotoProfile . '" class="LoginLink" href="' . $UserLoginLink . '">' . $UserLogin . '</a>';
		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
			$AdminInfoArray = array ();

			//  --забанен ли пользователь
			$UserBanDate   = $UserListRow['UBanTime'];
			$UserBanPeriod = $UserListRow['UBanPeriod'];
			$UserIsBanned = !OBB_Main_IsEmpty ($UserBanDate) && !OBB_Main_IsEmpty ($UserBanPeriod) && (bool)((time() - $UserBanDate) < $UserBanPeriod);
			if ($UserIsBanned) {
				$UserBanInfo = '<span style="color:red"><strong>' . $ForumLang['UserlistIsBanned'] . '</strong></span>';
				$AdminInfoArray[] = $UserBanInfo;

			}
			else {
				$UserBanInfo = '';
			}

			//  --активирован ли пользователь
			$UserIsActivated = $UserListRow['UActivated'] == 'yes';
			if (!$UserIsActivated) {
				$ActivationTimeRest = time() - $URegDate;
				if ($ActivationTimeRest > $Week) {
					$UserActivateInfo = '<strong>' . $ForumLang['UserlistUserActivationDelayed'] . '!!!</strong>';
				}
				else {
					$UserActivateInfo = '<strong>' . $ForumLang['UserlistUserIsNotActivated'] . '</strong>';
				}
				$UserActivationInfo = '<span style="color:#f40e0e">' . $UserActivateInfo . '</span>';
				$AdminInfoArray[] = $UserActivationInfo;
			}
			else {
				$UserActivationInfo = '';
			}
			if (sizeof ($AdminInfoArray) > 0) {
				$UserInfo = '<div style="color:#f40e0e;">' . implode ('&nbsp;', $AdminInfoArray) . '</div>';
			}
			else {
				$UserInfo = '';
			}
		}
		else {
			$UserInfo = '';
			$UserBanInfo = '';
		}

		//2.Пол
		$UserSexImg = '';

		//логин и пол
		$LoginSex = '<div class="ThemeNameDiv">' . $LoginHref . $UserSexImg . $UserInfo . '</div>';

		//3.группа
		$GroupColor = $UserGroups_Array[$UGroupID]['Color'];
		$GroupDescription = $UserGroups_Array[$UGroupID]['Description'];
		$UserGroup = '<div style="margin:5px; width:115px; overflow:hidden; font-size:13px; font-family:Arial,Verdana,sans-serif;">
						<span style="color:#' . $GroupColor . '">' . $GroupDescription . '</span>
					  </div>';

		//4.Дата регистрации
		$DateFormat = 'd.m.Y, H:i';
        $RegistrationDate = Main_ConvertDate ($URegDate, $ForumLang['DateArray'], $DateFormat);
		$RegDate = '<div class="UpdateTime">
						<acronym title="' . $RegistrationDate . '" class="UpdateAcr">' . $RegistrationDate . '</acronym>
					</div>';

		//5.Отправить письмо
		$MailFlag = false;
		if ($_SESSION['UserData']['UserType'] <> 'admin') {
			if (!OBB_MAIL_ALLOWED || !$UserGroups_Permissions['MailSendAllowed'] || $UOtherMail == 'no' || $_SESSION['UserData']['UserType'] == 'guest') {
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
			$UserMailHref = Defence_HTMLSpecials ($SelfName . '?action=tooling&label=mail&id_user=' . $UID);
			$UMailString = '<a title="' . $ForumLang['UserlistSendMail'] . '" href="' . $UserMailHref . '"><img title="' . $ForumLang['UserlistSendMail'] . '" alt="" src="' . OBB_IMAGE_DIR . '/send_mail.png" /></a>';
		}
		else {
			$UMailString = '&nbsp;';
		}

		//6.Активность на форуме
		if ($UIsOnline == 'yes') {
			$Image = 'online';
			$OnlineStatus = $ForumLang['UserlistOnline'];
			$ActionArray  = $ForumLang['UserActions'];
			$ActionString = '<span class="OnlineActivityOn">' . $ActionArray[$ULastAction] . '</span>';
		}
		else {
			$Image = 'offline';
			$OnlineStatus = $ForumLang['UserlistOffline'];
			$DatePattern = 'd.m.Y';
			$ConvertDate = Main_ConvertDate ($ULastLogin, $ForumLang['DateArray'], $DatePattern);
			$ConvertDate = '<strong>' . $ConvertDate . '</strong>';
			$TimePattern = 'H:i';
			$ConvertTime = Main_ConvertDate ($ULastLogin, $ForumLang['DateArray'], $TimePattern);
			$ConvertTime = '<strong>' . $ConvertTime . '</strong>';
			$DateTimeLogin = $ConvertDate . ' ' . $ForumLang['UserlistIn'] . ' ' . $ConvertTime;
			$ActionString = '<span class="OnlineActivityOff">' . $ForumLang['UserlistLastAction'] . ' - <span class="OnlineActivityTime">' . $DateTimeLogin . '</span></span>';
		}
		$UserActivity  = '<img class="OnlineImg" title="' . $OnlineStatus . '" alt="" src="' . OBB_IMAGE_DIR . '/' . $Image . '.png" />';
		$UserActivity .= '<span class="OnlineActivity">' . $ActionString . '</span>';

		 //Админские элементы управления
		if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS) {
			$AdminUserListPanel ='<td style="vertical-align:middle;" class="AdminShowFirst">
									<a title="' . $ForumLang['UserlistEditUser']   . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=admin&adm=user&uact=edit&uid=' . $UID) . '"><img title="' . $ForumLang['UserlistEditUser']   . '" alt="" src="' . OBB_IMAGE_DIR . '/admin_edit.png" /></a>
								</td>
								<td class="AdminShow">
									<a title="' . $ForumLang['UserlistDeleteUser'] . '" href="' . Defence_HTMLSpecials ($SelfName . '?action=admin&adm=user&uact=del&uid='  . $UID) . '"><img title="' . $ForumLang['UserlistDeleteUser'] . '" alt="" src="' . OBB_IMAGE_DIR . '/admin_delete.png" /></a>
								</td>';
		}
		else {
			$AdminUserListPanel = '';
		}

		//обобщение
		$MainOutput .= '<tr>
							<td style="background:#CFDAE7 !important; padding:5px 5px; vertical-align:middle;" class="ForumNameTD ThemeNameTD">
								' . $LoginSex     . '
							</td>
							<td style="background:#D9E0EA !important; padding:5px 2px; border-right:0; text-align:center; vertical-align:middle;" class="ForumUpdateTD">
								' . $RegDate      . '
							</td>
							<td style="background:#D9E0EA !important; padding:5px 5px; vertical-align:middle;" class="ForumNumThemesTD">
								' . $UNumPosts    . '
							</td>
							<td style="background:#D9E0EA !important; padding:5px 5px; text-align:left; vertical-align:middle;" class="ForumNumThemesTD">
								' . $UserGroup    . '
							</td>
							<td style="background:#D9E0EA !important; padding:5px 5px; text-align:center; vertical-align:middle;" class="ForumNumThemesTD">
								' . $UMailString  . '
							</td>
							<td style="background:#CFDAE7 !important; border-right:1px solid #FFF; padding:5px 5px;" class="ForumNameTD ThemeNameTD">
								' . $UserActivity . '
							</td>
							' . $AdminUserListPanel . '
						</tr>';
	}
}
else {
	$MainOutput .= '<tr>
						<td colspan="' . $Colspan . '" style="text-align:center !important; padding:5px 5px; vertical-align:middle;" class="ForumNameTD ThemeNameTD">
							<span>' . $ForumLang['UserlistNoUsers'] . '</span>
						</td>
					</tr>';
}

	$MainOutput .= '	<tr>
							<td class="ForumsTablePreBottom" colspan="' . $Colspan . '">
								<div style="height:10px;"><!-- --></div>
							</td>
						</tr>
					</table>';

//разделительный div
$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

//постраничная навигация
$MainOutput .= $PageList;

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

//echo microtime () - $a;

?>