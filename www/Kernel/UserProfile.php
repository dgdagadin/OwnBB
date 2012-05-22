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
//  --если вошедший админ
//  --если разрешены аватары на форуме
$AvatarFlag = false;
if ($CanEditProfile && OBB_ALLOW_AVATARS) {
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
	if ($CanEditProfile && $_SESSION['UserData']['UserType'] <> 'admin' && $UserGroups_Permissions['CaptchaEditProfile'] && OBB_CAPTCHA) {
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
$IsYourProfile = $ProfileOwner ? ' (' . $ForumLang['UserProfileYourOwnProfile'] . ')' : '';
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
$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'AddDelimiterDiv');

//вывод ошибок
if (sizeof ($ProfileEditError) > 0) {
	$ErrorListBlock = '<div class="ErrorBlockDiv">
							<div class="ErrorBlockTitle">' . $ForumLang['UserProfileErrors']['ErrorBlockTitle'] . ':</div>';
	foreach ($ProfileEditError as $Key=>$Value) {
		$ErrorListBlock .= '<div class="ErrorItemDiv">' . $Value . '</div>';
	}
	$ErrorListBlock .= '</div>
						<div style="height:15px;"><!-- --></div>';
}
else {
	$ErrorListBlock = '';
}
$MainOutput .= $ErrorListBlock;

//обшая информвция
$UserIsOnline = $ProfileIsOnline == 'yes' ? $ForumLang['UserProfileOnline'] : $ForumLang['UserProfileOffline'];
if ($ProfileIsOnline == 'yes') {
	$ActionString = $ForumLang['UserActions'][$ProfileLastAction];
}
else {
	$ActionString = $ForumLang['UserProfileLastVisit'] . ' ' . Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], 'd.m.Y') . ' ' . $ForumLang['UserProfileIn'] . ' ' . Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], 'H:i');
}

//Данные форума
//  -группа, статус, к-во тем, сообщений, номер пользователя
$StatisticsGroup  = '<span style="color:#' . $UserGroupArr[$ProfileGroupID]['Color'] . ';">' . $UserGroupArr[$ProfileGroupID]['Description'] . '</span>';
$IsUserAdminGroup = OBB_Main_IsAdminGroup ($ProfileGroupID);
$UserStatusPosts = $IsUserAdminGroup ? OBB_MAX_STATUS*OBB_NUM_POSTS_IN_STATUS : $ProfileNumPosts;
$StatisticsStat   = Echo_GetUserStatus ($UserStatusPosts, OBB_NUM_POSTS_IN_STATUS, OBB_MAX_STATUS, OBB_IMAGE_DIR);
$StatisticsNum    = '<span>' . $UserID . '</span>';
if (intval ($ProfileNumThemes) < 1) {
	$ProfileNumThemes = $ForumLang['UserProfileNoThemes'];
	$ShowAllThemes = '';
}
else {
	$ProfileNumThemes = intval ($ProfileNumThemes);
	$ShowAllThemes = '&nbsp;&nbsp;<span><a style="color:blue;" href="javascript:void(0);" onclick="findUserThemes();">' . $ForumLang['UserProfileAllThemes'] . '</a></span>';
}
if (intval ($ProfileNumPosts) < 1) {
	$ProfileNumPosts = $ForumLang['UserProfileNoPosts'];
	$ShowAllPosts  = '';
}
else {
	$ProfileNumPosts = intval ($ProfileNumPosts);
	$ShowAllPosts  = '&nbsp;&nbsp;<span><a style="color:blue;" href="javascript:void(0);" onclick="findUserPosts();">' . $ForumLang['UserProfileAllPosts']  . '</a></span>';
}
$StatisticsThemes = '<span>' . $ProfileNumThemes . '</span>';
$StatisticsPosts  = '<span>' . $ProfileNumPosts  . '</span>';

//дата регистрации
$UserRegDate = Main_ConvertDate ($ProfileRegDate, $DateArray, 'full');
$UserRegArr  = explode (',', $UserRegDate);
$UserRegDate = $UserRegArr[0] . ', ' . $UserRegArr[1];
$StatisticsReg = '<span>' . $UserRegDate . '</span>';

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

