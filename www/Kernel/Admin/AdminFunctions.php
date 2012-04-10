<?php

//функция показа ошибки
function OBB_Admin_ShowError ($ErrorFlag) {
	global $VALIDATION;

	switch ($ErrorFlag) {
		case'NO_ACCESS': {
			$NoAccess = TRUE;
			include (OBB_ADMIN_DIR . '/AdminLogin.php');
			break;
		}
	}
	
	exit;
}

//функция проверки на администратора
function OBB_Admin_CheckAdminPermissions () {
	global $SelfName, $AdminName, $HostName;

	if ($_SESSION['UserData']['UserType'] == 'guest') {
		OBB_Main_Redirect ('?action=login');
		return;
	}
	else {
		if 
		(
			$_SESSION['UserData']['UserType'] <> 'admin'
			||
			(
				$_SESSION['UserData']['UserType'] == 'admin' 
				&& 
				!isset ($_SESSION['UserData']['ConfirmAdmin'])
			)
		) {
			OBB_Admin_ShowError ('NO_ACCESS');
			return;
		}
	}
	return;
}

//функция получения текущего действия
function OBB_Admin_GetAdminModule () {
	$AdminActionArray = array ( 'main', 'config', 'install', 'clean', 'ban', 'cat', 'forum', 'theme', 'post', 'user', 'group', 'report', 'smile', 'login', 'logout', 'msg', 'other');

	$AdminModuleArray = array (
							'main'   =>'AdminMain',
							'config' =>'AdminConfig',
							'install'=>'AdminFastInstall',
							'clean'  =>'AdminClean',
							'ban'    =>'AdminBans',
							'cat'    =>'AdminCategories',
							'forum'  =>'AdminForums',
							'theme'  =>'AdminThemes',
							'user'   =>'AdminUsers',
							'group'  =>'AdminGroups',
							'report' =>'AdminReports',
							'smile'  =>'AdminSmiles',
							'login'  =>'AdminLogin',
							'other'  =>'AdminOthers',
							'msg'    =>'AdminMessage'
						);

	$AdminActionVariable = 'adm';

	return (OBB_Main_GetModule ($AdminActionArray, $AdminModuleArray, $AdminActionVariable));
}

?>