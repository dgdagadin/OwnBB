<?php

$a = microtime ();

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//не дебажить
if (!defined ('NO_DEBUG')) {
	define ('NO_DEBUG', TRUE);
}

//подключения
include (OBB_KERNEL_DIR . '/OwnBB.Mail.Class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Captcha.class.php');
include (OBB_KERNEL_DIR . '/Service.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Tools_' . $Config_Lang . '.php');

$LabelArray = array ('report','smile', 'mail','rules');
$Label = isset ($_GET['label']) && in_array ($_GET['label'], $LabelArray) ? $_GET['label'] : 'rules';

//главная переменная вывода
$MainOutput = '';

//жалоба на сообщение
if ($Label == 'report') {
	//запрещение гостям писать жалобы на сообщения
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
	}

	//проверка на разрешение писать жалобы
	if ($_SESSION['UserData']['UserType'] <> 'admin' && !$UserGroups_Permissions['ReportPosts']) {
		OBB_Main_ShowError ('common_no_access', 'STANDART', $ForumLang['Errors']);
	}

	$UserID = intval ($_SESSION['UserData']['UserID']);

	//идентификатор сообщения
	if (!isset ($_GET['id_post']) || !ctype_digit ($_GET['id_post'])) {
		OBB_Main_ShowError ('report_wrong_post', 'STANDART', $ForumLang['Errors']);
	}
	$IDPost = intval ($_GET['id_post']);

	//проверка того, подавал ли данный пользователь жалобу на этот пост
	$SQL = 'SELECT 1 FROM reports WHERE PostID = \'' . $IDPost . '\' AND UserID = \'' . $UserID . '\'';
	$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	if (DB_NumRows ($Config_DBType, $Query) > 0) {
		OBB_Main_ShowError ('report_report_exist', 'STANDART', $ForumLang['Errors']);
	}

	//получение свойств поста
	$SQL = 'SELECT 
				posts.UserID AS RUserID, 
				posts.ForumID AS RForumID, 
				posts.ThemeID AS RThemeID, 
				forums_list.ForumName AS RForumName, 
				themes.ThemeName AS RThemeName, 
				forums_list.ForumMinStatus AS FGroups 
			FROM 
				posts 
			LEFT JOIN 
				forums_list ON forums_list.ForumID = posts.PostID
			LEFT JOIN 
				themes ON themes.ThemeID = posts.ThemeID
			WHERE 
				PostID = \'' . $IDPost . '\'';
	$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}

	//если такого сообщения не существует
	if (DB_NumRows ($Config_DBType, $Query) < 1) {
		OBB_Main_ShowError ('report_post_not_exist', 'STANDART', $ForumLang['Errors']);
	}

	//присвоение переменных
	$ReportUserID    = DB_Result ($Config_DBType, $Query, 0, 'RUserID');
	$ReportForumID   = DB_Result ($Config_DBType, $Query, 0, 'RForumID');
	$ReportThemeID   = DB_Result ($Config_DBType, $Query, 0, 'RThemeID');
	$ReportForumName = DB_Result ($Config_DBType, $Query, 0, 'RForumName');
	$ReportThemeName = DB_Result ($Config_DBType, $Query, 0, 'RThemeName');
	$ForumGroups     = DB_Result ($Config_DBType, $Query, 0, 'FGroups');

	//intval
	$ReportUserID  = intval ($ReportUserID);
	$ReportForumID = intval ($ReportForumID);
	$ReportThemeID = intval ($ReportThemeID);

	//проверка группы доступа к форуму    
	if ($_SESSION['UserData']['UserType'] <> 'admin' && Access_CheckUserGroup ($ForumGroups) == FALSE) {
		OBB_Main_ShowError ('common_no_report_group_access', 'STANDART', $ForumLang['Errors']);
	}

	//обновление информации о пользователе
	OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'report_post');

	//массив ощибок
	$ReportErrorArray = array ();

	//переменные
	$ReportReason = isset ($_POST['ReportReason']) ? trim ($_POST['ReportReason']) : ''; 

	//если определен POST-массив
	if (isset ($_POST['report'])) {
		//ПРОВЕРКА
		//  --1.Капча
		//  --1.1.Если не админ
		//  --1.2.Если капча включена
		//  --1.3.Если включена капча у группы
		if ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaReportPost']) {
			$CaptchaCheck = Defence_CheckCaptcha ('CaptchaImg');
			if (is_string ($CaptchaCheck)) {
				$ReportErrorArray[] = $ForumLang['ToolErrors'][$CaptchaCheck];
			}
		}

		//  --2.Причина жалобы
		if (Main_Strlen ($ReportReason) == 0 || Main_Strlen ($ReportReason) > OBB_MAX_REPORT_REASON_LENGTH) {
			$ReasonLengthError = OBB_Main_ReplaceSymbols ($ForumLang['ToolErrors']['ReportBadReasonLength'], array('max'=>OBB_MAX_REPORT_REASON_LENGTH));
			$ReportErrorArray[] = $ReasonLengthError;
		}

		//если ошибок не обнаружено
		if (sizeof ($ReportErrorArray) == 0) {
			//подготовка переменных для вставки            
			$InsertDate     = time();
			$InsertPostUser = $ReportUserID;
			$InsertForumID  = $ReportForumID;
			$InsertThemeID  = $ReportThemeID;
			$InsertUserID   = intval ($_SESSION['UserData']['UserID']);
			$InsertReason   = $ReportReason;

			//добавление причины жалобы
			$SQL = 'INSERT INTO reports 
					(PostID, 
					ReportDate, 
					ReportPostUserID, 
					ReportPostForumID, 
					ReportPostThemeID, 
					UserID, 
					ReportReason)
					VALUES
					(\'' . $IDPost . '\',
					\'' . $InsertDate . '\',
					\'' . $InsertPostUser . '\',
					\'' . $InsertForumID . '\',
					\'' . $InsertThemeID . '\',
					\'' . $UserID . '\',
					\'' . Defence_EscapeString ($Config_DBType, $InsertReason) . '\')';
			$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//письмо администратору
			if ($Config_Mail['AdminMail'] == TRUE) {
				$RegMailer      = new OwnBB_Mailer ($Config_Mail);
				$LetterTempPath = OBB_HTML_LANGUAGE_DIR;

				$MailReportID   = DB_LastID ($Config_DBType, $ForumConnection);
				$MailPostID     = $IDPost;
				$MailForumID    = $InsertForumID;
				$MailForumName  = $ReportForumName;
				$MailThemeID    = $InsertThemeID;
				$MailThemeName  = $ReportThemeName;
				$MailDate       = Main_ConvertDate ($InsertDate, '', $Format = 'd.m.y, H:i');
				$MailUserID     = $UserID;
				$MailUserName   = $_SESSION['UserData']['UserName'];
				$MailUserStatus = $_SESSION['UserData']['UserType'];

				$AdmTheme   = $ForumLang['ToolsMailReportAdmin'] . ' "' . $Config_ForumName . '"';
				$AdminName  = $Config_Mail['FromName'];
				$AdminMail  = $Config_Mail['FromMail'];

				$AdminLetter = file_get_contents ($LetterTempPath . '/AdminMailReportPost.html');

				$AdminLetter = str_replace ('{postid}', $MailPostID, $AdminLetter);
				$AdminLetter = str_replace ('{forumid}', $MailForumID, $AdminLetter);
				$AdminLetter = str_replace ('{forumname}', $MailForumName, $AdminLetter);
				$AdminLetter = str_replace ('{themeid}', $MailThemeID, $AdminLetter);
				$AdminLetter = str_replace ('{themename}', $MailThemeName, $AdminLetter);
				$AdminLetter = str_replace ('{reportdate}', $MailDate, $AdminLetter);
				$AdminLetter = str_replace ('{userid}', $MailUserID, $AdminLetter);
				$AdminLetter = str_replace ('{username}', $MailUserName, $AdminLetter);
				$AdminLetter = str_replace ('{userstatus}', $MailUserStatus, $AdminLetter);
				$AdminLetter = str_replace ('{reportid}', $MailReportID, $AdminLetter);

				#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
				file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_ReportLog' . $MailPostID . '.html', $AdminLetter);
			}

			//если ошибок нет - перенаправляем
			$RedirectURL = '?action=themeview&id_forum=' . $MailForumID . '&id_theme=' . $MailThemeID . '&last_post=' . $IDPost . '#pid' . $IDPost;
			$_SESSION['Redirect'] = $RedirectURL;
			$_SESSION['Message']  = 'report_post';
			$URL = '?action=message';
			OBB_Main_Redirect ($URL);
		}
	}

	//навигация
	$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array('', $ForumLang['ToolsReportTitle'])
					);

	//Массив Javascript - НАЧАЛО
	$JSIsCaptcha = $_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaReportPost'];
	$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>$Label, 'IsCaptcha'=>$JSIsCaptcha);

	//JS-массив
	$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Report.js', '')
					);

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Report'], array('forumname'=>$Config_ForumName));

	//верх
	$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

	//ошибка отправки жалобы
	if (sizeof ($ReportErrorArray) > 0) {
		$ErrorListBlock = '<div class="ErrorBlockDiv">
								<div class="ErrorBlockTitle">' . $ForumLang['ToolErrors']['ReportErrorTitle'] . ':</div>';
		foreach ($ReportErrorArray as $Key=>$Value) {
			$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
		}
		$ErrorListBlock .= ' </div>
							<div style="height:15px;"><!-- --></div>';
	}
	else {
		$ErrorListBlock = '';
	}
	$MainOutput .= $ErrorListBlock;

	//описание ввода жалобы
	$ReportDescription = OBB_Main_ReplaceSymbols ($ForumLang['ToolsReportDescription'], array('max'=>OBB_MAX_REPORT_REASON_LENGTH));

	//Капча
	//  --1.1.Если не админ
	//  --1.2.Если капча включена
	//  --1.3.Если включена капча у группы
	if ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaReportPost']) {
		$CaptchaBlock = Echo_CaptchaBlock2 ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'ReportCaptcha');
	}
	else {
		$CaptchaBlock = '';
	}

	//вывод формы
	$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=tooling&label=report&id_post=' . $IDPost);
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName">
									<span>' . $ForumLang['ToolsReportTitle'] . '</span>
								</td>
							</tr>
							<tr class="ForumMainTR">
								<td style="padding:0;">
									<form style="padding:0; margin:0;" id="ReportForm" action="' . $ActionURL . '" method="post">
										<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
											<tr>
												<td style="width:160px; vertical-align:top;" class="FormTitleTD">
													<div class="InputTitle">
														' . $ForumLang['ToolsEnterReportReason'] . '<span class="Important">*</span>
													</div>
													<div class="InputDescr">
														(' . $ReportDescription . ')
													</div>
												</td>
												<td class="FormInputTD TextareaTD">
													<div style="height:7px;"><!-- --></div>
													<div>
														<textarea style="width:95%;" class="InpEl InpTextarea" id="ReportReasonText" name="ReportReason" cols="87" rows="10">' . Defence_HTMLSpecials ($ReportReason) . '</textarea>
													</div>
													<div style="height:19px;"><!-- --></div>
												</td>
											</tr>
											' . $CaptchaBlock . '
											<tr>
												<td style="border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
													<span class="Important">*</span> - ' . $ForumLang['ToolsImportantFields'] . '
												</td>
											</tr>
											<tr>
												<td colspan="2" class="FormInputTD CenterTD">
													<input type="hidden" name="report" value="1" />
													<div id="SubmitButtonDiv">
														<input class="InpButton" id="EditSubmit" type="submit" name="Adding"   value="' . $ForumLang['ToolsReportSubmit'] . '" />
														<input class="InpButton" type="button" name="Adding" value="' . $ForumLang['ToolsReset'] . '" />
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

	//вывод в браузер
	Main_ShowStandartHeader ();
	echo $MainOutput;
}

