<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//если не определена переменная сообщения
if (!isset ($_SESSION['Message']) || $_SESSION['Message'] == '') {
	OBB_Main_Redirect ($URL);
}

$ChangeArray = array ();

//сообщение
$Message = strtolower ($_SESSION['Message']);
var_dump($_SESSION);
//Начинаем присвоение
//Начало присвоения - доктайп и хеды 
$MainOutput  = '<!DOCTYPE html>';
$MainOutput .= '<html style="height:100%;"><head><meta content="text/html; charset=utf-8" http-equiv=Content-Type /></head>'; 
$MainOutput .= '<body style="height:100%;">';

//основная часть
switch ($Message) {
	case "reg_success":
	{
		$TemplateName = 'RegSuccess';
		$RedirectURL = '?action=login';
		break;
	}

    case "reg_success_confirm":
	{
		$TemplateName = 'RegSuccessConfirm';
		$RedirectURL = '?action=main';
		break;
	}

	case "auth_sucess":
	{
		$ChangeArray['username'] = Defence_HTMLSpecials ($_SESSION['UserData']['UserName']);
		//$InfoBox = str_replace ('{username}', $_SESSION['UserData']['UserName'], $ForumLang['Messages']['AuthSuccess']);
		$TemplateName = 'AuthSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "logout_sucess":
	{
		$TemplateName = 'LogoutSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "activate_sucess":
	{
		$TemplateName = 'ActivateSuccess';
		$RedirectURL = '?action=login';
		break;
	}

	case "add_theme":
	{
		$TemplateName = 'AddThemeSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "add_post":
	{
		$TemplateName = 'AddPostSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "edit_post":
	{
		$TemplateName = 'EditPostSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "delete_post":
	{
		$TemplateName = 'DeletePostSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "edit_profile":
	{
		$TemplateName = 'EditProfileSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "forgot_password":
	{
		$TemplateName = 'ForgotPassSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "report_post":
	{
		$TemplateName = 'ReportPostSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	case "send_mail":
	{
		$TemplateName = 'SendMailSuccess';
		$RedirectURL = $_SESSION['Redirect'];
		break;
	}

	default:
	{
		break;
	}
}

//
$InfoFile = OBB_TEMPLATE_LANGUAGE_DIR . '/' . $TemplateName . '_' . $Config_Lang . '.html';
if (!file_exists ($InfoFile)) {
	OBB_Main_Log ('Bad template file', OBB_ERROR_LOG_FILE);
}
$InfoBox = file_get_contents ($InfoFile);

//замена
if (sizeof ($ChangeArray) > 0) {
	foreach ($ChangeArray as $Key => $Value) {
		$InfoBox = str_replace ('{' . $Key . '}', $Value, $InfoBox);
	}
}

//Вывод в браузер
$MessageBox = '<table style="height:100%;width:100%;">
			   <tr>
			   <td style="height:100%;width:100%;text-align:center;vertical-align:center;">
			   <a href="' . $RedirectURL . '">' . $InfoBox . '</a>
			   </td></tr>
			   </table>';

//окно сообщения
/* $MainOutput .= '<script type="text/javascript">
				function locate(){
					document.location.href="' . $SelfName . $RedirectURL . '";
				}
				setTimeout("locate()", 10000)
				</script>' . $MessageBox; */
				$MainOutput .= $MessageBox;

$MainOutput .= '</body>';
$MainOutput .= '</html>';

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;

?>