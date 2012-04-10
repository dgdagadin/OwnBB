<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//проверка на доступ
if ($_SESSION['UserData']['UserType'] <> 'admin') {
	//  --если запрещен просмотр профиля на форуме
	if (!OBB_WATCH_PROFILE) {
		OBB_Main_ShowError ('common_no_access', 'STANDART', $ForumLang['Errors']);
	}

	//  --если запрещен просмотр профиля в группе
	if (!$UserGroups_Permissions['VisitUserProfile']) {
		$PermissionsFlag = $_SESSION['UserData']['UserType'] <> 'guest' ? 'STANDART' : 'NO_ACCESS';
		OBB_Main_ShowError ('common_no_access', $PermissionsFlag, $ForumLang['Errors']);
	}
}

//id пользователя
if (!isset ($_GET['user_id']) || !ctype_digit ($_GET['user_id'])) {
	OBB_Main_ShowError ('profile_wrong_user_id', 'STANDART', $ForumLang['Errors']);
}
$UserID = intval ($_GET['user_id']); 

//Проверка на существование пользователя
$SQL = 'SELECT 1 FROM users WHERE UserID = ' . $UserID;
$Query = DB_Query ($Config_DBType, $SQL, $ForumConnection);
if (!$Query) {
	OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
if (DB_NumRows ($Config_DBType, $Query) < 1) {
	OBB_Main_ShowError ('profile_no_user', 'STANDART', $ForumLang['Errors']);
}

//подключаемые файлы
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
//include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/UserProfile_' . $Config_Lang . '.php');

//получение ресурса парсера
$signature_bb = OBB_BBCode_GetParseSign ();

//определение, может ли заходящий редактировать данный профиль
//  --если админ
//  --если владелец профиля
//  --1)если разрешено редактирвоать в общем
//  --2)если разрешено редактировать в группе
$CanEditProfile = false;
if ($_SESSION['UserData']['UserType'] == 'admin') {
	$CanEditProfile = TRUE;
}
else if ($_SESSION['UserData']['UserType'] <> 'guest') {
	if (
		OBB_EDIT_PROFILE && 
		$UserGroups_Permissions['EditUserProfile'] && 
		($UserID == intval ($_SESSION['UserData']['UserID']))
	) {
		$CanEditProfile = TRUE;
	}
}

//определение, является ли данный пользователь владельцем профиля
if ($_SESSION['UserData']['UserType'] <> 'guest' && ($UserID == intval ($_SESSION['UserData']['UserID']))) {
	$ProfileOwner = TRUE;
}
else {
	$ProfileOwner = false;
}

//массив дат
$DateArray = $ForumLang['DateArray'];

//выбор групп
$UserGroupArr = OBB_Main_GetUserGroups ();

//Функция обновления онлайн-информации о пользователе/госте
if ($CanEditProfile) {
	if ($ProfileOwner) {
		$CurAction = 'edit_own_profile';
	}
	else {
		$CurAction = 'edit_profile';
	}
}
else {
	$CurAction = 'view_profile';
}
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//Запрос пользователя (великий))
$UserDataSQL = 'SELECT users.UserLogin AS Login,
					users.GroupID AS GroupID, 
					users.UserSlogan AS Slogan,
					users.UserMail AS Mail,
					users.UserMailHid AS MailHid,
					users.UserAdminMail AS AdminMail,
					users.UserOtherMail AS OtherMail,
					users.UserRegDate AS RegDate,
					users.UserSex AS USex,
					users.UserBirthDate AS Birth,
					users.UserSite AS Site,
					users.UserCountry AS Country,
					users.UserCity AS City,
					users.UserICQ AS ICQ,
					users.UserPhone AS Phone,
					users.UserMobile AS Mobile,
					users.UserNumThemes AS NumThemes,
					users.UserNumPosts AS NumPosts,
					users.GroupID AS GroupID,
					users.UserIsActivate AS IsActivated,
					users.UserAvatar AS Avatar,
					user_bans.UserBanTime AS UBanTime, 
					user_bans.UserBanPeriod AS UBanPeriod,
					user_activity.UserLastLogin AS LastLogin,
					user_activity.UserLastAction AS LastAction,
					user_activity.UserIsOnline AS IsOnline
				FROM users
				LEFT JOIN user_activity ON users.UserID = user_activity.UserID
				LEFT JOIN user_bans ON user_bans.UserID = users.UserID
				WHERE users.UserID = \'' . $UserID . '\'';
$UserDataQuery = DB_Query ($Config_DBType, $UserDataSQL, $ForumConnection);
if (!$UserDataQuery) {
	OBB_Main_Log ($UserDataSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$IsExist = DB_NumRows ($Config_DBType, $UserDataQuery);
if ($IsExist < 1) {
	OBB_Main_ShowError ('profile_no_user', 'STANDART', $ForumLang['Errors']);
}

//Присвоение ПЕРЕМЕННЫХ
$ProfileLogin       = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Login'     );
$ProfileSlogan      = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Slogan'    );
$ProfileMail        = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Mail'      );
$ProfileMailHidden  = DB_Result ($Config_DBType, $UserDataQuery, 0, 'MailHid'   );
$ProfileSex         = DB_Result ($Config_DBType, $UserDataQuery, 0, 'USex'      );
$ProfileAdminMail   = DB_Result ($Config_DBType, $UserDataQuery, 0, 'AdminMail' );
$ProfileOtherMail   = DB_Result ($Config_DBType, $UserDataQuery, 0, 'OtherMail' );
$ProfileBirth       = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Birth'     );
$ProfileSite        = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Site'      );
$ProfileCity        = DB_Result ($Config_DBType, $UserDataQuery, 0, 'City'      );
$ProfileICQ         = DB_Result ($Config_DBType, $UserDataQuery, 0, 'ICQ'       );
$ProfilePhone       = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Phone'     );
$ProfileMobile      = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Mobile'    );
$ProfileCountry     = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Country'   );
$ProfileAvatar      = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Avatar'    );
$ProfileLogin       = DB_Result ($Config_DBType, $UserDataQuery, 0, 'Login'     );
$ProfileGroupID     = DB_Result ($Config_DBType, $UserDataQuery, 0, 'GroupID'   );
$ProfileRegDate     = DB_Result ($Config_DBType, $UserDataQuery, 0, 'RegDate'   );
$ProfileNumThemes   = DB_Result ($Config_DBType, $UserDataQuery, 0, 'NumThemes' );
$ProfileNumPosts    = DB_Result ($Config_DBType, $UserDataQuery, 0, 'NumPosts'  );
$ProfileLastLogin   = DB_Result ($Config_DBType, $UserDataQuery, 0, 'LastLogin' );
$ProfileLastAction  = DB_Result ($Config_DBType, $UserDataQuery, 0, 'LastAction');
$ProfileIsOnline    = DB_Result ($Config_DBType, $UserDataQuery, 0, 'IsOnline'  );
$ProfileGroupID     = DB_Result ($Config_DBType, $UserDataQuery, 0, 'GroupID'   );

//получение разрешений просматриваемого пользователя
if ($ProfileOwner) {
	$CurrentPermissions = $UserGroups_Permissions;
}
else {
	$CurrentPermissions = OBB_Main_GetPermissionsByGroup ($ProfileGroupID);
}

//Флаг аватара
//  --если вошедший админ и редактируемый алмин
//  --если вошедший админ и у редактируемого есть права
//  --если вошедший и редактируемый - один пользователь и у него есть права
$AvatarFlag = false;
if (
	$CanEditProfile   && 
	OBB_ALLOW_AVATARS && 
	(
		OBB_Main_IsAdminGroup ($ProfileGroupID) || 
		(
			!OBB_Main_IsAdminGroup ($ProfileGroupID) && 
			$CurrentPermissions['AvatarsAllowed']
		)
	)
) {
	$AvatarFlag = TRUE;
}

//массив ошибок
$ProfileEditError = array ();

//Главная переменнвя вывода
$MainOutput = '';

//определение переменных, пришедших с _POST
//  --текст
$ProfilePasswordVar = $ProfileOwner && $CanEditProfile && isset ($_POST['ProfilePassword'])   && $_POST['ProfilePassword']   <> '' ? trim ($_POST['ProfilePassword'])   : '';
$ProfilePRepeatVar  = $ProfileOwner && $CanEditProfile && isset ($_POST['ProfilePassRepeat']) && $_POST['ProfilePassRepeat'] <> '' ? trim ($_POST['ProfilePassRepeat']) : '';
$ProfileCountryVar  = $CanEditProfile && isset ($_POST['ProfileCountry']) ? trim ($_POST['ProfileCountry'])    : $ProfileCountry;
$ProfileCityVar     = $CanEditProfile && isset ($_POST['ProfileCity'])    ? trim ($_POST['ProfileCity'])       : $ProfileCity;
$ProfileBirthVar    = $CanEditProfile && isset ($_POST['ProfileBirth'])   ? trim ($_POST['ProfileBirth'])      : $ProfileBirth;
$ProfileSiteVar     = $CanEditProfile && isset ($_POST['ProfileSite'])    ? trim ($_POST['ProfileSite'])       : $ProfileSite;
$ProfileSloganVar   = $CanEditProfile && isset ($_POST['ProfileSlogan'])  ? trim ($_POST['ProfileSlogan'])     : $ProfileSlogan;
$ProfileICQVar      = $CanEditProfile && isset ($_POST['ProfileICQ'])     ? trim ($_POST['ProfileICQ'])        : $ProfileICQ;
$ProfilePhoneVar    = $CanEditProfile && isset ($_POST['ProfilePhone'])   ? trim ($_POST['ProfilePhone'])      : $ProfilePhone;
$ProfileMobileVar   = $CanEditProfile && isset ($_POST['ProfileMobile'])  ? trim ($_POST['ProfileMobile'])     : $ProfileMobile;
$ProfileMailVar     = $CanEditProfile && isset ($_POST['ProfileMail'])    ? trim ($_POST['ProfileMail'])       : $ProfileMail;

//  --пол
$ProfileSexArray = array ('male', 'female');
if (isset ($_POST['ProfileSex']) && in_array ($_POST['ProfileSex'], $ProfileSexArray) && $CanEditProfile) {
	$ProfileSexVar = trim ($_POST['ProfileSex']);
}
else {
	$ProfileSexVar = $ProfileSex;
}

//  --сокрытие электронной почты
if (isset ($_POST['EditProfile']) && $CanEditProfile) {
	if (isset ($_POST['ProfileHidMail'])) {
		$ProfileMailHiddenVar = 'yes';
	}
	else {
		$ProfileMailHiddenVar = 'no';
	}
}
else {
	$ProfileMailHiddenVar = $ProfileMailHidden;
}

//  --разрешение получения почты администрации
if (isset ($_POST['EditProfile']) && $CanEditProfile) {
	if (isset ($_POST['ProfileAdmMail'])) {
		$ProfileAdminMailVar = 'yes';
	}
	else {
		$ProfileAdminMailVar = 'no';
	}
}
else {
	$ProfileAdminMailVar = $ProfileAdminMail;
}

//  --разрешение получения почты пользователей
if (isset ($_POST['EditProfile']) && $CanEditProfile) {
	if (isset ($_POST['ProfileOthMail'])) {
		$ProfileOtherMailVar = 'yes';
	}
	else {
		$ProfileOtherMailVar = 'no';
	}
}
else {
	$ProfileOtherMailVar = $ProfileOtherMail;
}

//  --удаление аватара
$ProfileNoUseAvatar = $CanEditProfile && isset ($_POST['ProfileNoUseAvatar']) && $_POST['ProfileNoUseAvatar'] == TRUE ? 'yes' : 'no';

//если нажата кнопка submit
if (isset ($_POST['EditProfile']) && $CanEditProfile) {
	//ПРОВЕРКА
	//  --1)капча
	if ($CanEditProfile && $_SESSION['UserData']['UserType'] <> 'admin' && $UserGroups_Permissions['CaptchaEditProfile']) {
		$CaptchaChech = Defence_CheckCaptcha ('CaptchaImg');
		if (is_string ($CaptchaChech)) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors'][$CaptchaChech];
		}
	}

	//  --2)Пароль
	//  --ПРИМЕЧАНИЕ - только владелец профиля может изменять пароль
	$PasswordSQL = '';
	if ($ProfileOwner && Main_Strlen ($ProfilePasswordVar) > 0) {
		$NewPassLength = Main_Strlen ($ProfilePasswordVar);
		if ($NewPassLength < OBB_MIN_PASSWORD_LENGTH || $NewPassLength > OBB_MAX_PASSWORD_LENGTH) {
			$WrongPasswordLength = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongPasswordLength'], array('min'=>OBB_MIN_PASSWORD_LENGTH, 'max'=>OBB_MAX_PASSWORD_LENGTH));
			$ProfileEditError[] = $WrongPasswordLength;
		}
		else {
			if ($ProfilePasswordVar <> $ProfilePRepeatVar) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongPasswordEqual'];
			}
			else {
				$HashedPassword = Main_Crypt ($ProfilePasswordVar);
				$PasswordSQL = 'UserPassword = \'' . $HashedPassword . '\', ';
			}
		}
	}

	// --3)Почта
	if ($ProfileMail <> $ProfileMailVar) {
		$CheckMailArray = OBB_Defence_CheckMail ($ProfileMailVar);
		$IsMailTrue = $CheckMailArray[0];
		if ($IsMailTrue == FALSE) {
			$MailError = $CheckMailArray[1];
			$ProfileEditError[] = $ForumLang['UserProfileErrors'][$MailError];
		}
	}

	//  --4)Дата рождения
	if ($ProfileBirth <> $ProfileBirthVar) {
		if (Main_Strlen ($ProfileBirthVar) == 0) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['EmptyDate'];
		}
		else {
			$DatePattern = '/^[0-3][\d][-\.\/][01][\d][-\.\/][12][\d]{3}$/ui';
			if (!preg_match ($DatePattern, $ProfileBirthVar)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongDateFormat'];
			}
			/* else {
				проверка актуальности даты
			} */
		}
	}

	//  --5)Девиз
	if (Main_Strlen ($ProfileSloganVar) > 0) {
		$SloganLength = Main_Strlen ($ProfileSloganVar);
		if ($SloganLength > OBB_MAX_SLOGAN_LENGTH) {
			$WrongSloganLengthError = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongSloganLength'], array('max'=>OBB_MAX_SLOGAN_LENGTH));
			$ProfileEditError[] = $WrongSloganLengthError;
		}
		else {
			$NumSloganRows = Defence_GetTextNumRows ($ProfileSloganVar);
			if ($NumSloganRows > OBB_MAX_SLOGAN_ROWS) {
				$WrongSloganRowsError = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongSloganRows'], array('max'=>OBB_MAX_SLOGAN_ROWS));
				$ProfileEditError[] = $WrongSloganRowsError;
			}
		}
	}

	//  --6)Аватар
	if ($AvatarFlag) {
		if ($ProfileNoUseAvatar == 'yes') {
			$ProfileAvatarVar = '';
			$AvatarAction  = !OBB_Main_IsEmpty ($ProfileAvatar) ? 'delete' : 'no_action';
		}
		else {
			if (isset ($_FILES['ProfileAvatar']['name']) && $_FILES['ProfileAvatar']['name'] <> '') {
				$AvatarDataArray = $_FILES['ProfileAvatar'];
				$AvatarObject = new OwnBB_CheckUpload ($FilesArray, OBB_MAX_AVATAR_SIZE, OBB_MAX_AVATAR_WIDTH, OBB_MAX_AVATAR_HEIGHT);
				$AttachArray = $AvatarObject->FullCheckOfUpload ($AvatarDataArray);

				$IsTrue = $AttachArray['IsTrue'];
				if ($IsTrue == FALSE) {
					$LangAttach    = $ForumLang['Attach'];
					$FileErrorCode = $AttachArray['Error'];
					$ProfileEditError[] = $LangAttach[$FileErrorCode];
					$AvatarAction = NULL;
				}
				else {
					$IsImage = $AttachArray['Image'];
					if ($IsImage <> '1') {
						$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongAvatar'];
					}
					else {
						$ProfileAvatarVar = Main_Strtolower ($AttachArray['Ext']);
						$AvatarAction  = !OBB_Main_IsEmpty ($ProfileAvatar) ? 'update' : 'insert';
					}
				}
			}
			else {
				$ProfileAvatarVar = $ProfileAvatar;
				$AvatarAction  = 'no_action';
			}
		}
	}
	else {
		$ProfileAvatarVar = $ProfileAvatar;
		$AvatarAction  = 'no_action';
	}

	//  --7)Город
	if (Main_Strlen ($ProfileCityVar) > 0) {
		if (Main_Strlen ($ProfileCityVar) < OBB_MIN_CITY_LENGTH || Main_Strlen ($ProfileCityVar) > OBB_MAX_CITY_LENGTH) {
			$WrongCityLengthError = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongCityLength'], array('min'=>OBB_MIN_CITY_LENGTH,'max'=>OBB_MAX_CITY_LENGTH));
			$ProfileEditError[] = $WrongCityLengthError;
		}
		else {
			$CityPattern = '/^[a-zа-я][-a-zа-я ]*$/ui';
			if (!preg_match ($CityPattern, $ProfileCityVar)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCityFormat'];
			}
		}
	}

	//  --8)Страна
	if (Main_Strlen ($ProfileCountryVar) > 0) {
		if (Main_Strlen ($ProfileCountryVar) < OBB_MIN_COUNTRY_LENGTH || Main_Strlen ($ProfileCountryVar) > OBB_MAX_COUNTRY_LENGTH) {
			$WrongCountryLengthError = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongCountryLength'], array('min'=>OBB_MIN_COUNTRY_LENGTH,'max'=>OBB_MAX_COUNTRY_LENGTH));
			$ProfileEditError[] = $WrongCountryLengthError;
		}
		else {
			$CountryPattern = '/^[a-zа-я][-a-zа-я ]*$/ui';
			if (!preg_match ($CountryPattern, $ProfileCountryVar)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCountryFormat'];
			}
		}
	}

	//  --9)Домашняя страница
	if (Main_Strlen ($ProfileSiteVar) > 0) {
		if (Main_Strlen ($ProfileSiteVar) < OBB_MIN_HOME_SITE_LENGTH || Main_Strlen ($ProfileSiteVar) > OBB_MAX_HOME_SITE_LENGTH) {
			$WrongSiteLengthError = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongSiteLength'], array('min'=>OBB_MIN_HOME_SITE_LENGTH,'max'=>OBB_MAX_HOME_SITE_LENGTH));
			$ProfileEditError[] = $WrongSiteLengthError;
		}
		else {
			$LowerProfileSite = Main_Strtolower ($ProfileSiteVar);
			$SitePattern = "/^(https?:\/\/)?(([0-9a-z_!~*'().&=+$%-]+:)?[0-9a-z_!~*'().&=+$%-]+@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z_!~*'()-]+\.)*([0-9a-z][0-9a-z-]{0,61})+[0-9a-z]\.[a-z]{2,6})(:[0-9]{1,4})?((\/?)|(\/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+\/?)$/u";
			if (!preg_match ($SitePattern, $LowerProfileSite)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongSiteFormat'];
			}
		}
	}

	//  --10)ICQ
	if (Main_Strlen ($ProfileICQVar) > 0) {
		$ICQPattern = '/^[\d]{9}$/iu';
		if (!preg_match ($ICQPattern, $ProfileICQVar)) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongICQFormat'];
		}
	}

	//  --11)Дом. телефон
	if (Main_Strlen ($ProfilePhoneVar) > 0) {
		$MobilePattern = '/^(?:\+7|8){1}[ ]?[\(][\d]{3}[\)][ ]?[\d]{3}[- ]?[\d]{2}[- ]?[\d]{2}$/ui';
		if (!preg_match ($MobilePattern, $ProfilePhoneVar)) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongPhoneFormat'];
		}
	}

	// --12)Моб. телефон
	if (Main_Strlen ($ProfileMobileVar) > 0) {
		$MobilePattern = '/^(?:\+7|8){1}[ ]?[\(][\d]{3}[\)][ ]?[\d]{3}[- ]?[\d]{2}[- ]?[\d]{2}$/ui';
		if (!preg_match ($MobilePattern, $ProfileMobileVar)) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongMobileFormat'];
		}
	}

	//если ошибок нет
	if (sizeof ($ProfileEditError) < 1) {
		//  --1.действие с аватаром
		if (!isset ($AvatarAction)) {
			$AvatarAction = 'no_action';
		}

		//  --2.Великий запрос редактирования пользователя :)
		$EditProfileSQL = 'UPDATE users
						SET 
							UserSlogan = \'' . Defence_EscapeString ($Config_DBType, $ProfileSloganVar) . '\',
							UserMail = \'' . Defence_EscapeString ($Config_DBType, $ProfileMailVar) . '\',
							' . $PasswordSQL . '
							UserMailHid = \'' . $ProfileMailHiddenVar . '\',
							UserAdminMail = \'' . $ProfileAdminMailVar . '\',
							UserOtherMail = \'' . $ProfileOtherMailVar . '\',
							UserSex = \'' . $ProfileSexVar . '\',
							UserBirthDate = \'' . Defence_EscapeString ($Config_DBType, $ProfileBirthVar) . '\',
							UserSite = \'' . Defence_EscapeString ($Config_DBType, $ProfileSiteVar) . '\',
							UserCity = \'' . Defence_EscapeString ($Config_DBType, $ProfileCityVar) . '\',
							UserCountry = \'' . Defence_EscapeString ($Config_DBType, $ProfileCountryVar) . '\',
							UserICQ = \'' . Defence_EscapeString ($Config_DBType, $ProfileICQVar) . '\',
							UserPhone = \'' . Defence_EscapeString ($Config_DBType, $ProfilePhoneVar) . '\',
							UserMobile = \'' . Defence_EscapeString ($Config_DBType, $ProfileMobileVar) . '\',
							UserAvatar = \'' . Defence_EscapeString ($Config_DBType, $ProfileAvatarVar) . '\'
						WHERE UserID = \'' . $UserID . '\'';
		$EditProfileQuery = DB_Query ($Config_DBType, $EditProfileSQL, $ForumConnection);
		if (!$EditProfileQuery) {
			OBB_Main_Log ($EditProfileSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}

		//  --3.Действия с аватаром
		$AvatarPath = OBB_AVATAR_DIR . '/' . $UserID . '.';
		if ($AvatarAction == 'delete' || $AvatarAction == 'update') {
			//удаление старого файла
			if (file_exists ($AvatarPath . $ProfileAvatar)) {
				unlink ($AvatarPath . $ProfileAvatar);
			}

			//если модифицирование
			if ($AvatarAction == 'update') {
				$TMPName = $AttachArray['Data'];
				$NewAvatarPath = $AvatarPath . $ProfileAvatarVar;
				//проверка и удаление такого же файла
				if (file_exists ($NewAvatarPath)) {
					unlink ($NewAvatarPath);
				}

				//перемещение
				if (!move_uploaded_file ($TMPName, $NewAvatarPath)) {
					OBB_Main_Log ('Error while updating user avatar', OBB_ERROR_LOG_FILE);
				}
			}
		}
		else if ($AvatarAction == 'insert') {
			$TMPName = $AttachArray['Data'];
			$NewAvatarPath = $AvatarPath . $ProfileAvatarVar;
			//проверка и удаление такого же файла
			if (file_exists ($NewAvatarPath)) {
				unlink ($NewAvatarPath);
			}

			//перемещение
			if (!move_uploaded_file ($TMPName, $NewAvatarPath)) {
				OBB_Main_Log ('Error while inserting user avatar', OBB_ERROR_LOG_FILE);
			}
		}

		//  --4.Письмо администратору
		if ($Config_Mail['AdminMail'] == TRUE) {
			$AdminProfLogin = $ProfileLogin;
			$AdminEditerID  = $_SESSION['UserData']['UserID'];
			$AdminProfOwner = $UserID;
			$AdminProfDate  = Main_ConvertDate (time (), '', $Format = 'd.m.y, H:i');
			$AdminTheme     = $ForumLang['UserProfileAdminMailProfile'] . ' "' . $Config_ForumName . '"';
			$AdminName      = $Config_Mail['FromName'];
			$AdminMail      = $Config_Mail['FromMail'];

			$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/AdminMailProfile.html');

			$AdminLetter = str_replace ('{username}', $AdminProfLogin, $AdminLetter);
			$AdminLetter = str_replace ('{userid}', $AdminEditerID, $AdminLetter);
			$AdminLetter = str_replace ('{userdate}', $AdminProfDate, $AdminLetter);
			$AdminLetter = str_replace ('{userownerid}', $AdminProfOwner, $AdminLetter);

			#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
			file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_EditProfLog' . $AdminProfOwner . '_' . microtime () . '.html', $AdminLetter);
		}

		//редирект - если нет ошибок
		$RedirectURL = '?action=profile&user_id=' . $UserID;
		$_SESSION['Message'] = 'edit_profile';
		$_SESSION['Redirect'] = $RedirectURL;
		OBB_Main_Redirect ('?action=message');
	}
}