//hidden form
$HiddenActionURL = Defence_HTMLSpecials ($SelfName . '?action=search&search_build=1');
$HiddenForm = '<div id="SearchHiddenForm" style="display:none;">
					<form id="UserSearchFormID" action="' . $HiddenActionURL . '" method="post">
						<div>
							<input type="hidden" name="SearchWord" value="" />
							<input type="hidden" name="SearchMethod" value="1" />
							<input type="hidden" name="SearchUser" value="' . $ProfileLogin . '" />
							<input checked="checked" type="checkbox" name="SearchFullUser" />
							<input type="hidden" name="SearchSortBy" value="1" />
							<input type="hidden" name="SearchSortHow" value="2" />
							<input id="ProfileSearchMethodIn1" type="hidden" name="SearchMethodIn" value="" />
							<input type="hidden" name="search" value="1" />
							<input type="checkbox" name="SearchHighlight" />
						</div>
					</form>
				</div>';

//common user information
//  --online/offline
if ($ProfileIsOnline == 'yes') {
	$Image = 'online';
	$OnlineStatus = $ForumLang['UserProfileOnline'];
	$ActionArray  = $ForumLang['UserActions'];
	$ActionString = '<span class="OnlineActivityOn">' . $ActionArray[$ProfileLastAction] . '</span>';
}
else {
	$Image = 'offline';
	$OnlineStatus = $ForumLang['UserProfileOffline'];
	$DatePattern = 'd.m.Y';
	$ConvertDate = Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], $DatePattern);
	$ConvertDate = $ConvertDate;
	$TimePattern = 'H:i';
	$ConvertTime = Main_ConvertDate ($ProfileLastLogin, $ForumLang['DateArray'], $TimePattern);
	$ConvertTime = $ConvertTime;
	$DateTimeLogin = $ConvertDate . ' ' . $ForumLang['UserProfileIn'] . ' ' . $ConvertTime;
	$ActionString = '<span class="OnlineActivityOff">' . $ForumLang['UserProfileLastAction'] . ' - <span class="OnlineActivityTime">' . $DateTimeLogin . '</span></span>';
}
$UserActivity  = '<img style="vertical-align:middle;" class="OnlineImg" title="' . $OnlineStatus . '" alt="" src="' . OBB_IMAGE_DIR . '/' . $Image . '.png" />';
$UserActivity .= '&nbsp;<span style="font-weight:normal !important;" class="OnlineActivity">' . $ActionString . '</span>';

if ($CanEditProfile) {
	$CommonBlockDisplay = ' style="display:none;"';
	$CommonBlockIcon    = 'expand';
}
else {
	$CommonBlockDisplay = '';
	$CommonBlockIcon    = 'collapse';
}

