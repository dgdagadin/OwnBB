<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

$UsersOnlineString = '';

$CommonOnlineArray = OBB_Main_GetUsersOnline ();

$NumUsersOnlineArray = $CommonOnlineArray[0];
$UsersOnlineArray    = $CommonOnlineArray[1];

if ($NumUsersOnlineArray < 1) {
	$UsersOnlineString = $ForumLang['UsersOnlineNoUsers'];
}
else {
	$UsersArray = array ();
	//while ($UsersOnlineRow = DB_FetchAssoc ($Config_DBType, $UsersOnlineQuery)) {
	foreach ($UsersOnlineArray as $key=>$value) {
		$UID    = $value['id'];
		$ULogin = $value['login'];
		$UGroup = $value['group'];

		$UGroupColor = $GroupsArray[$UGroup]['Color'];

		$UserProfileHref = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $UID);
		$OneUser = '<span class="UserGroupSpan" style="color:#' . $UGroupColor . '"><a style="color:#' . $UGroupColor . '" href="' . $UserProfileHref . '">' . Defence_HTMLSpecials ($ULogin) . '</a></span>';
		$UsersArray[] = $OneUser;
	}
	$UsersOnlineString = implode (',&nbsp;', $UsersArray);
}

//Вывод в браузер
echo $UsersOnlineString;

?>