//Формируем массив главной навигации
$IsYourProfile = $ProfileOwner ? '&nbsp;(' . $ForumLang['UserProfileYourOwnProfile'] . ')' : '';
$NavigProfile = $ForumLang['UserProfileTitle'] . ' \'' . Defence_HTMLSpecials ($ProfileLogin) . '\'' . $IsYourProfile;
$NavigUsers   = $ForumLang['UserProfileUsers'];
$NavigArray = array (
					array ($SelfName, $ForumLang['MainPage']),
					array ($SelfName . '?action=usersview', $NavigUsers),
					array ('', $NavigProfile)
				);

///*JS-массив*///
	if ($CanEditProfile) {
		if (
			$CanEditProfile && 
			$_SESSION['UserData']['UserType'] <> 'admin' && 
			$UserGroups_Permissions['CaptchaEditProfile'] && 
			OBB_CAPTCHA
		) {
			$JSIsCaptcha = TRUE;
		}
		else {
			$JSIsCaptcha = false;
		}
		$JSIsPassword = $ProfileOwner;
		$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'', 'IsCaptcha'=>$JSIsCaptcha, 'IsPassword'=>$JSIsPassword);
	}
	///*JS-массив*///

	$JavaScriptArray = array (
							array ('jquery.js', ''),
							array ('jscal2.js', ''),
							array ($JavaScriptLangArray[$Config_Lang] . '.js', 'lang'),
							array ('functions.js', ''),
							array ('gui.js', '')
						);
	if ($CanEditProfile) {
		$JavaScriptArray[] = array ('parameters', $JSParametersArray);
		$JavaScriptArray[] = array ('UserProfile.js', '');
	}

	//массив BB-редактора
	$Parameters = array ('BBArray'=>array(
									array ('BBType'=>'simple', 'BBSubType'=>'b'),
									array ('BBType'=>'simple', 'BBSubType'=>'i'),
									array ('BBType'=>'simple', 'BBSubType'=>'u'),
									array ('BBType'=>'simple', 'BBSubType'=>'s'),
									array ('BBType'=>'simple', 'BBSubType'=>'url')
								),
						'TextareaName'=>'ProfileSlogan',
						'TextareaID'=>'ProfileSlogan',
						'FormID'=>'ProfileForm',
						'NumberCols'=>'100',
						'NumberRows'=>'15',
						'BorderColor'=>'#000000',
						'SmilesLink'=>$SelfName . '/index.php?action=tooling&label=smile&f_id=ProfileForm&t_id=ProfileSlogan',
						'ImagesPath'=>OBB_IMAGE_DIR . '/bbcodes');