//отправка сообщения по почте
if ($Label == 'mail') {
	//гостям не разрешено
	if ($_SESSION['UserData']['UserType'] == 'guest') {
		OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);	
	}

	//проверка на разрешение отсылать сообшение по почте
	if ($_SESSION['UserData']['UserType'] <> 'admin' && (!$UserGroups_Permissions['MailSendAllowed'] || !OBB_MAIL_ALLOWED)) {
		OBB_Main_ShowError ('common_no_access', 'STANDART', $ForumLang['Errors']);
	}

	$FromUserID   = intval ($_SESSION['UserData']['UserID']);
	$FromUserName = Defence_HTMLSpecials ($_SESSION['UserData']['UserName']);

	//идентификатор пользователя
	if (!isset ($_GET['id_user']) || !ctype_digit ($_GET['id_user'])) {
		OBB_Main_ShowError ('profile_wrong_user_id', 'STANDART', $ForumLang['Errors']);
	}
	$ToUserID = intval ($_GET['id_user']);

	//проверка пользователя
	$SQL = 'SELECT UserLogin, UserMail, UserAdminMail, UserOtherMail FROM users WHERE UserID = \'' . $ToUserID . '\'';
	$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	if (DB_NumRows ($Config_DBType, $Query) < 1) {
		OBB_Main_ShowError ('profile_no_user', 'STANDART', $ForumLang['Errors']);
	}

	//присвоение
	$UserName      = DB_Result ($Config_DBType, $Query, 0, 'UserLogin');
	$UserMail      = DB_Result ($Config_DBType, $Query, 0, 'UserMail');
	$UserAdminMail = DB_Result ($Config_DBType, $Query, 0, 'UserAdminMail');
	$UserOtherMail = DB_Result ($Config_DBType, $Query, 0, 'UserOtherMail');

	//разрешил ли пользователь отправлять себе сообщения
	$CanSendMail = $UserOtherMail == 'yes';

	//если не разрешено отправлять
	if (!$CanSendMail && $_SESSION['UserData']['UserType'] <> 'admin') {
		OBB_Main_ShowError ('mail_no_send_allowed', 'STANDART', $ForumLang['Errors']);
	}

	//переменные
	$MailContentVar = isset ($_POST['MailContent']) ? trim ($_POST['MailContent']) : '';

	//основная переменная вывода
	$MainOutput = '';

	//обновление информации о пользователе
	OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'send_mail');

	//массив ошибок
	$MailErrorArray = array ();

	//переменная редиректа
	$RedirectURL = 'http://' . $HostName . $SelfName;
	$RedirectURLPattern = '/http:\/\/' . preg_quote ($HostName, "/") . preg_quote ($SelfName, "/") . '(?:\?action=[_a-z]{3-10}(?:&[_a-z]{2,20}=(.*?))*)?/ui';
	if (!isset ($_POST['MailRedirectURL'])) { 
		if (isset ($_SERVER['HTTP_REFERER'])) {
			if (preg_match ($RedirectURLPattern, $_SERVER['HTTP_REFERER'])) {
				preg_match_all ('/\?action=(.*?)&/ui', $_SERVER['HTTP_REFERER'], $matches);
				if (!isset ($matches[1][0]) || Main_Strtolower ($matches[1][0]) == 'message' || Main_Strlen ($matches[1][0]) < 1) {
					$RedirectURL = 'http://' . $HostName . $SelfName;
				}
				else {
					$RedirectURL = $_SERVER['HTTP_REFERER'];
				}
			}
		}
	}
	else {
		if (preg_match ($RedirectURLPattern, $_POST['MailRedirectURL'])) {
			$RedirectURL = $_POST['MailRedirectURL'];
		}
	}
	//переменная редиректа - КОНЕЦ

	//если нажата кнопка "Отправить"
	if (isset ($_POST['mail'])) {
		//Проверка на ошибки
		//ПРОВЕРКА
		//  --1.Капча
		//  --1.1.Если не админ
		//  --1.2.Если капча включена
		//  --1.3.Если включена капча у группы
		if ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaSendMail']) {
			$CaptchaCheck = Defence_CheckCaptcha ('CaptchaImg');
			if (is_string ($CaptchaCheck)) {
				$MailErrorArray[] = $ForumLang['ToolErrors'][$CaptchaCheck];
			}
		}

		//  --2)содержимое письма
		if (Main_Strlen ($MailContentVar) == 0 || Main_Strlen ($MailContentVar) > OBB_MAIL_MAX_LETTER_LENGTH) {
			$WrongLetterLength = OBB_Main_ReplaceSymbols ($ForumLang['ToolErrors']['MailBadMailLetterLength'], array('max'=>OBB_MAIL_MAX_LETTER_LENGTH));
			$MailErrorArray[] = $WrongLetterLength;
		}

		//если ошибок нет
		if (sizeof ($MailErrorArray) == 0) {
			//отправка почты выбранному пользователю
			$Mail           = new OwnBB_Mailer ($Config_Mail);
			$LetterTempPath = OBB_LANGUAGE_DIR . '/HTMLTemplates/';

			$MailDate = Main_ConvertDate (time(), '', $Format = 'd.m.y, H:i');
			$MailTheme = OBB_Main_ReplaceSymbols ($ForumLang['ToolsMailTheme'], array('username'=>$FromUserName, 'date'=>$MailDate));

			$ToName  = $UserName;
			$ToMail  = $UserMail;

			$AdminLetter = $MailContentVar;

			#$RegMailer->SendMail ($ToName, $ToMail, $MailTheme, $AdminLetter);
			file_put_contents (OBB_ERROR_MAIL_DIR . '/User_SendMail' . time() . '.html', $AdminLetter);

			//перенаправление
			$_SESSION['Redirect'] = str_replace ('http://' . $HostName . $SelfName, '', $RedirectURL);
			$_SESSION['Message']  = 'send_mail';
			$URL = '?action=message';
			OBB_Main_Redirect ($URL);
		}
	}

	//навигация
	$ToolsMailTitleTo = OBB_Main_ReplaceSymbols ($ForumLang['ToolsMailTitleTo'], array('username'=>$UserName));
	$ToolsMailTitleTo = Defence_HTMLSpecials ($ToolsMailTitleTo);
	$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array('', $ToolsMailTitleTo)
					);

	//Массив Javascript - НАЧАЛО
	$JSIsCaptcha = $_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaSendMail'];
	$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>$Label, 'IsCaptcha'=>$JSIsCaptcha);
	//Массив JavaScript - КОНЕЦ

	//JS-массив
	$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Mail.js', '')
					);

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['SendMail'], array('forumname'=>$Config_ForumName));

	//верх
	$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

	//ошибка отправки почты
	if (sizeof ($MailErrorArray) > 0) {
		$ErrorListBlock = '<div class="ErrorBlockDiv">
								<div class="ErrorBlockTitle">' . $ForumLang['ToolErrors']['MailErrorTitle'] . ':</div>';
		foreach ($MailErrorArray as $Key=>$Value) {
			$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
		}
		$ErrorListBlock .= ' </div>
							<div style="height:15px;"><!-- --></div>';
	}
	else {
		$ErrorListBlock = '';
	}
	$MainOutput .= $ErrorListBlock;

	//КАПЧА
	//  --1.Капча
	//  --1.Если не админ
	//  --2.Если капча включена
	//  --3.Если включена капча у группы
	$CaptchaBlock = '';
	if ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_CAPTCHA && $UserGroups_Permissions['CaptchaSendMail']) {
		$CaptchaBlock = Echo_CaptchaBlock2 ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'MailCaptcha');
	}
	else {
		$CaptchaBlock = '';
	}
	
	//описание ввода жалобы
	$MailDescription = OBB_Main_ReplaceSymbols ($ForumLang['ToolsMailDescription'], array('max'=>OBB_MAIL_MAX_LETTER_LENGTH));

	//вывод формы
	$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=tooling&label=mail&id_user=' . $ToUserID);
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName">
									<span>' . $ForumLang['ToolsMailTitle'] . '</span>
								</td>
							</tr>
							<tr class="ForumMainTR">
								<td style="padding:0;">
									<form style="padding:0; margin:0;" id="MailForm" action="' . $ActionURL . '" method="post">
										<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
											<tr>
												<td style="width:160px; vertical-align:top;" class="FormTitleTD">
													<div class="InputTitle">
														' . $ForumLang['ToolsEnterMailContent'] . '<span class="Important">*</span>
													</div>
													<div class="InputDescr">
														(' . $MailDescription . ')
													</div>
												</td>
												<td class="FormInputTD TextareaTD">
													<div style="height:7px;"><!-- --></div>
													<div>
														<textarea style="width:95%;" class="InpEl InpTextarea" id="MailContentText" name="MailContent" cols="87" rows="10">' . Defence_HTMLSpecials ($MailContentVar) . '</textarea>
													</div>
													<div style="height:19px;"><!-- --></div>
												</td>
											</tr>
											' . $CaptchaBlock . '
											<tr>
												<td style="border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
													<span class="Important">*</span> - ' . $ForumLang['ToolsImportantFields'] . '
												</td>
											</tr>
											<tr>
												<td colspan="2" class="FormInputTD CenterTD">
													<input type="hidden" name="mail" value="1" />
													<div id="SubmitButtonDiv">
														<input class="InpButton" id="MailSubmit" type="submit" name="MailSubmit" value="' . $ForumLang['MailSubmit'] . '" />
														<input class="InpButton" type="button" name="Adding" value="' . $ForumLang['ToolsReset'] . '" />
													</div>
													<input type="hidden" name="MailRedirectURL" value="' . Defence_HTMLSpecials ($RedirectURL) . '" />
													<input type="hidden" name="mail" value="1" />
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
	
	//вывод в браузер
	Main_ShowStandartHeader ();
	echo $MainOutput;
}

