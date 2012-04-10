<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//Если это не гость, то перенаправляем его на главную страницу
if ($_SESSION['UserData']['UserType'] <> 'guest') {
	OBB_Main_Redirect ($URL);
}

//проверка, разрешено ли гостю регистрироваться
if (!OBB_ALLOW_REGISTRATION) {
    OBB_Main_ShowError ('reg_no_access', 'STANDART', $ForumLang['Errors']);
}

//подключение
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Registration_' . $Config_Lang . '.php');

//сейчас
$Now = time ();

//Функция обновления онлайн-информации о ГОСТЕ (и только! пользователей тут быть не должно!)
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, '');

$MainOutput = '';
$RegistrationFirstStep = $ForumLang['Registration'] . ' - ' . $ForumLang['RegStepOne'];
$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array('', $RegistrationFirstStep)
					);

//Определение стадии регистрации
$RegStagesArray = array ('2','3');
(isset ($_GET['r_stage']) && in_array ($_GET['r_stage'], $RegStagesArray)) ? $RegAction = $_GET['r_stage'] : $RegAction = '1';

switch ($RegAction) {
	//если не определена $_GET['agree'], вызываем правила 
	case "1": 
	default: {
		//JS-массив
		$JavaScriptArray = array (
								array ('jquery.js', ''),
								array ('functions.js', ''),
								array ('gui.js', '')
							);

		//заголовок страницы
		$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Registration'], array('forumname'=>$Config_ForumName));

		//верх
		$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

		//Правила регистрации
		$RulesHTMLFile = OBB_HTML_LANGUAGE_DIR . '/Rules.html';
		if (!file_exists ($RulesHTMLFile)) {
			OBB_Main_Log ('No rulefile', OBB_ERROR_LOG_FILE);
		}
		$Rules = Main_GetCachedData ($RulesHTMLFile);
		/* $MainOutput .= '<div>
							<fieldset style="margin-left:30px; width:80%;">
								<legend>' . $ForumLang['RegRules'] . '</legend>
								' . $Rules . '
							</fieldset>
						</div>';

		//Форма продолжения регистрации
		$MainOutput .= '<form action="' . $SelfName . '?action=registration" method="GET">
							<input type="hidden" name="action" value="registration">
							<input type="hidden" name="r_stage" value="2">
							<input type="checkbox" name="rules_agreement"> ' . $ForumLang['AgreeWithRules'] . '<br>
							<input type="submit" value="' . $ForumLang['RegContinue'] . '">
						</form>'; */
		$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName">
									<span>' . $ForumLang['RegRules'] . '</span>
								</td>
							</tr>
							<tr>
								<td style="border-top:none !important; padding:15px 15px 25px 15px;" class="ForumUpdateTD">
									' . $Rules . '
								</td>
							</tr>
							<tr>
								<td style="padding:0;">
									<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
										<tr>
											<td colspan="2" class="FormInputTD CenterTD">
												<form action="' . $SelfName . '?action=registration" method="get">
													<input type="hidden" name="action" value="registration" />
													<input type="hidden" name="r_stage" value="2" />
													<input style="padding:0; margin:0;" type="checkbox" name="rules_agreement" />
													<span style="font-size:13px; color:#000; margin:0 15px 0 1px;">' . $ForumLang['AgreeWithRules'] . '</span>
													<input class="InpButton" id="RegSubmit" type="submit" value="' . $ForumLang['RegContinue'] . '" />
												</form>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="ForumsTableBottom" colspan="1">
									<div style="height:5px;"><!-- --></div>
								</td>
							</tr>
						</table>';

		//Футер форума
		$MainOutput .= Echo_PrintFoot ();
		break;
	}

	case "2": {
		//определение типа регистрации
		if (OBB_REGISTRATION_CONFIRM) {
			if (OBB_REGISTRATION_AUTOPASS) {
				$RegistrationType = '1';
			}
			else {
				$RegistrationType = '2';
			}
		}
		else {
			$RegistrationType = '3';
		}

		//если не поставлена галка согласия
		if (!isset ($_GET['rules_agreement']) || $_GET['rules_agreement'] <> 'on') {
			OBB_Main_Redirect ('?action=registration');
		}

		//проверка откуда пользователь пришел
		$AllowedURL1 = 'http://' . $HostName . $SelfName . '?action=registration';
		if (Defence_ComingControl ($AllowedURL1, '0') == FALSE || !isset ($_GET['rules_agreement']) || $_GET['rules_agreement'] <> 'on') {
			OBB_Main_Redirect ('?action=registration');
		}

		//орпеделение переменных
		$RegLogin      = isset ($_POST['RegLogin'])      ? trim ($_POST['RegLogin'])      : '';
		$RegMail       = isset ($_POST['RegMail'])       ? trim ($_POST['RegMail'])       : '';
		$RegRepeatMail = isset ($_POST['RegRepeatMail']) ? trim ($_POST['RegRepeatMail']) : '';
		$RegPass       = isset ($_POST['RegPass'])       ? trim ($_POST['RegPass'])       : '';
		$RegRepeatPass = isset ($_POST['RegRepeatPass']) ? trim ($_POST['RegRepeatPass']) : '';
		$RegSex        = isset ($_POST['RegSex'])        ? trim ($_POST['RegSex'])        : '';
		$RegBirthDate  = isset ($_POST['RegBirthDate'])  ? trim ($_POST['RegBirthDate'])  : '';

		//если определен пост-массив
		if (isset ($_POST['SendRegForm'])) {
			//Массивы
			$ErrorArray = array ();

			//флаг аватара
			if (OBB_ALLOW_AVATARS == TRUE && isset ($_FILES['RegAvatar']['name']) && $_FILES['RegAvatar']['name'] <> '') {
				$AvatarFlag = '1';
			}
			else {
				$AvatarFlag = '0';
			}

			//проверка
			//  --1)капча
			if (OBB_CAPTCHA && OBB_REGISTRATION_CAPTCHA) {
				$CaptchaCheck = Defence_CheckCaptcha ();
				if (is_string ($CaptchaCheck)) {
					$ErrorArray[] = $ForumLang['RegErrors'][$CaptchaCheck];
				}
			}

			//  --2)антифлуд

			//  --3)проверка логина
			$CheckRegLogin = OBB_Defence_CheckLogin ($RegLogin);
			$RegLoginCorrect = $CheckRegLogin[0];
			if (!$RegLoginCorrect) {
				$RegLoginError = $CheckRegLogin[1];
                $WrongRegistrationLogin = OBB_Main_ReplaceSymbols ($ForumLang['RegErrors']['Reg'.$RegLoginError], array('min'=>OBB_MIN_LOGIN_LENGTH, 'max'=>OBB_MAX_LOGIN_LENGTH));
				$ErrorArray[] = $WrongRegistrationLogin;
			}

			//  --4)проверка почты
			$CheckRegMail = OBB_Defence_CheckMail ($RegMail);
			$RegMailCorrect = $CheckRegMail[0];
			if (!$RegMailCorrect) {
				$RegMailError = $CheckRegMail[1];
				$WrongRegistrationMail = OBB_Main_ReplaceSymbols ($ForumLang['RegErrors']['Reg'.$RegMailError], array('min'=>OBB_MIN_MAIL_LENGTH, 'max'=>OBB_MAX_MAIL_LENGTH));
				$ErrorArray[] = $WrongRegistrationMail;
			}

			//  --5)повтор почты
			if ($RegRepeatMail == '') {
				$ErrorArray[] = $ForumLang['RegErrors']['RegEmptyRepeatMail'];
			}

			//  --6)Проверка равенства почты и повтора почты
			if ($RegRepeatMail <> $RegMail) {
				$ErrorArray[] = $ForumLang['RegErrors']['RegMailsNoEq'];
			}

			//  --7)пароль и повтор пароля (при опр. типах регистрации)
			if ($RegistrationType <> '1') {
				if (Main_Strlen ($RegPass) == 0) {
					$ErrorArray[] = $ForumLang['RegErrors']['PasswordIsEmpty'];
				}
				else if (Main_Strlen ($RegPass) < OBB_MIN_PASSWORD_LENGTH || Main_Strlen ($RegPass) > OBB_MAX_PASSWORD_LENGTH) {
					$WrongPasswordLength = OBB_Main_ReplaceSymbols ($ForumLang['RegErrors']['WrongPasswordLength'], array('min'=>OBB_MIN_PASSWORD_LENGTH, 'max'=>OBB_MAX_PASSWORD_LENGTH));
					$ErrorArray[] = $WrongPasswordLength;
				}
				else {
					if ($RegPass <> $RegRepeatPass) {
						$ErrorArray[] = $ForumLang['RegErrors']['WrongPasswordEqual'];
					}
				}
			}

			//  --8)проверка даты
			if ($RegBirthDate == '') {
				$ErrorArray[] = $ForumLang['RegErrors']['RegEmptyDate'];
			}
			else {
				$DatePattern = '/^[0-3][\d][-\.\/][01][\d][-\.\/][12][\d][\d][\d]$/';
				if (!preg_match ($DatePattern, $RegBirthDate)) {
					$ErrorArray[] = $ForumLang['RegErrors']['RegWrongDate'];
				}
				/* else {
					проверка актуальности даты
				} */
			}

			//  --9)Проверка аватара (если он есть)
			if ($AvatarFlag == '1') {
				$FileDataArray = $_FILES['RegAvatar'];
				$AvatarObject = new OwnBB_CheckUpload ($FilesArray, OBB_MAX_AVATAR_SIZE, OBB_MAX_AVATAR_WIDTH, OBB_MAX_AVATAR_HEIGHT);
				$AttachArray = $AvatarObject->FullCheckOfUpload ($FileDataArray);

				$IsTrue = $AttachArray['IsTrue'];
				if ($IsTrue == FALSE) {
					$LangAttach      = $ForumLang['Attach'];
					$FileErrorCode   = $AttachArray['Error'];
					$ErrorArray[] = $LangAttach[$FileErrorCode];
				}
				else {
					$IsImage = $AttachArray['Image'];
					if ($IsImage <> '1') {
						$ErrorArray[] = $ForumLang['RegErrors']['RegWrongAvatar'];
					}
					else {
						$UserAvatar = Main_Strtolower ($AttachArray['Ext']);
					}
				}
			}
			else {
				$UserAvatar = '';
			}

			//если ошибок не обнаружено
			if (sizeof ($ErrorArray) == 0) {
				//определение пола
				$SexArray = array ('male','female');
				$RegSex = $RegSex == '' || !in_array ($RegSex, $SexArray) ? 'male' : $RegSex;

				//длполнительный опции
				$RegHideMail  = isset ($_POST['RegHideMail'])  && $_POST['RegHideMail']  == TRUE  ? 'yes' : 'no';
				$GetAdminMail = isset ($_POST['GetAdminMail']) && $_POST['GetAdminMail'] == TRUE  ? 'yes' : 'no';
				$GetUserMail  = isset ($_POST['GetUserMail'])  && $_POST['GetUserMail']  == TRUE  ? 'yes' : 'no';

				$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
				$Transaction->StartTransaction ();

				//пароль - генерация или введенный
				if ($RegistrationType == '1') {
					$UserPassword  = Main_GenerateRandString ('10', '1');
					$CryptPassword = Main_Crypt ($UserPassword);
				}
				else {
					$CryptPassword = Main_Crypt ($RegPass);
				}

				//создание уникальной записи для автологина - НЕУДАЧНО - ПЕРЕДЕЛАТЬ!!!
				$StringExists = false;
				while (!$StringExists) {
					$AutoLoginString = Main_GenerateRandString (25, '0');
					$SQL = 'SELECT 1 FROM users WHERE UserAutoLogin = \'' . Defence_EscapeString ($Config_DBType, $AutoLoginString) . '\'';
					$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
					if (!$Query) {
						OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
					}
					if (DB_NumRows ($Config_DBType, $Query) == 0) {
						$TrueAutoLogin = $AutoLoginString;
						$StringExists = TRUE;
					}
				}
				//создание уникальной записи для автологина - КОНЕЦ

				//вычисление ид группы пользователей
				$DefaultGroupID = NULL;
				foreach ($UserGroups_Array as $key=>$value) {
					if ($value['Default']) {
						$DefaultGroupID = $key;
						break;
					}
				}

				//активировать ли пользователя сейчас, или только после подтверждения
				if ($RegistrationType == '3') {
					$UserIsActivateValue = 'yes';
				}
				else {
					$UserIsActivateValue = 'no';
				}

				//запрос - добавление в основную таблицу
				$SQLInsUser = 'INSERT INTO users
							(UserLogin, 
							UserPassword, 
							UserMail, 
							UserMailHid, 
							UserAdminMail, 
							UserOtherMail, 
							UserRegDate, 
							UserSex, 
							UserBirthDate, 
							UserAvatar, 
							UserIsActivate, 
							GroupID, 
							UserAutoLogin)
							VALUES
							(\'' . Defence_EscapeString ($Config_DBType, $RegLogin) . '\',
							\'' . $CryptPassword . '\',
							\'' . Defence_EscapeString ($Config_DBType, $RegMail) . '\',
							\'' . $RegHideMail . '\',
							\'' . $GetAdminMail . '\',
							\'' . $GetUserMail . '\',
							\'' . $Now . '\',
							\'' . $RegSex . '\',
							\'' . Defence_EscapeString ($Config_DBType, $RegBirthDate) . '\',
							\'' . Defence_EscapeString ($Config_DBType, $UserAvatar) . '\',
							\'' . $UserIsActivateValue . '\',
							\'' . intval ($DefaultGroupID) . '\',
							\'' . Defence_EscapeString ($Config_DBType, $TrueAutoLogin) . '\')';
				$InsUserQuery = DB_Query ($Config_DBType, $SQLInsUser, $ForumConnection);
				if (!$InsUserQuery) {
					OBB_Main_Log ($SQLInsUser . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$LastUserID = DB_LastID ($Config_DBType, $ForumConnection);

			    $InsUserArray =   array (
										'Operation'  => 'd',
										'FieldArray' => array (),
										'WhereArray' => array('UserID'=>array ('Value'=>$LastUserID, 'Type'=>'int')), 
										'TableName'  => 'users'
										);
				$Transaction->PrepareQueryToRollback ($InsUserArray);

				//Запрос - добавление в таблицу посещений
				$Now = time ();
				$CurAction = 'main_page';
				$OnLine = 'no';
				$SQLInsUserActivity = 'INSERT INTO user_activity
									(UserID, 
									UserLastLogin, 
									UserLastAction, 
									UserIsOnline, 
									UserIPAddress)
									VALUES
									(\'' . $LastUserID . '\',
									\'' . $Now . '\',
									\'' . $CurAction . '\',
									\'' . $OnLine . '\',
									\'' . $UserIP . '\')';
				$InsUserActivityQuery = DB_Query ($Config_DBType, $SQLInsUserActivity, $ForumConnection);
				if (!$InsUserActivityQuery) {
					$Transaction->RollbackTransaction ();
					OBB_Main_Log ($SQLInsUserActivity . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}

				$InsUserActivityArray = array (
											'Operation'  => 'd',
											'FieldArray' => array (),
											'WhereArray' => array('UserID'=>array('Value'=>$LastUserID,'Type'=>'int')),
											'TableName'  => 'user_activity'
											);
				$Transaction->PrepareQueryToRollback ($InsUserActivityArray);

				//запрос - увеличение статистики пользователей
				if (!OBB_REGISTRATION_CONFIRM) {
					$SQLUserStatistics = 'UPDATE statistics SET StatisticsValue=(SELECT COUNT(*) FROM users WHERE UserIsActivate = \'yes\') WHERE StatisticsKey = \'1\'';
					$UserStatQuery = DB_Query ($Config_DBType, $SQLUserStatistics, $ForumConnection);
					if (!$UserStatQuery) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ($SQLUserStatistics . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
					}

					$SQLUserStatArray = array (
												'Operation'  => 'u',
												'FieldArray' => array ('StatisticsValue'=>array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'int')),
												'WhereArray' => array('StatisticsKey'=>array ('Value'=>'1', 'Type'=>'int')), 
												'TableName'  => 'statistics'
											);
					$Transaction->PrepareQueryToRollback ($SQLUserStatArray);
				}
				
				//добавление аватара
				if ($AvatarFlag == '1') {
					$Extension  = Main_Strtolower ($UserAvatar);
					$AvatarPath = OBB_AVATAR_DIR . '/' . $LastUserID . '.' . $Extension;
					$TMPName    = $AttachArray['Data'];
					if (!move_uploaded_file ($TMPName, $AvatarPath)) {
						$Transaction->RollbackTransaction ();
						OBB_Main_Log ('Error while moving uploaded avatar', OBB_ERROR_LOG_FILE);
					}
				}

				//создание регистрационного файла - при опр. типах регистрации
				if ($RegistrationType <> '3') {
					$FileName = sha1 ($LastUserID);
					$ActivateString = Main_GenerateRandString ('30', '0');
					$CreateHandler = fopen(OBB_ACTIVATION_DIR . '/' . $FileName, "w");
					if (!$CreateHandler) {
						OBB_Main_Log ('Error while creating activate file', OBB_ERROR_LOG_FILE);
					}
					fwrite($CreateHandler, $ActivateString);
					fclose ($CreateHandler);
				}

				//ОТПРАВКА ПИСЕМ
				$LetterTempPath = OBB_HTML_LANGUAGE_DIR . '/';
				//  --отправка письма пользователю для подтверждения регистрации по почте
				if ($RegistrationType <> '3') {
					$RegMailer      = new OwnBB_Mailer ($Config_Mail);

					//  -письмо пользователю
					$UserHref = 'http://' . $HostName. $SelfName . '?action=registration&r_stage=3&user_id=' . $LastUserID . '&activate_key=' . $ActivateString;
					$SiteAddress = 'http://' . $_SERVER['HTTP_HOST'];
					$ForumName = $Config_ForumName;
					$MailTheme = $ForumLang['RegMailThemeUser'] . ' "' . $ForumName . '"';

					$UserLetter = file_get_contents ($LetterTempPath . 'UserMailReg.html');

					$UserLetter = str_replace ('{siteaddress}', $SiteAddress, $UserLetter);
					$UserLetter = str_replace ('{forumname}', $Config_ForumName, $UserLetter);
					$UserLetter = str_replace ('{username}', $RegLogin, $UserLetter);
					$UserLetter = str_replace ('{password}', $UserPassword, $UserLetter);
					$UserLetter = str_replace ('{userhref}', $UserHref, $UserLetter);

					#$RegMailer->SendMail ($RegLogin, $RegMail, $MailTheme, $UserLetter);
					file_put_contents (OBB_ERROR_MAIL_DIR . '/UserRegLog' . $LastUserID . '.html', $UserLetter);
				}

				//  -письмо администратору
				if ($Config_Mail['AdminMail'] == TRUE) {
					$AdmUserLogin = $RegLogin;
					$AdmUserMail  = $RegMail;
					$AdmUserID    = $LastUserID;
					$AdmUserDate  = Main_ConvertDate (time (), '', $Format = 'd.m.y, H:i');
					$AdminName    = $Config_Mail['FromName'];
					$AdminMail    = $Config_Mail['FromMail'];

					$AdminLetter = file_get_contents ($LetterTempPath . 'AdminMailReg.html');

					$AdminLetter = str_replace ('{username}', $AdmUserLogin, $AdminLetter);
					$AdminLetter = str_replace ('{usermail}', $AdmUserMail, $AdminLetter);
					$AdminLetter = str_replace ('{userid}', $AdmUserID, $AdminLetter);
					$AdminLetter = str_replace ('{userdate}', $AdmUserDate, $AdminLetter);

					#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
					file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_UserRegLog' . $LastUserID . '.html', $AdminLetter);
				}

				//перенаправление
                $Message = $RegistrationType <> '3' ? 'reg_success_confirm' : 'reg_success';
				$_SESSION['Message'] = $Message;
				OBB_Main_Redirect ('?action=message');
			}
		}
		
		//календарь
		$CalendarScript = '
		<script type="text/javascript">//<![CDATA[
		  Calendar.setup({
			inputField : "RegistrationBirthDate",
			trigger    : "CalendarIcon",
			onSelect   : function() { this.hide() },
			showTime   : 12,
			dateFormat : "%d.%m.%Y"
		  });
		//]]></script>';

		//вывод полей пароля
		if ($RegistrationType <> '1') {
			$PasswordFields = '<div>
								' . $ForumLang['RegPasswordTitle'] . ' (' . OBB_MIN_PASSWORD_LENGTH . '-' . OBB_MAX_PASSWORD_LENGTH . ' ' . $ForumLang['RegSymbolWord'] . ') *
								<br />
								<input id="RegistrationPass" type="password" name="RegPass" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="">
							</div>
							<br />
							<div>
								' . $ForumLang['RegRepeatPasswordTitle'] . ' *<br />
							<input id="RegistrationRepeatPass" type="password" name="RegRepeatPass" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="">
							</div>
							<br />';
		}
		else {
			$PasswordFields = '';
		}

		///*JS-массив*///
		$JSIsCaptcha = OBB_CAPTCHA && OBB_REGISTRATION_CAPTCHA;
		$JSIsPassword = $RegistrationType <> '1';
		$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'reg', 'IsCaptcha'=>$JSIsCaptcha, 'IsPassword'=>$JSIsPassword);
		///*JS-массив*///

		$JavaScriptArray = array (
								array ('jquery.js', ''),
								array ('jscal2.js', ''),
								array ($JavaScriptLangArray[$Config_Lang] . '.js', 'lang'),
								array ('functions.js', ''),
								array ('gui.js', ''),
								array ('parameters', $JSParametersArray),
								array ('Registration.js', '')
							);

		//заголовок страницы
		$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Registration'], array('forumname'=>$Config_ForumName));

		//верх
		$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, $JSParametersArray);
		$MainOutput .= '<div>
							<b>' . $ForumLang['Registration'] . ' - ' . $ForumLang['RegStepTwo'] . '</b>
						</div>';

		//если определены ошибки - выводим блок ошибок
		if (isset ($ErrorArray)) {
			$ErrorBlock = Echo_DisplayUserErrors ($ErrorArray, $ForumLang['RegErrors']['ErrorBlockTitle']);
		}
		else {
			$ErrorBlock = '';
		}
		$MainOutput .= '<div id="ErrorBlockDiv">' . $ErrorBlock . '</div>';

		//Главная форма регистрации
		$MainOutput .= '<form id="RegistrationForm" action="' . $SelfName . '?action=registration&r_stage=2&rules_agreement=on" method="POST"' . (OBB_ALLOW_AVATARS == TRUE ? ' enctype="multipart/form-data"' : '') . '>';

		//Логин
		$MainOutput .= '<fieldset style="margin-left:30px; width:80%;">
							<legend>' . $ForumLang['RegPersonMain'] . '</legend>
							<div>'
								. $ForumLang['RegLoginTitle'] . ' (' . OBB_MIN_LOGIN_LENGTH . '-' . OBB_MAX_LOGIN_LENGTH . ' ' . $ForumLang['RegSymbolWord'] . ') *
								<br />
								<input id="RegistrationLogin" type="text" name="RegLogin" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="' . Defence_HTMLSpecials ($RegLogin) . '">
							</div>
							<br />';

		//Пароль
		$MainOutput .= $PasswordFields;

		//Почтовый адрес
		$MainOutput .= '<div>'
							. $ForumLang['RegMailTitle'] . ' (' . OBB_MIN_MAIL_LENGTH . '-' . OBB_MAX_MAIL_LENGTH . ' ' . $ForumLang['RegSymbolWord'] . ') *
							<br />
							<input id="RegistrationMail" type="text" name="RegMail" maxlength="' . OBB_MAX_MAIL_LENGTH . '" value="' . Defence_HTMLSpecials ($RegMail) . '">
						</div>
						<br />
						<div>'
							. $ForumLang['RegRepeatMailTitle'] . ' *<br />
							<input id="RegistrationRepeatMail" type="text" name="RegRepeatMail" maxlength="' . OBB_MAX_MAIL_LENGTH . '" value="' . Defence_HTMLSpecials ($RegRepeatMail) . '">
						</div>
						<br />';

		//Пол
		$MainOutput .= '<div>'
							. $ForumLang['RegSexTitle'] . ' *
							<br />
							<input checked type="radio" name="RegSex" value="male">' . $ForumLang['RegSexMTitle'] . '
							<input type="radio" name="RegSex" value="female">' . $ForumLang['RegSexFTitle'] . '
						</div>
						<br />';

		//Дата рождения
		$MainOutput .= '	<div>'
								. $ForumLang['RegBirthDateTitle'] . ' *
								<br />
								<input id="RegistrationBirthDate" type="text" name="RegBirthDate" maxlength="10" value="' . Defence_HTMLSpecials ($RegBirthDate) . '">
								<a id="CalendarIcon" href="javascript:void(0);"><img src="' . OBB_IMAGE_DIR . '/calendar.png" border="0" /></a>
							</div>
						</fieldset>';

		//Аватар
		if (OBB_ALLOW_AVATARS) {
			$MainOutput .= '<fieldset style="margin-left:30px; width:80%;">
								<legend>'
									. $ForumLang['RegAvatar'] . '
								</legend>
								<div>'
									. $ForumLang['RegTitleAvatar'] . ' (' . $ForumLang['RegMax'] . ' ' . OBB_MAX_AVATAR_SIZE . ' ' . $ForumLang['RegBytes'] . ')
									<input type="file" name="RegAvatar">
								</div>
							</fieldset>';
		}

		//Дополнительные опции - прятать емаил, получать письма
		$MainOutput .= '<fieldset style="margin-left:30px; width:80%;">
							<legend>' . $ForumLang['RegOther'] . '</legend>
							<div>
								<input type="checkbox" ' . (isset ($_POST['RegHideMail'])  ? 'checked ' : '') . 'name="RegHideMail">' . $ForumLang['RegHideMail'] . '<br />
								<input type="checkbox" ' . (isset ($_POST['GetAdminMail']) ? 'checked ' : '') . 'name="GetAdminMail">' . $ForumLang['RegGetAdminMail'] . '<br />
								<input type="checkbox" ' . (isset ($_POST['GetUserMail'])  ? 'checked ' : '') . 'name="GetUserMail">' . $ForumLang['RegGetUserMail'] . '
							</div>
						</fieldset>';

		//капча
		if (OBB_CAPTCHA && OBB_REGISTRATION_CAPTCHA) {
			$MainOutput .= Echo_CaptchaBlock ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'RegistrationCaptcha');
		}

		//кнопка, хидден
		$MainOutput .= '<div id="SubmitButtonDiv">';
		$MainOutput .= '<input id="RegistrationSubmit" type="submit" name="RegSubmit" value="' . $ForumLang['RegContinue'] . '" />';
		$MainOutput .= '</div>';
		$MainOutput .= '<input type="hidden" name="SendRegForm" value="1" />';
		$MainOutput .= '<input type="reset" value="' . $ForumLang['Reset'] . '" />';
		$MainOutput .= '</form>';
		$MainOutput .= $CalendarScript;
		$MainOutput .= '<div>* - ' . $ForumLang['RegFootnote'] . '</div>';

		//Футер форума
		$MainOutput .= Echo_PrintFoot ();

		break;
	}

	case "3": {
		//если не определен ид пользователя или активационный ключ
		if (!isset ($_GET['user_id']) || !ctype_digit ($_GET['user_id']) || !isset ($_GET['activate_key'])) {
			OBB_Main_Redirect ('?action=registration');
		}

		$UserID = intval ($_GET['user_id']);
		$ActivateArray = array ();

		$SQL = 'SELECT UserLogin, UserRegDate, UserIsActivate FROM users WHERE UserID = \'' . $UserID . '\'';
		$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
		if (!$Query) {
			OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		$NumActRows = DB_NumRows ($Config_DBType, $Query);
		if ($NumActRows <> 1) {
			$ActivateArray[] = $ForumLang['ActErrors']['NoUser'];
		}
		else {
			$Week = 60*60*24*7;
			$NowTime = time ();
			$UserRow = DB_FetchAssoc ($Config_DBType, $Query);
			$UserRegDate = $UserRow['UserRegDate'];
			$UserIsActiv = $UserRow['UserIsActivate'];

			if ($UserRegDate < ($NowTime - $Week)) {
				$ActivateArray[] = $ForumLang['ActErrors']['WrongActivationDate'];
			}
			else if ($UserIsActiv == 'yes') {
				$ActivateArray[] = $ForumLang['ActErrors']['AlreadyActivated'];
			}
			else {
				$ActFile = sha1 ($UserID);
				if (!file_exists (OBB_ACTIVATION_DIR . '/' . $ActFile)) {
					$ActivateArray[] = $ForumLang['ActErrors']['WrongKey'];
				}		
				else {
					$ActivateKey = $_GET['activate_key'];
					$RealKey = trim (file_get_contents (OBB_ACTIVATION_DIR . '/' . $ActFile));
					if ($RealKey <> $ActivateKey) $ActivateArray[] = $ForumLang['ActErrors']['WrongKey'];
				}
			}
		}

		if (sizeof ($ActivateArray) == 0) {
			$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
			$Transaction->StartTransaction ();

			//изменение статуса
			$SQL = 'UPDATE users SET UserIsActivate = \'yes\' WHERE UserID=\'' . $UserID . '\'';
			$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			$SQLArray   = array (
			                     'Operation'  => 'u',
								 'FieldArray' => array ('UserIsActivate'=>array ('Value'=>'no', 'Flag'=>'none', 'Type'=>'string')),
								 'WhereArray' => array('UserID'=>array ('Value'=>$UserID, 'Type'=>'int')), 
								 'TableName'  => 'users'
								);
			$Transaction->PrepareQueryToRollback ($SQLArray);

			//запрос - статистика
			$SQLUserStatistics = 'UPDATE 
									statistics 
								SET 
									StatisticsValue=
													(
														SELECT 
															COUNT(*) 
														FROM 
															users 
														WHERE 
															UserIsActivate = \'yes\'
													) 
								WHERE 
									StatisticsKey = \'1\'';
			$UserStatQuery = DB_Query ($Config_DBType, $SQLUserStatistics, $ForumConnection);
			if (!$UserStatQuery) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ($SQLUserStatistics . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			$SQLUserStatArray = array (
										'Operation'  => 'u',
										'FieldArray' => array ('StatisticsValue'=>array ('Value'=>'', 'Flag'=>'decr', 'Type'=>'int')),
										'WhereArray' => array('StatisticsKey'=>array ('Value'=>'1', 'Type'=>'int')), 
										'TableName'  => 'statistics'
									);
			$Transaction->PrepareQueryToRollback ($SQLUserStatArray);

			//удаление файла активации
			if (!unlink (OBB_ACTIVATION_DIR . '/' . $ActFile)) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ('Error while deleting user activate file', OBB_ERROR_LOG_FILE);
			}

			//перенаправление
			$_SESSION['Message'] = 'activate_sucess';
			OBB_Main_Redirect ('?action=message');
		}
		else {
			//JS-массив
			$JavaScriptArray = array (
									array ('jquery.js', ''),
									array ('functions.js', '')
								);

			//заголовок страницы
			$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Registration'], array('forumname'=>$Config_ForumName));

			$MainOutput  = Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);
			$MainOutput .= $MainNav;
			$MainOutput .= '<div>
								<b>' . $ForumLang['Registration'] . ' - ' . $ForumLang['RegStepThree'] . '</b>
							</div>';
			$MainOutput .= Echo_DisplayUserErrors ($ActivateArray, $ForumLang['ActErrors']['ErrorBlockTitle']);
			$MainOutput .= Echo_PrintFoot ();
		}

		break;
	}
}

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;
//echo microtime () - $a;
?>