//залоговок страницы
$CurrentTitle = $CanEditProfile ? 'ProfileEdit' : 'ProfileWatch';
$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title'][$CurrentTitle], array('forumname'=>$Config_ForumName,'user'=>$ProfileLogin));

//верх
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title);

//ошибки
if (sizeof ($ProfileEditError)) {
	$ErrorBlock = Echo_DisplayUserErrors ($ProfileEditError, $ForumLang['UserProfileErrors']['ErrorBlockTitle']);
}
else {
	$ErrorBlock = '';
}
$MainOutput .= '<div id="ErrorBlockDiv">' . $ErrorBlock . '</div>';

//обшая информвция
$UserIsOnline = $ProfileIsOnline == 'yes' ? $ForumLang['UserProfileOnline'] : $ForumLang['UserProfileOffline'];
if ($ProfileIsOnline == 'yes') {
	$ActionString = $ForumLang['UserActions'][$ProfileLastAction];
}
else {
	$ActionString = $ForumLang['UserProfileLastVisit'] . ' ' . Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], 'd.m.Y') . ' ' . $ForumLang['UserProfileIn'] . ' ' . Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], 'H:i');
}

//Вспомогательные
$Star         = $CanEditProfile  ? '<font color="red">*</font>' : '';
$DoHid        = $CanEditProfile  ? '<span>' . $ForumLang['UserProfileHidden']   . '</span>' : '';
$IsEmpty      = !$CanEditProfile ? '<i>'    . $ForumLang['UserProfileEmpty']    . '</i>' : '';
$NotAvailable = !$CanEditProfile ? '<i>'    . $ForumLang['UserProfileNotAvail'] . '</i>' : '';

