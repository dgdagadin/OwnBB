<?php

$a = microtime ();
//проверка системной переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//Если это пользователь, то перенаправляем его на главную страницу
if ($_SESSION['UserData']['UserType'] <> 'guest') {
	OBB_Main_Redirect ();
}

//подключение
include (OBB_KERNEL_DIR . '/Service.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Login_' . $Config_Lang . '.php');

//сейчас
$Now = time ();

//Функция обновления онлайн-информации о ГОСТЕ (и только! пользователей тут быть не должно!)
if (!isset ($NoAccess)) {
	OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, '');
}

//навигация
$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array('', $ForumLang['Authorization'])
				);

//нотисы)
//$AuthLogin  = '';
$MainOutput = '';

//массив ошибок
$AuthError = array ();

//переменная редиректа
//  --старая версия
$RedirectURL = 'http://' . $HostName . $SelfName;
$RedirectURLPattern = '/http:\/\/' . preg_quote ($HostName, "/") . preg_quote ($SelfName, "/") . '(?:\?action=[_a-z]{3-10}(?:&[_a-z]{2,20}=(.*?))*)?/ui';
if (!isset ($_POST['AuthRedirectURL'])) {
	if (isset ($_SERVER['HTTP_REFERER'])) {
		if (preg_match ($RedirectURLPattern, $_SERVER['HTTP_REFERER'], $matches)) {
			$RedirectURL = $_SERVER['HTTP_REFERER'];
			preg_match_all ('/\?action=(.*?)&/ui', $RedirectURL, $matches);
			if (!isset ($matches[1][0]) || Main_Strtolower ($matches[1][0]) == 'message' || Main_Strlen ($matches[1][0]) < 1) {
				$RedirectURL = 'http://' . $HostName . $SelfName;
			}
		}
	}
}
else {
	if (preg_match ($RedirectURLPattern, $_POST['AuthRedirectURL'])) {
		$RedirectURL = $_POST['AuthRedirectURL'];
	}
}

//  --получение переменной редиректа
//  --новая версия - ОТЛОЖИТЬ НА ПОТОМ
/* if (!isset ($_POST['AuthRedirectURL']) || Main_Strlen ($_POST['AuthRedirectURL']) < 1) {
	if (isset ($NoAccess)) {
		if (isset ($_SERVER['REQUEST_URI'])) {
			$_SERVICE_REDIRECT = 'http://' . $HostName . $_SERVER['REQUEST_URI'];
		}
		else {
			$_SERVICE_REDIRECT = $RedirectURL;
		}
	}
	else {
		if (isset ($_SERVER['HTTP_REFERER'])) {
			$_SERVICE_REDIRECT = $_SERVER['HTTP_REFERER'];
		}
		else {
			$_SERVICE_REDIRECT = $RedirectURL;
		}
	}
}
else {
	$_SERVICE_REDIRECT = trim ($_POST['AuthRedirectURL']);
}

//  --проверка переменной редиректа
if (preg_match ($RedirectURLPattern, $_SERVICE_REDIRECT, $matches)) {
	preg_match_all ('/\?action=(.*?)&/ui', $_SERVICE_REDIRECT, $matches);
	if (!isset ($matches[1][0]) || Main_Strtolower ($matches[1][0]) == 'message' || Main_Strlen ($matches[1][0]) < 1) {
		$_SERVICE_REDIRECT = 'http://' . $HostName . $SelfName;
	}
} */
//переменная редиректа - КОНЕЦ
//  --новая версия - ОТЛОЖИТЬ НА ПОТОМ