//получение набора всех смайлов
else if ($Label == 'smile') {
	//получение ид формы
	$FormIDArray = array ('AddForm', 'EditForm', 'ShortAddForm', 'ProfileForm');
	if (!isset ($_GET['f_id']) || !in_array ($_GET['f_id'], $FormIDArray)) {
		$FormID = 'PostContent';
	}
	else {
		$FormID = Defence_HTMLSpecials ($_GET['f_id']);
	}

	//получение ид текстарии
	$TextareaIDArray = array ('PostContent', 'EditPostContent', 'ShortPostContent', 'ProfileSlogan');
	if (!isset ($_GET['t_id']) || !in_array ($_GET['t_id'], $TextareaIDArray)) {
		$TextareaID = 'PostContent';
	}
	else {
		$TextareaID = Defence_HTMLSpecials ($_GET['t_id']);
	}

	//выборка смайлов из таблицы
	$SmilesArray = array ();
	$SQL = 'SELECT * FROM smiles';
	$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
	if (!$Query) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	while ($Row = DB_FetchAssoc ($Config_DBType, $Query)) {
		$SmileChars = $Row['SmileChars'];
		$SmileImg   = $Row['SmileImage'];
		$SmileName  = $Row['SmileName'];

		//если файл смайла существует - добавляем его в массив
		if (file_exists (OBB_SMILES_DIR . '/' . $SmileImg)) {
			$SmilesArray[] = array ($SmileChars, $SmileImg, $SmileName);
		}
	}

	//главная переменная вывода
	$MainOutput = '';

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Smiles'], array('forumname'=>$Config_ForumName));

	//Вывод смайлов
	$MainOutput = '';
	
	foreach ($SmilesArray as $key=>$value) {
		$SmileText = $value[0];
		$SmileImg  = $value[1];
		$SmileName = $value[2];

		$SmileHref = 'http://' . $HostName . '/' . OBB_ROOT_DIR . '/' . OBB_SMILES_DIR . '/' . $SmileImg;

		$MainOutput .= '<a title="' . $SmileName . '" href="javascript:void(0);" onclick="addSmilie(\'' . $FormID . '\', \'' . $TextareaID . '\', \'' . $SmileText . '\');"><img alt="' . $SmileImg . '" src="' . $SmileHref . '" border="0" /></a>&nbsp;&nbsp;';
	}
	/* $MainOutput .= '</body>';
	$MainOutput .= '</html>'; */

	//Вывод смайлов
	Main_ShowStandartHeader ();
	echo $MainOutput;    
}

