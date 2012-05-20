<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//если не определена переменная сообщения
if (!isset ($_SESSION['Message']) || $_SESSION['Message'] == '') {
	OBB_Main_Redirect ();
}

$ChangeArray = array ();

//сообщение
$Message = strtolower ($_SESSION['Message']);

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

if (Main_Strlen ($RedirectURL) < 1) {
	$RedirectURL = 'http://' . $HostName . $SelfName;
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

$PathToMVC = OBB_MVC_DIR . '/Message_html_tpl.php';
ob_start ();
include ($PathToMVC);
$MainOutput = ob_get_contents ();
ob_end_clean ();

//Вывод в браузер
Main_ShowStandartHeader ();
echo $MainOutput;

?>