//обработка
if (isset ($_POST['login'])) {
	if (!isset ($_POST['AuthLogin']) || $_POST['AuthLogin'] == '' || !isset ($_POST['AuthPass']) || $_POST['AuthPass'] == '') {
		$AuthError[] = $ForumLang['AuthErrors']['WrongLoginPass'];
	}
	else {
		//логин и пароль
		$AuthLogin = trim ($_POST['AuthLogin']);
		$AuthPass  = trim ($_POST['AuthPass']);

		//галка "запомнить меня"
		$AuthRememberMe = isset ($_POST['RememberMe']) ? TRUE : false;

		$CheckUserValid = 0;

		//проверка логина-пароля
		$SQLCheckAuth = 'SELECT
							UserID,
							UserPassword,
							UserMail,
							GroupID,
							UserIsActivate,
							UserAutoLogin
						FROM
							users
						WHERE
							UserLogin = \'' . Defence_EscapeString ($Config_DBType, $AuthLogin) . '\'';
		$CheckAuthQuery = DB_Query ($Config_DBType, $SQLCheckAuth, $ForumConnection);
		if (!$CheckAuthQuery) {
			OBB_Main_Log ($SQLCheckAuth . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		$UserExists = DB_NumRows ($Config_DBType, $CheckAuthQuery);
		if ($UserExists == 0) {
			$CheckUserValid++;
		}

		//массив
		$UserRow = DB_FetchAssoc ($Config_DBType, $CheckAuthQuery);

		//данные
		$UserPass  = $UserRow['UserPassword'];
		$UserID    = intval ($UserRow['UserID']);
		$UserMail  = $UserRow['UserMail'];
		$GroupID   = intval ($UserRow['GroupID']);
		$AutoLogin = $UserRow['UserAutoLogin'];
		$ActiveStatus = $UserRow['UserIsActivate'];

		if (Main_Crypt ($AuthPass) <> $UserPass) {
			$CheckUserValid++;
		}

		if ($CheckUserValid > 0) {
			$AuthError[] = $ForumLang['AuthErrors']['WrongLoginPass'];
		}
		else {
			if ($ActiveStatus <> 'yes') {
				$AuthError[] = $ForumLang['AuthErrors']['NotActivate'];
			}
		}

		//если ошибок нету
		if (sizeof ($AuthError) == 0) {
			//удаление из гостей
			$SQLDelFromGuests = 'DELETE FROM guest_activity WHERE GuestIPAddress=\'' . $UserIP . '\'';
			$DelFromGuestsQuery = DB_Query ($Config_DBType, $SQLDelFromGuests, $ForumConnection);
			if (!$DelFromGuestsQuery) {
				OBB_Main_Log ($SQLDelFromGuests . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//редактирование таблицы пользователя
			$CurAction = 'main_page';
			$SQLUpdateUser = 'UPDATE user_activity
							SET UserLastLogin=\'' . $Now . '\', 
							UserLastAction=\'' . $CurAction . '\', 
							UserIPAddress = \'' . $UserIP . '\',
							UserIsOnline=\'yes\'
							WHERE UserID=\'' . $UserID . '\'';
			$UpdateUserQuery = DB_Query ($Config_DBType, $SQLUpdateUser, $ForumConnection);
			if (!$UpdateUserQuery) {
				OBB_Main_Log ($SQLUpdateUser . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//выборка статуса пользователя
			$SQL = 'SELECT AclStatus FROM user_group_permissions WHERE GroupID = \'' . $GroupID . '\'';
			$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
			$UserStatus = DB_Result ($Config_DBType, $Query, 0, 'AclStatus');
			$UserStatus = Main_Strtolower ($UserStatus);

			//регенерация ид сессии
			session_regenerate_id (TRUE);

			$SecureString = OBB_Main_SetAuthorizationString ();

			//случайная строка - хеш
			$RandomString = Main_GenerateRandString (10, '1');
			$RandomString = sha1 ($RandomString . $Config_AuthSalt);

			//данные в сессию
			unset ($_SESSION['UserData']);
			$_SESSION['UserData'] = array ();
			$_SESSION['UserData']['UserType']  = $UserStatus;
			$_SESSION['UserData']['UserID']    = $UserID;
			$_SESSION['UserData']['UserName']  = $AuthLogin;
			$_SESSION['UserData']['UserIP']    = OBB_Main_GetUserIPAddress ();
			$_SESSION['UserData']['GroupID']   = $GroupID;
			$_SESSION['UserData']['CheckTime'] = time ();
			$_SESSION['UserData'][$Config_SecureKey] = $SecureString;
			$_SESSION['UserData'][$Config_SecureCookieName] = $RandomString;

			//куки для дополнительного уровня безопасности
			setcookie ($Config_SecureCookieName, $RandomString);

			//если выбрана галка "Запомнить меня" - формируется автологин
			if ($AuthRememberMe) {
				// 1)удаление из таблицы автологина записи с таким UserID и $SecureString - В СЛУЧАЕ ЗАКОНЧИВШЕГОСЯ АВТОЛОГИНА!!!
				$SQL = 'DELETE FROM user_autologins WHERE UserID = \'' . $UserID . '\' AND AutoLoginString = \'' . Defence_EscapeString ($Config_DBType, $SecureString) . '\'';
				$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				// 2)Добавление в таблицу автологина записи с таким UserID и AutologinString
				$SQL = 'INSERT INTO user_autologins (UserID, AutoLoginString, AutoLoginDate, AutoLoginIP) VALUES (\'' . $UserID . '\', \'' . $SecureString . '\', \'' . time() . '\', \'' . OBB_Main_GetUserIPAddress () . '\')';
				$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				// 3)Создание куки автоматического входа в систему
				$AutologinCookieTime = time() + 3600 * 24* 31 * OBB_AUTOLOGIN_NUM_MONTHS;
				setcookie ($Config_AutologinCookieName, $AutoLogin, $AutologinCookieTime);
			}

			//перенаправление
			$_SESSION['Message']  = 'auth_sucess';
			$_SESSION['Redirect'] = str_replace ('http://' . $HostName . $SelfName, '', $RedirectURL);
			$URL = '?action=message';
			OBB_Main_Redirect ($URL);
		}
	}
}

//переменная действия
if (isset($NoAccess)) {
	$Action = 'login';
}

//Javascript-массив
$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'');

//JS-массив
$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Login.js', '')
					);

//заголовок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Login'], array('forumname'=>$Config_ForumName));

//если нет доступа - добавляем ошибку отсутствия доступа
if (isset ($NoAccess)) {
	$AuthError[] = $ForumLang['AuthErrors']['NoAcess'];
}

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

//ошибки авторизации
if (isset ($AuthError) && sizeof ($AuthError) > 0) {
	$ErrorTitle = isset ($NoAccess) ? $ForumLang['AuthErrors']['ErrorNoAccessTitle'] : $ForumLang['AuthErrors']['ErrorBlockTitle'];
	$ErrorListBlock = '<div class="ErrorBlockDiv">
							<div class="ErrorBlockTitle">' . $ErrorTitle . ':</div>';
	foreach ($AuthError as $Key=>$Value) {
		$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
	}
	$ErrorListBlock .= ' </div>
						<div style="height:15px;"><!-- --></div>';
}
else {
	$ErrorListBlock = '';
}
$MainOutput .= $ErrorListBlock;

//Логин
$InpLogin = isset ($_POST['AuthLogin']) ? Defence_HTMLSpecials ($_POST['AuthLogin']) : '';

//эл-ты формы
$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=login');
$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
					<tr class="MainColumnRow">
						<td colspan="1" class="MainColumnName"><span>' . $ForumLang['AuthTitle'] . '</span></td>
					</tr>
					<tr class="ForumMainTR">
						<td style="padding:0;">
							<form style="padding:0; margin:0;" id="LoginForm" action="' . $ActionURL . '" method="post">
								<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="border-top:1px solid #FFFFFF; vertical-align:middle; width:160px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['AuthLoginTitle'] . '
											</div>
										</td>
										<td style="border-top:1px solid #FFFFFF;" class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="LoginID" type="text" name="AuthLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="' . $InpLogin . '" />
										</td>
									</tr>
									<tr>
										<td style="vertical-align:middle; width:160px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['AuthPassTitle'] . '
											</div>
										</td>
										<td class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="PasswordID" type="password" name="AuthPass" value="" />
										</td>
									</tr>
									<tr>
										<td style="vertical-align:middle; width:160px;" class="FormTitleTD">
											&nbsp;
										</td>
										<td style="vertical-align:top;" class="FormInputTD">
											<div class="AuthOther">
												<a href="' . $SelfName . '?action=registration">' . $ForumLang['AuthRegistration'] . '</a>&nbsp;|&nbsp;<a href="' . $SelfName . '?action=forgot">' . $ForumLang['AuthForgotten'] . '</a>
											</div>
											<div class="AuthRemember">
												<input id="Label_RememberMe" type="checkbox" name="RememberMe" />&nbsp;&nbsp;<label for="Label_RememberMe">' . $ForumLang['AuthRemember'] . '</label>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2" class="FormInputTD CenterTD">
											<div id="SubmitButtonDiv">
												<input class="InpButton" id="LoginSubmit" name="SendAuthForm" type="submit" value="' . $ForumLang['AuthTitle'] . '" />
												<input class="InpButton" type="reset" value="' . $ForumLang['AuthReset'] . '" />
											</div>
											<input type="hidden" name="AuthRedirectURL" value="' . Defence_HTMLSpecials ($RedirectURL) . '" />
											<input type="hidden" name="login" value="1" />
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

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;
//echo microtime() - $a;
?>