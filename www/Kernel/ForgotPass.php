<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//если не гость - редирект
if ($_SESSION['UserData']['UserType'] <> 'guest') {
	OBB_Main_Redirect ($URL);
}

//подключения
include (OBB_KERNEL_DIR . '/OwnBB.Mail.class.php');
include (OBB_KERNEL_DIR . '/Service.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/ForgotPass_' . $Config_Lang . '.php');

$MainOutput = '';

//инфо о госте
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, '');

//навигация и верх
$NavigArray = array (
				array ($SelfName, $ForumLang['MainPage']),
				array ('', $ForumLang['ForgotPassTitle'])
			);

//Массив Javascript
$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'');

$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Forgot.js', '')
					);

//массив подключемых стилей
$CSSArray = array ('style.css', 'jscal2.css');

//заголовок страницы
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['ForgotPass'], array('forumname'=>$Config_ForumName));

//шапка
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

//массив ошибок
$ForgotErrorArr = array ();

//нажата кнопка "отправить"
if (isset ($_POST['PassForgot'])) {
	//определение
	$ForgotLogin = isset ($_POST['ForgotLogin']) && $_POST['ForgotLogin'] <> '' ? trim ($_POST['ForgotLogin']) : '';
	$ForgotMail  = isset ($_POST['ForgotMail'])  && $_POST['ForgotMail']  <> '' ? trim ($_POST['ForgotMail'])  : '';

	//экранирование
	$UserLogin = Defence_EscapeString ($Config_DBType, $ForgotLogin);
	$UserMail  = Defence_EscapeString ($Config_DBType, $ForgotMail);

	//запрос, есть ли такой юзер
	$UserExistsSQL = 'SELECT UserID FROM users WHERE UserLogin = \'' . $UserLogin . '\' AND UserMail = \'' . $UserMail . '\'';
	$UserExistsQuery = DB_Query ($Config_DBType, $UserExistsSQL, $ForumConnection);
	if (!$UserExistsQuery) {
		OBB_Main_Log ($UserExistsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	$IfUserExists = DB_NumRows ($Config_DBType, $UserExistsQuery);
	$IfUserExists = intval ($IfUserExists);
	if ($IfUserExists < 1) {
		$ForgotErrorArr[] = $ForumLang['ForgotErrors']['UserNotExists'];
	}

	//если ошибок не обнаружено
	if (sizeof ($ForgotErrorArr) < 1) {
		//идентификатор
		$UserID = DB_Result ($Config_DBType, $UserExistsQuery, 0, 0);
		$UserID = intval ($UserID);

		//генерируем новый пароль
		$NewPassword   = Main_GenerateRandString ('10', '1');
		$CryptPassword = Main_Crypt ($NewPassword);

		//запрос изменения пароля у пользователя
		$ChangePassSQL = 'UPDATE users SET UserPassword = \'' . $CryptPassword . '\' WHERE UserID = \'' . $UserID . '\'';
		$ChangePassQuery = DB_Query ($Config_DBType, $ChangePassSQL, $ForumConnection);
		if (!$ChangePassQuery) {
			OBB_Main_Log ($ChangePassSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		//ОТПРАВКА ПИСЕМ
		$ForgotMailer   = new OwnBB_Mailer ($Config_Mail);
		$AdmForgotDate  = Main_ConvertDate (time (), '', $Format = 'd.m.y, H:i');

		//  -отправка пользователю
		$UserLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/UserMailForgot.html');

		$ForgotName  = $ForgotLogin;
		$ForgotPass  = $NewPassword;
		$ForgotTheme = $ForumLang['ForgotUserMailTheme'] . ' "' . $Config_ForumName . '"';
		$ForgotSite  = 'http://' . $_SERVER['HTTP_HOST'];
		$ForgotForum = $Config_ForumName;

		$UserLetter = str_replace ('{username}',    $ForgotName,  $UserLetter);
		$UserLetter = str_replace ('{password}',    $ForgotPass,  $UserLetter);
		$UserLetter = str_replace ('{siteaddress}', $ForgotSite,  $UserLetter);
		$UserLetter = str_replace ('{forumname}',   $ForgotForum, $UserLetter);

		#$RegMailer->SendMail ($ForgotName, $ForgotMail, $ForgotTheme, $UserLetter);
		file_put_contents (OBB_ERROR_MAIL_DIR . '/UserForgotPassLog' . $UserID . '.html', $UserLetter);

		//  -отправка админу
		if ($Config_Mail['AdminMail'] == TRUE) {
			$AdmTheme     = $ForumLang['ForgotAdminMailTheme'] . ' "' . $Config_ForumName . '"';
			$AdminName    = $Config_Mail['FromName'];
			$AdminMail    = $Config_Mail['FromMail'];

			$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/AdminMailForgot.html');

			$AdminLetter = str_replace ('{username}', $ForgotName,    $AdminLetter);
			$AdminLetter = str_replace ('{userid}',   $UserID,        $AdminLetter);
			$AdminLetter = str_replace ('{userdate}', $AdmForgotDate, $AdminLetter);

			#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
			file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_ForgotPassLog' . $UserID . '.html', $AdminLetter);
		}

		//редирект
		$RedirectURL = '?action=login';
		$_SESSION['Message'] = 'forgot_password';
		$_SESSION['Redirect'] = $RedirectURL;
		OBB_Main_Redirect ('?action=message');
	}
}

//вывод ошибок
if (isset ($ForgotErrorArr) && sizeof ($ForgotErrorArr) > 0) {
	$ErrorListBlock = '<div class="ErrorBlockDiv">
							<div class="ErrorBlockTitle">' . $ForumLang['ForgotErrors']['Title'] . ':</div>';
	foreach ($ForgotErrorArr as $Key=>$Value) {
		$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
	}
	$ErrorListBlock .= ' </div>
						<div style="height:15px;"><!-- --></div>';
}
else {
	$ErrorListBlock = '';
}
$MainOutput .= $ErrorListBlock;

//форма
$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=forgot');
$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
					<tr class="MainColumnRow">
						<td colspan="1" class="MainColumnName"><span>' . $ForumLang['ForgotPassTitle'] . '</span></td>
					</tr>
					<tr class="ForumMainTR">
						<td style="padding:0;">
							<form style="padding:0; margin:0;" id="ForgotForm" action="' . $ActionURL . '" method="post">
								<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="border-top:1px solid #FFFFFF; vertical-align:top; width:250px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['ForgotPassLogin'] . '
											</div>
										</td>
										<td style="vertical-align:top; border-top:1px solid #FFFFFF;" class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="ForgotLoginID" type="text" name="ForgotLogin" value="" />
										</td>
									</tr>
									<tr>
										<td style="padding-top:6px; vertical-align:top; width:250px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['ForgotPassMail'] . '
											</div>
											<div class="InputDescr">
												(' . $ForumLang['ForgotPassMailNotice'] . ')
											</div>
										</td>
										<td style="padding-top:6px; vertical-align:top;" class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="ForgotMailID" type="text" name="ForgotMail" value="" />
										</td>
									</tr>
									<tr>
										<td colspan="2" class="FormInputTD CenterTD">
											<div id="SubmitButtonDiv">
												<input class="InpButton" id="ForgotSubmit" type="submit" value="' . $ForumLang['ForgotPassSubmit'] . '" />
												<input class="InpButton" type="reset" value="' . $ForumLang['ForgotPassReset'] . '" />
											</div>
											<input type="hidden" name="PassForgot" value="1" />
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
/* $MainOutput .= '<div>
					<b>' . $ForumLang['ForgotPassTitle'] . '</b>
				</div>
				' . $ForgotErrors . '
				<form id="ForgotForm" name="ForgotForm" action="' . $SelfName . '?action=forgot" method="POST">
					<div>
						<span>' . $ForumLang['ForgotPassLogin'] . '</span>
						<input id="ForgotLoginID" type="text" name="ForgotLogin" value="" />
					</div>
					<br />
					<div>
						<span>' . $ForumLang['ForgotPassMail'] . '</span>
						<input id="ForgotMailID" type="text" name="ForgotMail" value="" />
						<br />
						<span>
							<small>' . $ForumLang['ForgotPassMailNotice'] . '</small>
						</span>
					</div>
					<br />
					<div>
						<input type="hidden" name="PassForgot" value="1" />
						<div id="SubmitButtonDiv">
							<input id="ForgotSubmit" type="submit" value="' . $ForumLang['ForgotPassSubmit'] . '" />
						</div>
					</div>
				</form>'; */

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

//echo microtime () - $a;

?>