//форма
$MultyPart  = OBB_ALLOW_AVATARS == TRUE && $CanEditProfile == TRUE ? ' enctype="multipart/form-data"' : '';
$FormStart  = $CanEditProfile ? '<form id="ProfileForm" name="ProfileForm" action="' . $SelfName . '?action=profile&user_id=' . $UserID . '" method="POST"' . $MultyPart . '>' : '';
$FormEnd    = $CanEditProfile ? '</form>' : '';
$EditButton = $CanEditProfile ? '<br /><div id="SubmitButtonDiv"><input id="ProfileSubmit" type="submit" value="' . $ForumLang['UserProfileSubmit'] . '"></div>' : '';
$HiddenVar  = $CanEditProfile ? '<input type="hidden" name="EditProfile" value="1">' : '';

//Данные пользователя
//1.Персональная информация
if ($CanEditProfile) {
	//календарь
	$CalendarScript = '
	<script type="text/javascript">//<![CDATA[
	  Calendar.setup({
		inputField : "ProfileBirthDate",
		trigger    : "CalendarIcon",
		onSelect   : function() { this.hide() },
		showTime   : 12,
		dateFormat : "%d.%m.%Y"
	  });
	//]]></script>';

	//иконка календаря
	$CalendarIcon = '<a id="CalendarIcon" href="javascript:void(0);"><img src="' . OBB_IMAGE_DIR . '/calendar.png" border="0" /></a>';

	//  -страна, город, д.р., домашняя страница
	$PersonalCountry = '<input id="ProfileCountry"   type="text" name="ProfileCountry" maxlength="'  . OBB_MAX_COUNTRY_LENGTH   . '" value="' . Defence_HTMLSpecials ($ProfileCountryVar) . '">';
	$PersonalCity    = '<input id="ProfileCity"      type="text" name="ProfileCity"    maxlength="'  . OBB_MAX_CITY_LENGTH      . '" value="' . Defence_HTMLSpecials ($ProfileCityVar)    . '">';
	$PersonalBirth   = '<input id="ProfileBirthDate" type="text" name="ProfileBirth" maxlength="10"' .                            ' value="' . Defence_HTMLSpecials ($ProfileBirthVar)    . '">';
	$PersonalSite    = '<input id="ProfileSite"      type="text" name="ProfileSite" maxlength="'     . OBB_MAX_HOME_SITE_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileSiteVar)    . '">';

	//  -девиз
	$PersonalSlogan  = '';
	$PersonalSlogan .= Echo_BBEditor ($Parameters);
	$PersonalSlogan .= '<textarea id="ProfileSlogan" name="ProfileSlogan" cols="30" rows="3">' . Defence_HTMLSpecials ($ProfileSloganVar) . '</textarea>';

	//  -пол
	$MaleChecked   = $ProfileSexVar == 'male'   ? ' checked' : '';
	$FemaleChecked = $ProfileSexVar == 'female' ? ' checked' : '';
	$PersonalMale   = '<input type="radio"' . $MaleChecked   . ' name="ProfileSex" value="male"  >&nbsp;' . $ForumLang['UserProfileMale'];
	$PersonalFemale	= '<input type="radio"' . $FemaleChecked . ' name="ProfileSex" value="female">&nbsp;' . $ForumLang['UserProfileFemale'];
	$PersonalSex    = '<span>' . $PersonalMale . $PersonalFemale . '</span>';
}
else {
	//календарь
	$CalendarScript = '';

	//  -д.р.
	$PersonalBirth = Defence_HTMLSpecials ($ProfileBirth);

	//иконка календаря
	$CalendarIcon = '';

	//  -страна, город, девиз
	$PersonalCountry = !OBB_Main_IsEmpty ($ProfileCountry) ? Defence_HTMLSpecials ($ProfileCountry) : $IsEmpty;
	$PersonalCity    = !OBB_Main_IsEmpty ($ProfileCity)    ? Defence_HTMLSpecials ($ProfileCity)    : $IsEmpty;
	if (!OBB_Main_IsEmpty ($ProfileSlogan)) {
		if (OBB_BB_PARSE_ALLOWED) {
			$PersonalSlogan = $signature_bb->parse ($ProfileSlogan);
			//$PersonalSlogan = $Slogan;
		}
		else {
			$PersonalSlogan = Defence_HTMLSpecials ($ProfileSlogan);
		}
		$PersonalSlogan = nl2br ($PersonalSlogan);
	}
	else {
		$PersonalSlogan = $IsEmpty;
	}

	//  -домашняя страница
	$UserSite     = Defence_HTMLSpecials ($ProfileSite);
	$PersonalSite = !OBB_Main_IsEmpty ($UserSite) ? '<a target="_blank" href="' . $UserSite . '">' . $UserSite . '</a>' : $IsEmpty;

	//  -пол
	$PersonalSex = $ProfileSex == 'male' ? $ForumLang['UserProfileMale'] : $ForumLang['UserProfileFemale'];
	$PersonalSex = '<span>' . $PersonalSex . '</span>';
}