//правила форума
else if ($Label == 'rules') {
	//Функция обновления онлайн-информации
	OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'read_rules');

	//правила форума
	$RulesHTMLFile = OBB_HTML_LANGUAGE_DIR . '/Rules.html';
	if (!file_exists ($RulesHTMLFile)) {
		OBB_Main_Log ('No rulefile', OBB_ERROR_LOG_FILE);
	}
	$Rules = Main_GetCachedData ($RulesHTMLFile);

	//главная переменная вывода
	$MainOutput = '';

	//JS-массив
	$JavaScriptArray = array (
							array ('jquery.js', ''),
							array ('functions.js', ''),
							array ('gui.js', '')
						);

	//навигация
	$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array('', $ForumLang['ToolsRulesTitle'])
					);

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Rules'], array('forumname'=>$Config_ForumName));

	//верх
	$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

	//вывод правил форума
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
							<tr class="MainColumnRow">
								<td style="border-bottom:1px solid #FFF !important;" colspan="1" class="MainColumnName">
									<span>' . $ForumLang['ToolsForumRules'] . '</span>
								</td>
							</tr>
							<tr>
								<td style="border-top:0 !important; padding:15px 15px 25px 15px;" class="ForumUpdateTD">
									' . $Rules . '
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

	//вывод в браузер
	Main_ShowStandartHeader ();
	echo $MainOutput;
}

//echo microtime() - $a;

?>