//  --output
$CommonUserInfo = '<tr>
						<td style="width:200px;" class="ForumCharter">
							<span class="ThemesTitle">' . $ForumLang['UserProfileStatistics'] . '</span>
						</td>
						<td style=" text-align:right; vertical-align:middle;" class="ForumCharter">
							<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfileCommonClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfileCommonImgID\');"><img id="ProfileCommonImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/' . $CommonBlockIcon . '.gif" /></a>
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; border-top:1px solid #FFFFFF; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileLogin'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top; border-top:1px solid #FFFFFF;" class="FormInputTD">
							' . Defence_HTMLSpecials ($ProfileLogin) . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:12px; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileGroup'] . '
							</div>
						</td>
						<td style="padding-bottom:13px; vertical-align:top;" class="FormInputTD">
							' . $StatisticsGroup . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileRegDate'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top;" class="FormInputTD">
							' . $StatisticsReg . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileNumber'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top;" class="FormInputTD">
							' . $StatisticsNum . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileThemes'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top;" class="FormInputTD">
							<span>' . $StatisticsThemes . '</span>&nbsp;' . $ShowAllThemes . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; vertical-align:top; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfilePosts'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top;" class="FormInputTD">
							<span>' . $StatisticsPosts . '</span>&nbsp;' . $ShowAllPosts . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:14px; vertical-align:middle; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileStatus'] . '
							</div>
						</td>
						<td style="padding-bottom:15px; vertical-align:top;" class="FormInputTD">
							' . $StatisticsStat . '
						</td>
					</tr>
					<tr class="ProfileCommonClassHide"' . $CommonBlockDisplay . '>
						<td style="padding-top:6px; padding-bottom:16px; vertical-align:middle; width:200px;" class="FormTitleTD">
							<div class="InputTitle">
								' . $ForumLang['UserProfileActivity'] . '
							</div>
						</td>
						<td style="padding-bottom:16px; vertical-align:top;" class="FormInputTD">
							' . $UserActivity . '
						</td>
					</tr>';

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

	//multipart
	$MultyPart  = OBB_ALLOW_AVATARS == TRUE && $CanEditProfile == TRUE ? ' enctype="multipart/form-data"' : '';

	//checkings
	$HidChecked    = $ProfileMailHiddenVar == 'yes' ? ' checked="checked"' : '';
	$AdmMailCheck  = $ProfileAdminMailVar  == 'yes' ? ' checked="checked"' : '';
	$OthMailCheck  = $ProfileOtherMailVar  == 'yes' ? ' checked="checked"' : '';
	$MaleChecked   = $ProfileSexVar == 'male'   ? ' checked="checked"' : '';
	$FemaleChecked = $ProfileSexVar == 'female' ? ' checked="checked"' : '';

	//Password
	if ($ProfileOwner) {
		$TopBorder = '';
		$PasswordBlock = '<tr class="ProfilePersonalsClassHide">
							<td style="border-top:1px solid #FFF; padding-bottom:7px; width:200px;" class="FormTitleTD">
								<div class="InputTitle">
									' . $ForumLang['UserProfilePassword'] . '<span class="Important">*</span>
								</div>
								<div class="InputDescr">
									(' . OBB_Main_ReplaceSymbols ($ForumLang['UserPasswordLimit'], array('min'=>OBB_MIN_PASSWORD_LENGTH, 'max'=>OBB_MAX_PASSWORD_LENGTH)) . ')
								</div>
							</td>
							<td style="border-top:1px solid #FFF; padding-bottom:7px;" class="FormInputTD">
								<input style="width:280px;" class="InpEl InpText" id="ProfilePass" type="password" name="ProfilePassword" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" />
							</td>
						</tr>
						<tr class="ProfilePersonalsClassHide">
							<td style="padding-bottom:7px; width:200px;" class="FormTitleTD">
								<div class="InputTitle">
									' . $ForumLang['UserProfilePassRepeat'] . '<span class="Important">*</span>
								</div>
							</td>
							<td style="padding-bottom:7px;" class="FormInputTD">
								<input style="width:280px;" class="InpEl InpText" id="ProfileRepeatPass" type="password" name="ProfilePassRepeat" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" />
							</td>
						</tr>';
	}
	else {
		$TopBorder = 'border-top:1px solid #FFF; ';
		$PasswordBlock = '';
	}

	//Avatar
	if ($AvatarFlag) {
		//расширения
		$ExtenArray = array ();
		foreach ($FilesArray as $FilesKey=>$FilesValue) {
			$Extension = $FilesValue['extension'];
			$IsImage   = $FilesValue['image'];
			if ($IsImage) {
				$ExtenArray[] = $Extension;
			}
		}
		$ExstensionString = '<span style="border-bottom:1px dotted #222;">' . $ForumLang['UserProfileAvatarsAllowed'] . '</span>:&nbsp;<span style="color:#444;">' . implode (', ', $ExtenArray) . '</span>';

		if (!OBB_Main_IsEmpty ($ProfileAvatar) && file_exists (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar)) {
			$ProfileAvatarSize = filesize (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar);
			$SizeArr   = Main_ShowSize ($ProfileAvatarSize);
			$FileUnit  = $SizeArr['Unit'];
			$FileSize  = $SizeArr['Size'];
			$UnitLoc = $ForumLang['UserProfileAttach'.$FileUnit];
			$AvatarSizeString = $FileSize . '&nbsp;' . $UnitLoc;

			$ProfileAvatarGabaritsArray = getimagesize (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar);
			$ProfileAvatarWidth  = $ProfileAvatarGabaritsArray[0];
			$ProfileAvatarHeight = $ProfileAvatarGabaritsArray[1];
			$AvatarGabaritsString = '&nbsp;' . $ProfileAvatarWidth . 'x' . $ProfileAvatarHeight;

			$CurrentAvatarBlock = '<div class="ExistingAttachDiv">
										<div style="margin-bottom:10px;" class="FileAttachTitle">
											' . $ForumLang['UserProfileCurrentAvatar'] . '
										</div>
										<div>
											<img alt="" title="" src="' . OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar . '" />
										</div>
										<div style="margin-top:5px; margin-bottom:19px;">
											<span class="AttachInfo">' . $AvatarSizeString . ',&nbsp;' . $AvatarGabaritsString . '</span><span style="margin-left:17px;"><input id="Label_ProfileNoUseAvatar" type="checkbox" name="ProfileNoUseAvatar" /></span><label for="Label_ProfileNoUseAvatar">' . $ForumLang['UserProfileAvatarDel'] . '</label>
										</div>
									</div>';
		}
		else {
			$CurrentAvatarBlock = '';
		}

		$AvatarFullBlock = '<tr>
								<td style="width:200px;" class="ForumCharter">
									<span class="ThemesTitle">' . $ForumLang['UserProfileAvatarTitle'] . '</span>
								</td>
								<td style=" text-align:right; vertical-align:middle;" class="ForumCharter">
									<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfileAvatarClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfileAvatarImgID\');"><img id="ProfileAvatarImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
								</td>
							</tr>
							<tr class="ProfileAvatarClassHide">
								<td style="border-top:1px solid #FFF; padding-bottom:7px; width:200px;" class="FormTitleTD">
									<div class="InputTitle">
										' . $ForumLang['UserProfileAvatar'] . '
									</div>
								</td>
								<td style="border-top:1px solid #FFF; padding-bottom:7px;" class="FormInputTD">
									<div style="margin-bottom:10px; border:none; background:#DFE6EF;" class="MainBlockAttach">
										' . $CurrentAvatarBlock . '
										<div class="AttachAddAction">
											<strong>' . $ForumLang['UserProfileAvatarNew'] . '</strong>
										</div>
										<div class="AttachFileField">
											<input type="file" name="ProfileAvatar" />
										</div>
										<div class="AttachExtensions">
											' . $ExstensionString . '
										</div>
										<div class="AttachExtensions">
											<span style="border-bottom:1px dotted #222;">' . $ForumLang['UserProfileAvatarAllowedSize'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_AVATAR_SIZE . '&nbsp;' . $ForumLang['UserProfileAttachb'] . '</span>
										</div>
										<div class="AttachExtensions">
											<span style="border-bottom:1px dotted #222;">' . $ForumLang['UserProfileAvatarAllowedGabarits'] . '</span>:&nbsp;<span style="color:#444;">' . OBB_MAX_AVATAR_WIDTH . '(' . $ForumLang['UserProfileAvatarhWidth'] . ')&nbsp;<strong>X</strong>&nbsp;' . OBB_MAX_AVATAR_WIDTH . '(' . $ForumLang['UserProfileAvatarhHeight'] . ')</span>
										</div>
									</div>
								</td>
							</tr>';
	}
	else {
		$AvatarFullBlock = '';
	}

	//Капча
	if ($_SESSION['UserData']['UserType'] <> 'admin' && $UserGroups_Permissions['CaptchaEditProfile'] && OBB_CAPTCHA) {
		$CaptchaBlock  = '<tr>
							<td style="border-bottom:1px solid #FFF; width:200px;" class="ForumCharter">
								<span class="ThemesTitle">' . $ForumLang['UserProfileCaptchaTitle'] . '</span>
							</td>
							<td style="border-bottom:1px solid #FFF; text-align:right; vertical-align:middle;" class="ForumCharter">
								&nbsp;
							</td>
						</tr>';
		$CaptchaBlock .= Echo_CaptchaBlock2 ($ForumLang['CaptchaTitle'], $ForumLang['EnterCaptcha'], 'ProfileCaptcha');
	}
	else {
		$CaptchaBlock = '';
	}

	//Action
	$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $UserID);

	//Start outputting
	$MainOutput .= $HiddenForm;
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td style="font-size:13px;" colspan="1" class="MainColumnName"><span>' . $NavigProfile . '</span></td>
						</tr>
						<tr class="ForumMainTR">
							<td style="padding:0;">
								<form style="padding:0; margin:0;" id="ProfileForm" action="' . $ActionURL . '" method="post"' . $MultyPart . '>
									<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
										' . $CommonUserInfo . '
										<tr>
											<td style="width:200px;" class="ForumCharter">
												<span class="ThemesTitle">' . $ForumLang['UserProfilePersonals'] . '</span>
											</td>
											<td style="text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfilePersonalsClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfilePersonalsImgID\');"><img id="ProfilePersonalsImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
											</td>
										</tr>
										' . $PasswordBlock . '
										<tr class="ProfilePersonalsClassHide">
											<td style="' . $TopBorder . 'padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileBirth'] . '<span class="Important">*</span>
												</div>
												<div class="InputDescr">
													(' . $ForumLang['UserProfileBirthLimit'] . ')
												</div>
											</td>
											<td style="' . $TopBorder . 'padding-bottom:7px;" class="FormInputTD">
												<input style="width:130px;" class="InpEl InpText" id="ProfileBirthDate" type="text" name="ProfileBirth" maxlength="10" value="' . Defence_HTMLSpecials ($ProfileBirthVar) . '" />
												<a id="CalendarIcon" href="javascript:void(0);"><img style="vertical-align:middle;" title="" alt="" src="' . OBB_IMAGE_DIR . '/calendar.png" /></a>
											</td>
										</tr>
										<tr class="ProfilePersonalsClassHide">
											<td style="padding-top:9px; padding-bottom:12px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileSex'] . '<span class="Important">*</span>
												</div>
											</td>
											<td style="padding-bottom:12px;" class="FormInputTD">
												<input id="Label_ProfileSexMale" type="radio"' . $MaleChecked   . ' name="ProfileSex" value="male" />&nbsp;<label for="Label_ProfileSexMale">' . $ForumLang['UserProfileMale'] . '</label>
												&nbsp;
												<input id="Label_ProfileSexFemale" type="radio"' . $FemaleChecked . ' name="ProfileSex" value="female" />&nbsp;<label for="Label_ProfileSexFemale">' . $ForumLang['UserProfileFemale'] . '</label>
											</td>
										</tr>
										<tr class="ProfilePersonalsClassHide">
											<td style="padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileWWW'] . '<span class="Important">*</span>
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['UserProfileWWWLimit'], array('min'=>OBB_MIN_HOME_SITE_LENGTH,'max'=>OBB_MAX_HOME_SITE_LENGTH)) . ')
												</div>
											</td>
											<td style="padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileSite" type="text" name="ProfileSite" maxlength="' . OBB_MAX_HOME_SITE_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileSiteVar) . '" />
											</td>
										</tr>
										<tr class="ProfilePersonalsClassHide">
											<td style="width:200px; vertical-align:top;" class="FormTitleTD">
												<br /><br />
												<div class="InputTitle">
													' . $ForumLang['UserProfileSlogan'] . '
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['UserProfileSloganLimit'], array('max'=>OBB_MAX_SLOGAN_LENGTH)) . ')
												</div>
											</td>
											<td class="FormInputTD TextareaTD">
												' . Echo_BBEditor ($Parameters) . '
												<div>
													<textarea style="width:400px;" class="InpEl InpTextarea" id="ProfileSlogan" name="ProfileSlogan" onfocus="initInsertions(\'ProfileForm\', \'ProfileSlogan\');" onkeyup="storeCaret(this);" onclick="storeCaret(this);" onselect="storeCaret(this);" cols="87" rows="5">' . Defence_HTMLSpecials ($ProfileSloganVar) . '</textarea>
												</div>
												<div style="height:15px;"><!-- --></div>
											</td>
										</tr>
										<tr class="ProfilePersonalsClassHide">
											<td style="padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileCountry'] . '
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['UserProfileCountryLimit'], array('min'=>OBB_MIN_COUNTRY_LENGTH,'max'=>OBB_MAX_COUNTRY_LENGTH)) . ')
												</div>
											</td>
											<td style="padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileCountry" type="text" name="ProfileCountry" maxlength="' . OBB_MAX_COUNTRY_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileCountryVar) . '" />
											</td>
										</tr>
										<tr class="ProfilePersonalsClassHide">
											<td style="padding-bottom:15px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileCity'] . '
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['UserProfileCityLimit'], array('min'=>OBB_MIN_CITY_LENGTH,'max'=>OBB_MAX_CITY_LENGTH)) . ')
												</div>
											</td>
											<td style="padding-bottom:15px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileCity" type="text" name="ProfileCity" maxlength="' . OBB_MAX_CITY_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileCityVar) . '" />
											</td>
										</tr>
										<tr>
											<td style="width:200px;" class="ForumCharter">
												<span class="ThemesTitle">' . $ForumLang['UserProfileContacts'] . '</span>
											</td>
											<td style=" text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfileContactsClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfileContactsImgID\');"><img id="ProfileContactsImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="border-top:1px solid #FFFFFF; padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileMail'] . '<span class="Important">*</span>
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['UserProfileMailLimit'], array('min'=>OBB_MIN_MAIL_LENGTH,'max'=>OBB_MAX_MAIL_LENGTH)) . ')
												</div>
											</td>
											<td style="border-top:1px solid #FFFFFF; padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileMail" type="text" name="ProfileMail" maxlength="' . OBB_MAX_MAIL_LENGTH . '" value="' . Defence_HTMLSpecials ($ProfileMailVar) . '" />
												&nbsp;&nbsp;
												<input id="Label_ProfileHidMail"' . $HidChecked . ' type="checkbox" name="ProfileHidMail" /><label for="Label_ProfileHidMail">' . $ForumLang['UserProfileHidden'] . '</label>
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileICQ'] . '
												</div>
											</td>
											<td style="padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileICQ" type="text" name="ProfileICQ" maxlength="9" value="' . Defence_HTMLSpecials ($ProfileICQVar) . '" />
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-top:12px; padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfilePhone'] . '
												</div>
											</td>
											<td style="padding-top:10px; padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfilePhone" type="text" name="ProfilePhone"  value="' . Defence_HTMLSpecials ($ProfilePhoneVar) . '" />
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-top:12px; padding-bottom:7px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileMobile'] . '
												</div>
											</td>
											<td style="padding-top:10px; padding-bottom:7px;" class="FormInputTD">
												<input style="width:280px;" class="InpEl InpText" id="ProfileMobile" type="text" name="ProfileMobile" value="' . Defence_HTMLSpecials ($ProfileMobileVar) . '" />
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-top:10px; padding-bottom:14px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileMailOpts'] . '
												</div>
											</td>
											<td style="padding-top:10px; padding-bottom:14px;" class="FormInputTD">
												<input id="Label_ProfileAdmMail"' . $AdmMailCheck . ' type="checkbox" name="ProfileAdmMail" /><label for="Label_ProfileAdmMail">'  . $ForumLang['UserProfileAdmMail'] . '</label><br />
												<input id="Label_ProfileOthMail"' . $OthMailCheck . ' type="checkbox" name="ProfileOthMail" /><label for="Label_ProfileOthMail">'  . $ForumLang['UserProfileOthMail'] . '</label><br />
											</td>
										</tr>
										' . $AvatarFullBlock . '
										' . $CaptchaBlock    . '
										<tr>
											<td style="border-top:1px solid #FFFFFF; border-top:1px solid #FFFFFF;" colspan="2" class="FormInputTD AdditionalTD">
												<span class="Important">*</span> - ' . $ForumLang['UserProfileImportantFields'] . '
											</td>
										</tr>
										<tr>
											<td colspan="2" class="FormInputTD CenterTD">
												<input type="hidden" name="EditProfile" value="1" />
												<div id="SubmitButtonDiv">
													<input class="InpButton" id="ProfileSubmit" type="submit" value="' . $ForumLang['UserProfileSubmit'] . '" />
													<input class="InpButton" type="button" value="' . $ForumLang['UserProfileReset'] . '" />
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
					</table>' . $CalendarScript;
}
else {
	//Вспомогательные
	$IsEmpty      = '<span style="font-style:italic; color:#444444;">' . $ForumLang['UserProfileEmpty'] . '</span>';
	$NotAvailable = '<span style="font-style:italic; color:#AA0000;">' . $ForumLang['UserProfileNotAvail'] . '</span>';

	//birth date
	$PersonalBirth = Defence_HTMLSpecials ($ProfileBirth);

	//пол
	$PersonalSex = $ProfileSex == 'male' ? $ForumLang['UserProfileMale'] : $ForumLang['UserProfileFemale'];
	$PersonalSex = '<span>' . $PersonalSex . '</span>';

	//домашняя страница
	$UserSite     = Defence_HTMLSpecials ($ProfileSite);
	$PersonalSite = !OBB_Main_IsEmpty ($UserSite) ? '<a style="color:blue;" href="' . $UserSite . '">' . $UserSite . '</a>' : $IsEmpty;

	//страна, город, девиз
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

	//электронная почта
	if ($ProfileMailHidden == 'yes') {
		$ContactsEMail = $NotAvailable;
	}
	else {
		$ContactsEMail = !OBB_Main_IsEmpty ($ProfileMail) ? Defence_HTMLSpecials ($ProfileMail) : $IsEmpty;
	}

	//аська, телефоны
	$ContactsICQ     = !OBB_Main_IsEmpty ($ProfileICQ)    ? Defence_HTMLSpecials ($ProfileICQ)     : $IsEmpty;
	$ContactsPhone   = !OBB_Main_IsEmpty ($ProfilePhone)  ? Defence_HTMLSpecials ($ProfilePhone)   : $IsEmpty;
	$ContactsMobile  = !OBB_Main_IsEmpty ($ProfileMobile) ? Defence_HTMLSpecials ($ProfileMobile)  : $IsEmpty;

	//аватар
	if (OBB_ALLOW_AVATARS) {
		if (!OBB_Main_IsEmpty ($ProfileAvatar) && file_exists (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar)) {
			$ProfileAvatarSize = filesize (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar);
			$SizeArr   = Main_ShowSize ($ProfileAvatarSize);
			$FileUnit  = $SizeArr['Unit'];
			$FileSize  = $SizeArr['Size'];
			$UnitLoc = $ForumLang['UserProfileAttach'.$FileUnit];
			$AvatarSizeString = $FileSize . '&nbsp;' . $UnitLoc;

			$ProfileAvatarGabaritsArray = getimagesize (OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar);
			$ProfileAvatarWidth  = $ProfileAvatarGabaritsArray[0];
			$ProfileAvatarHeight = $ProfileAvatarGabaritsArray[1];
			$AvatarGabaritsString = '&nbsp;' . $ProfileAvatarWidth . 'x' . $ProfileAvatarHeight;

			$CurrentAvatar = '<div style="margin-top:5px;" class="ExistingAttachDiv">
								<div>
									<img alt="" title="" src="' . OBB_AVATAR_DIR . '/' . $UserID . '.' . (string)$ProfileAvatar . '" />
								</div>
								<div style="margin-top:5px; margin-bottom:19px;">
									<span class="AttachInfo">' . $AvatarSizeString . ',&nbsp;' . $AvatarGabaritsString . '</span>
								</div>
							</div>';
		}
		else {
			$CurrentAvatar = '<span>' . $ForumLang['UserProfileNoAvatar'] . '</span>';
		}
		$AvatarFullBlock =  '<tr>
								<td style="width:200px;" class="ForumCharter">
									<span class="ThemesTitle">' . $ForumLang['UserProfileAvatarTitle'] . '</span>
								</td>
								<td style=" text-align:right; vertical-align:middle;" class="ForumCharter">
									<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfileAvatarClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfileAvatarImgID\');"><img id="ProfileAvatarImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
								</td>
							</tr>
							<tr class="ProfileAvatarClassHide">
								<td style="border-top:1px solid #FFF; padding-bottom:7px; width:200px;" class="FormTitleTD">
									<div class="InputTitle">
										' . $ForumLang['UserProfileShowAvatarTitle'] . '
									</div>
								</td>
								<td style="border-top:1px solid #FFF; padding-bottom:7px; padding-top:0;" class="FormInputTD">
									<div style="margin-bottom:10px; border:none; background:#DFE6EF;" class="MainBlockAttach">
										' . $CurrentAvatar . '
									</div>
								</td>
							</tr>';
	}
	else {
		$AvatarFullBlock = '';
	}

	//Start outputting
	$MainOutput .=  $HiddenForm .
					'<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td style="" colspan="1" class="MainColumnName"><span>' . $NavigProfile . '</span></td>
						</tr>
						<tr class="ForumMainTR">
							<td style="padding:0;">
								<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
									' . $CommonUserInfo  . '
									<tr>
										<td style="width:200px;" class="ForumCharter">
											<span class="ThemesTitle">' . $ForumLang['UserProfilePersonals'] . '</span>
										</td>
										<td style="text-align:right; vertical-align:middle;" class="ForumCharter">
											<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfilePersonalsClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfilePersonalsImgID\');"><img id="ProfilePersonalsImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="border-top:1px solid #FFF; padding-bottom:7px; width:200px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileBirth'] . '
											</div>
										</td>
										<td style="border-top:1px solid #FFF; padding-bottom:7px;" class="FormInputTD">
											<span>' . $PersonalBirth . '</span>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="padding-top:11px; padding-bottom:12px; width:200px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileSex'] . '
											</div>
										</td>
										<td style="padding-top:11px; padding-bottom:12px;" class="FormInputTD">
											<span>' . $PersonalSex . '</span>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="padding-bottom:7px; width:200px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileWWW'] . '
											</div>
										</td>
										<td style="padding-bottom:7px;" class="FormInputTD">
											<span>' . $PersonalSite . '</span>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="padding-top:16px; width:200px; vertical-align:top;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileSlogan'] . '
											</div>
										</td>
										<td style="padding-top:16px;" class="FormInputTD TextareaTD">
											<div>
												' . $PersonalSlogan . '
											</div>
											<div style="height:15px;"><!-- --></div>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="padding-bottom:18px; width:200px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileCountry'] . '
											</div>
										</td>
										<td style="padding-bottom:18px;" class="FormInputTD">
											<span>' . $PersonalCountry . '</span>
										</td>
									</tr>
									<tr class="ProfilePersonalsClassHide">
										<td style="padding-bottom:18px; width:200px;" class="FormTitleTD">
											<div class="InputTitle">
												' . $ForumLang['UserProfileCity'] . '
											</div>
										</td>
										<td style="padding-bottom:18px;" class="FormInputTD">
											' . $PersonalCity . '
										</td>
									</tr>
									<tr>
										<td style="width:200px;" class="ForumCharter">
											<span class="ThemesTitle">' . $ForumLang['UserProfileContacts'] . '</span>
										</td>
										<td style=" text-align:right; vertical-align:middle;" class="ForumCharter">
											<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'ProfileContactsClassHide\',\'' . OBB_IMAGE_DIR . '\',\'ProfileContactsImgID\');"><img id="ProfileContactsImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
										</td>
									</tr>
									<tr class="ProfileContactsClassHide">
											<td style="border-top:1px solid #FFFFFF; padding-bottom:16px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileMail'] . '
												</div>
											</td>
											<td style="border-top:1px solid #FFFFFF; padding-bottom:16px;" class="FormInputTD">
												' . $ContactsEMail . '
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-bottom:9px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileICQ'] . '
												</div>
											</td>
											<td style="padding-bottom:9px;" class="FormInputTD">
												' . $ContactsICQ . '
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-top:12px; padding-bottom:12px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfilePhone'] . '
												</div>
											</td>
											<td style="padding-top:10px; padding-bottom:12px;" class="FormInputTD">
												' . $ContactsPhone . '
											</td>
										</tr>
										<tr class="ProfileContactsClassHide">
											<td style="padding-top:12px; padding-bottom:15px; width:200px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['UserProfileMobile'] . '
												</div>
											</td>
											<td style="padding-top:10px; padding-bottom:15px;" class="FormInputTD">
												' . $ContactsMobile . '
											</td>
										</tr>
									' . $AvatarFullBlock . '
								</table>
							</td>
						</tr>
						<tr>
							<td class="ForumsTablePreBottom" colspan="1">
								<div style="height:10px;"><!-- --></div>
							</td>
						</tr>
					</table>';
}

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

$b = microtime ();
//echo ($b-$a);

?>