//2.Данные форума
//  -группа, статус, к-во тем, сообщений, номер пользователя 
$StatisticsGroup  = '<span style="color:#' . $UserGroupArr[$ProfileGroupID]['Color'] . ';">' . $UserGroupArr[$ProfileGroupID]['Description'] . '</span>';
$StatisticsStat   = '<span>' . Echo_GetUserStatus ($ProfileNumPosts, OBB_NUM_POSTS_IN_STATUS, OBB_MAX_STATUS, OBB_IMAGE_DIR) . '</span>';
$StatisticsNum    = '<span>' . $UserID    . '</span>';
if (intval ($ProfileNumThemes) < 1) {
	$ProfileNumThemes = $ForumLang['UserProfileNoThemes'];
	$ShowAllThemes = '';
}
else {
	$ProfileNumThemes = intval ($ProfileNumThemes);
	$ShowAllThemes = '&nbsp;&nbsp;<span><a href="javascript:void(0);" onclick="findUserThemes();">' . $ForumLang['UserProfileAllThemes'] . '</a></span>';
}
if (intval ($ProfileNumPosts) < 1) {
	$ProfileNumPosts = $ForumLang['UserProfileNoPosts'];
	$ShowAllPosts  = '';
}
else {
	$ProfileNumPosts = intval ($ProfileNumPosts);
	$ShowAllPosts  = '<span><a href="javascript:void(0);" onclick="findUserPosts();">' . $ForumLang['UserProfileAllPosts']  . '</a></span>';
}
$StatisticsThemes = '<span>' . $ProfileNumThemes . '</span>';
$StatisticsPosts  = '<span>' . $ProfileNumPosts  . '</span>';

//  -дата регистрации
$UserRegDate = Main_ConvertDate ($ProfileRegDate, $DateArray, 'full');
$UserRegArr  = explode (',', $UserRegDate);
$UserRegDate = $UserRegArr[0] . ', ' . $UserRegArr[1];
$StatisticsReg = '<span>' . $UserRegDate . '</span>';

//3.Контактные данные
if ($CanEditProfile) {
	//  -аська, телефоны
	$ContactsICQ     = '<input id="ProfileICQ"   type="text" name="ProfileICQ" maxlength="9" value="' . Defence_HTMLSpecials ($ProfileICQVar)  . '">';
	$ContactsPhone   = '<input id="ProfilePhone" type="text" name="ProfilePhone"  value="' . Defence_HTMLSpecials ($ProfilePhoneVar)  . '">';
	$ContactsMobile  = '<input id="ProfileMobile" type="text" name="ProfileMobile" value="' . Defence_HTMLSpecials ($ProfileMobileVar) . '">';

	//  -эл. почта
	$HidChecked    = $ProfileMailHiddenVar == 'yes' ? ' checked' : '';
	$ContactIsHid  = '<input' . $HidChecked . ' type="checkbox" name="ProfileHidMail">';
	$ContactsEMail = '<input id="ProfileMail" type="text" name="ProfileMail" maxlength="' . OBB_MAX_MAIL_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileMailVar) . '">';

	//  -отсыл письма админом
	$AdmMailCheck    = $ProfileAdminMailVar == 'yes' ? ' checked' : '';
	$TextAdmMail     = $ForumLang['UserProfileAdmMail'];
	$ContactsAdmMail = '<input' . $AdmMailCheck . ' type="checkbox" name="ProfileAdmMail">';

	//  -отсыл письма пользователями
	$OthMailCheck    = $ProfileOtherMailVar == 'yes' ? ' checked' : '';
	$TextOthMail     = $ForumLang['UserProfileOthMail'];
	$ContactsOthMail = '<input' . $OthMailCheck . ' type="checkbox" name="ProfileOthMail">';

	//  -пароль
	//  -ПРИМЕЧАНИЕ: пароль может изменять ТОЛЬКО ВЛАДЕЛЕЦ профиля
	if ($ProfileOwner) {
		$PasswordDiv = '<br />
						<div>
							<span>' . $ForumLang['UserProfilePassword'] . '</span>
							<br />
							<span><input id="ProfilePass" type="password" name="ProfilePassword"   maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" /></span>
							<br />
							<span>' . $ForumLang['UserProfilePassRepeat'] . '</span>
							<br />
							<span><input id="ProfileRepeatPass" type="password" name="ProfilePassRepeat" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" /></span>
						</div>';
	}
	else {
		$PasswordDiv = '';
	}

	//  -обобщение
	$ContactsComMail = '<br />
						<div>
							<span>' . $ForumLang['UserProfileMailOpts'] . '</span>
							<br />
							<span>' . $ContactsAdmMail . '</span>&nbsp;<span>' . $TextAdmMail . '</span>
							<br />
							<span>' . $ContactsOthMail . '</span>&nbsp;<span>' . $TextOthMail . '</span>
						</div>';
}
else {
	//  -аська, телефоны
	$ContactsICQ     = !OBB_Main_IsEmpty ($ProfileICQ)    ? Defence_HTMLSpecials ($ProfileICQ)     : $IsEmpty;
	$ContactsPhone   = !OBB_Main_IsEmpty ($ProfilePhone)  ? Defence_HTMLSpecials ($ProfilePhone)   : $IsEmpty;
	$ContactsMobile  = !OBB_Main_IsEmpty ($ProfileMobile) ? Defence_HTMLSpecials ($ProfileMobile)  : $IsEmpty;

	//  -эл. почта
	$HidChecked      = '';
	$ContactIsHid    = '';
	if ($ProfileMailHidden == 'yes') {
		$ContactsEMail = $NotAvailable;
	}
	else {
		$ContactsEMail = !OBB_Main_IsEmpty ($ProfileMail) ? Defence_HTMLSpecials ($ProfileMail) : $IsEmpty;
	}

	//  -отсыл эл. писем админом и пользователем
	$TextAdmMail     = '';
	$TextOthMail     = '';
	$ContactsAdmMail = '';
	$ContactsOthMail = '';
	$ContactsComMail = '';

	//  -пароль
	$PasswordDiv = '';
}

//4.Аватар
if (!OBB_Main_IsEmpty ($ProfileAvatar) && file_exists (OBB_AVATAR_DIR . '/' . $UserID . '.' . $ProfileAvatar)) {
	if ($CanEditProfile && $AvatarFlag) {
		$AvatarDoNot = '<span><input type="checkbox" name="ProfileNoUseAvatar"></span>&nbsp;<span>' . $ForumLang['UserProfileAvatarDel'] . '</span>';
	}
	else {
		$AvatarDoNot = '';
	}
	$AvatarImage = '<span><img src="' . OBB_AVATAR_DIR . '/' . $UserID . '.' . $ProfileAvatar . '" border="0" /></span>';
}
else {
	$AvatarDoNot = '';
	$NoAvatarPhrase = $ProfileOwner ? $ForumLang['UserProfileOwnerNoAvatar'] : $ForumLang['UserProfileNoAvatar'];
	$AvatarImage = '<span>' . $NoAvatarPhrase . '</span>';
}

//  -форма загрузки нового аватара
if ($CanEditProfile && $AvatarFlag) {
	$AvatarLoadForm = '<span>
						' . $ForumLang['UserProfileAvatarNew'] . '
					</span>
					<br />
					<span>
						<input type="file" name="ProfileAvatar" />
					</span>';
}
else {
	$AvatarLoadForm = '';
}

//  -объединение
$AvatarAll = '<br />
			<div class="Avatar" id="Avatar">
				<div>
					<b>' . $ForumLang['UserProfileAvatar'] . '</b>
				</div>
				<div>
					<div>
						' . $AvatarImage . '
						&nbsp;
						' . $AvatarDoNot . '
					</div>
					<br />
					<div>
						' . $AvatarLoadForm . '
					<div>
				</div>
			</div>';


if (!OBB_ALLOW_AVATARS) {
	$AvatarAll = '';
}

//админские элементы
if ($_SESSION['UserData']['UserType'] == 'admin' && OBB_SHOW_ADMIN_ELEMENTS && !OBB_Main_IsAdminGroup ($ProfileGroupID)) {
	$AdminElements = '&nbsp;&nbsp;
					<a title="' . $ForumLang['UserProfileDeleteUser'] . '" href="index.php?action=admin&adm=user&uact=del&uid=' . $UserID . '">
						<img src="' . OBB_IMAGE_DIR . '/admin_delete.png" border="0" />
					</a>
					&nbsp;
					<a title="' . $ForumLang['UserProfileEditUser'] . '" href="index.php?action=admin&adm=user&uact=edit&uid=' . $UserID . '">
						<img src="' . OBB_IMAGE_DIR . '/admin_edit.png" border="0" />
					</a>';
}
else {
	$AdminElements = '';
}

//Начало вывода данных
$MainOutput .= '<tr>
					<td>
						<div id="SearchHiddenForm" name="SearchHiddenForm" style="display:none;">
							<form id="UserSearchFormID" name="UserSearchFormID" action="' . $SelfName . '?action=search&search_build=1" method="POST">
								<input type="hidden" name="SearchWord" value="" />
									<input type="hidden" name="SearchMethod" value="1" />
									<input type="hidden" name="SearchUser" value="' . $ProfileLogin . '" />
									<input checked type="checkbox" name="SearchFullUser" />
									<input type="hidden" name="SearchSortBy" value="1" />
									<input type="hidden" name="SearchSortHow" value="2" />
									<input id="ProfileSearchMethodIn" type="hidden" name="SearchMethodIn" value="" />
									<input type="hidden" name="search" value="1" />
									<input type="checkbox" name="SearchHighlight" />
							</form>
						</div>
						<div>
							<div>
								<b>' . Defence_HTMLSpecials ($ProfileLogin) . '</b>
								' . $AdminElements . '
							</div>
							<div>
								<span>' . $UserIsOnline . '</span>
								<br />
								<span>' . $ActionString . '</span>
							</div>
						</div>';

$MainOutput .= $FormStart;

//Персональные данные
$MainOutput .= '		<br />
						<div class="Personals" id="Personals">
							<div>
								<b>' . $ForumLang['UserProfilePersonals'] . '</b>
							</div>
							' . $PasswordDiv . '
							<br />
							<div>
								<span>' . $ForumLang['UserProfileBirth'] . '</span>' . $Star . '
								<br />
								<span>' . $PersonalBirth . '</span>' . $CalendarIcon . '
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileSex'] . '</span>' . $Star . '
								<br />
								<span>' . $PersonalSex . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileWWW'] . '</span>
								<br />
								<span>' . $PersonalSite . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileSlogan'] . '</span>
								<br />
								<span>' . $PersonalSlogan . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileCountry'] . '</span>
								<br />
								<span>' . $PersonalCountry . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileCity'] . '</span>
								<br />
								<span>' . $PersonalCity . '</span>
							</div>
						</div>';

//Данные форума
$MainOutput .= '		<br />
						<div class="ForumStatistics" id="ForumStatistics">
							<div>
								<b>' . $ForumLang['UserProfileStatistics'] . '</b>
							</div>
							<div>
								<span>' . $ForumLang['UserProfileGroup'] . '</span>
								<br />
								<span>' . $StatisticsGroup . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileStatus'] . '</span>
								<br />
								<span>' . $StatisticsStat . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileRegDate'] . '</span>
								<br />
								<span>' . $StatisticsReg . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileNumber'] . '</span>
								<br />
								<span>' . $StatisticsNum . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileThemes'] . '</span>
								<br />
								<span>' . $StatisticsThemes . '</span>&nbsp;' . $ShowAllThemes . '
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfilePosts'] . '</span>
								<br />
								<span>' . $StatisticsPosts . '</span>&nbsp;' . $ShowAllPosts . '
							</div>
						</div>';

//Контактная информация
$MainOutput .= '		<br />
						<div class="Contacts" id="Contacts">
							<div>
								<b>' . $ForumLang['UserProfileContacts'] . '</b>
							</div>
							<div>
								<span>' . $ForumLang['UserProfileMail'] . $Star . '</span>
								<br />
								<span>' . $ContactsEMail . '</span>' . $ContactIsHid . '&nbsp;' . $DoHid . '
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileICQ'] . '</span>
								<br />
								<span>' . $ContactsICQ . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfilePhone'] . '</span>
								<br />
								<span>' . $ContactsPhone . '</span>
							</div>
							<br />
							<div>
								<span>' . $ForumLang['UserProfileMobile'] . '</span>
								<br />
								<span>' . $ContactsMobile . '</span>
							</div>
						</div>';

//Аватар
$MainOutput .= $AvatarAll;

//Опции получения писем
$MainOutput .= $ContactsComMail;

//Капча
if (
	$CanEditProfile && 
	$_SESSION['UserData']['UserType'] <> 'admin' && 
	$UserGroups_Permissions['CaptchaEditProfile'] && 
	OBB_CAPTCHA
) {
	$MainOutput .= Echo_CaptchaBlock ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'ProfileCaptcha');
}

$MainOutput .= $HiddenVar;
$MainOutput .= $EditButton;
$MainOutput .= $FormEnd;

$MainOutput .= $CalendarScript;

//конец вывода данных
$MainOutput .= '	</td>
				</tr>';

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

$b = microtime ();
//echo ($b-$a);

?>