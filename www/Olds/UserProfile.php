<?php

$a = microtime ();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//проверка на гостевой доступ
if ($_SESSION['UserData']['UserType'] == 'guest' && OBB_GUEST_WATCH_PROFILE == FALSE) {
	OBB_Main_ShowError ('', 'NO_ACCESS', $ForumLang['Errors']);
}

//id пользователя
if (!isset ($_GET['user_id']) || !ctype_digit ($_GET['user_id'])) {	
	OBB_Main_ShowError ('profile_wrong_user_id', 'STANDART', $ForumLang['Errors']);
}
$UserID = intval ($_GET['user_id']); 

//Флаг аватара
if (OBB_ALLOW_AVATARS == TRUE && isset ($_FILES['ProfileAvatar']['name']) && $_FILES['ProfileAvatar']['name'] <> '') {
    $AvatarFlag = TRUE;
}
else {
    $AvatarFlag = FALSE;
}

//вложения
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.CheckUpload.class.php');
include (OBB_KERNEL_DIR . '/OwnBB.Transactions.Class.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/UserProfile_' . $Config_Lang . '.php');

//получение ресурса парсера
$signature_bb = OBB_BBCode_GetParseSign ();;

//определение, является ли заходящий владельцом профиля
if ($_SESSION['UserData']['UserType'] <> 'guest') {
	if ($_SESSION['UserData']['UserID'] == $UserID) {
		$ProfileOwner = TRUE;
	}
	else {
		$ProfileOwner = FALSE;
	}
}
else {
	$ProfileOwner = FALSE;
}

//массив дат
$DateArray = $ForumLang['DateArray'];

//выбор групп
$UserGroupArr = Main_GetUserGroups ();

//Функция обновления онлайн-информации о пользователе/госте
$CurAction = $ProfileOwner == TRUE ? 'own_profile' : 'user_profiles';
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, $CurAction);

//Главная переменнвя вывода
$MainOutput = '';

//Запрос пользователя (великий))
$UserDataSQL = 'SELECT users.UserLogin AS Login,
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
                       users.UserAvatar AS Avatar,
					   user_activity.UserLastLogin AS LastLogin,
					   user_activity.UserLastAction AS LastAction,
					   user_activity.UserIsOnline AS IsOnline
					FROM users
					LEFT JOIN user_activity ON users.UserID = user_activity.UserID
					WHERE users.UserID = \'' . $UserID . '\'';//echo $UserDataSQL;
$UserDataQuery = DB_Query ($Config_DBType, $UserDataSQL, $ForumConnection);
if (!$UserDataQuery) {
	OBB_Main_Log ($UserDataSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
}
$IsExist = DB_NumRows ($Config_DBType, $UserDataQuery);
if ($IsExist < 1) {
	OBB_Main_ShowError ('profile_no_user', 'STANDART', $ForumLang['Errors']);
}

//Присвоение - вспомогательная вещь для удобства
$UserDataRow = DB_FetchAssoc ($Config_DBType, $UserDataQuery);
$OldSlogan  = $Slogan     = $UserDataRow['Slogan'];
$OldMail    = $Mail       = $UserDataRow['Mail'];
$OldMailHid = $MailHid    = $UserDataRow['MailHid'];
$OldUSex    = $USex       = $UserDataRow['USex'];
$OldAdmMail = $AdmMail    = $UserDataRow['AdminMail'];
$OldOthMail = $OthMail    = $UserDataRow['OtherMail'];
$OldBirth   = $Birth      = $UserDataRow['Birth'];
$OldSite    = $Site       = $UserDataRow['Site'];
$OldCity    = $City       = $UserDataRow['City'];
$OldICQ     = $ICQ        = $UserDataRow['ICQ'];
$OldPhone   = $Phone      = $UserDataRow['Phone'];
$OldMobile  = $Mobile     = $UserDataRow['Mobile'];
$OldCountry = $Country    = $UserDataRow['Country'];
$OldAvatar  = $Avatar     = $UserDataRow['Avatar'];
$Login      = $UserDataRow['Login'];
$GroupID    = $UserDataRow['GroupID'];
$RegDate    = $UserDataRow['RegDate'];
$NumThemes  = $UserDataRow['NumThemes'];
$NumPosts   = $UserDataRow['NumPosts'];
$LastLogin  = $UserDataRow['LastLogin'];
$LastAction = $UserDataRow['LastAction'];
$IsOnline   = $UserDataRow['IsOnline'];

$TrueLogin = $Login;

//если нажата кнопка субмит
if ($ProfileOwner == TRUE && isset ($_POST['EditProfile']) && $_POST['EditProfile'] == '1') {
    //проверка откуда пользователь пришел
    $AllowedURL = 'http://' . $HostName . $SelfName . '?action=profile&user_id=' . $UserID;			
    if (Defence_ComingControl ($AllowedURL, '1') == FALSE) {
        OBB_Main_Redirect ($AllowedURL);
    }

    //массив ошибок
    $ProfileEditError = array ();

    //определение переменных - текстовые
    $ProfilePassword = isset ($_POST['ProfilePassword'])   && $_POST['ProfilePassword']   <> '' ? trim ($_POST['ProfilePassword'])   : '';
    $ProfilePRepeat  = isset ($_POST['ProfilePassRepeat']) && $_POST['ProfilePassRepeat'] <> '' ? trim ($_POST['ProfilePassRepeat']) : '';
    $ProfileCountry  = isset ($_POST['ProfileCountry'])    && $_POST['ProfileCountry']    <> '' ? trim ($_POST['ProfileCountry'])    : '';
    $ProfileCity     = isset ($_POST['ProfileCity'])       && $_POST['ProfileCity']       <> '' ? trim ($_POST['ProfileCity'])       : '';
    $ProfileBirth    = isset ($_POST['ProfileBirth'])      && $_POST['ProfileBirth']      <> '' ? trim ($_POST['ProfileBirth'])      : '';
    $ProfileSite     = isset ($_POST['ProfileSite'])       && $_POST['ProfileSite']       <> '' ? trim ($_POST['ProfileSite'])       : '';
    $ProfileSlogan   = isset ($_POST['ProfileSlogan'])     && $_POST['ProfileSlogan']     <> '' ? trim ($_POST['ProfileSlogan'])     : '';    
    $ProfileICQ      = isset ($_POST['ProfileICQ'])        && $_POST['ProfileICQ']        <> '' ? trim ($_POST['ProfileICQ'])        : '';
    $ProfilePhone    = isset ($_POST['ProfilePhone'])      && $_POST['ProfilePhone']      <> '' ? trim ($_POST['ProfilePhone'])      : '';
    $ProfileMobile   = isset ($_POST['ProfileMobile'])     && $_POST['ProfileMobile']     <> '' ? trim ($_POST['ProfileMobile'])     : '';
    $ProfileMail     = isset ($_POST['ProfileMail'])       && $_POST['ProfileMail']       <> '' ? trim ($_POST['ProfileMail'])       : '';
    
    //определение - чекбоксы и радиобоксы
    //  -пол
    $SexArray = array ('male', 'female');
    if (isset ($_POST['ProfileSex'])) {
        $USex = in_array ($_POST['ProfileSex'], $SexArray) ? $_POST['ProfileSex'] : 'male';
    }
    else {
        $USex = 'male';
    }
    
    //  -сокрытие эл. почты
    $ProfileHidMail = isset ($_POST['ProfileHidMail']) && $_POST['ProfileHidMail'] == TRUE ? 'yes' : 'no';
	$MailHid = $ProfileHidMail;
    
    //  -удаление аватара
    $ProfileNoUseAvatar = isset ($_POST['ProfileNoUseAvatar']) && $_POST['ProfileNoUseAvatar'] == TRUE ? 'yes' : 'no';
	
	//  -разрешение маила админу
	$ProfileAdmMail = isset ($_POST['ProfileAdmMail']) && $_POST['ProfileAdmMail'] == TRUE ? 'yes' : 'no';
	$AdmMail = $ProfileAdmMail;
	
	//  -разрешение маила пользователям
	$ProfileOthMail = isset ($_POST['ProfileOthMail']) && $_POST['ProfileOthMail'] == TRUE ? 'yes' : 'no';
	$OthMail = $ProfileOthMail;
            
    //1.Город
	if ($ProfileCity <> '') {
		if (Main_Strlen ($ProfileCity) < OBB_MIN_CITY_LENGTH || Main_Strlen ($ProfileCity) > OBB_MAX_CITY_LENGTH) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCityLength'];
		}
		else {
			$CityPattern = '/^[a-zа-я][-a-zа-я ]*[a-zа-я]$/ui';
			if (!preg_match ($CityPattern, $ProfileCity)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCityFormat'];
			}
		}
		$City = $ProfileCity;
	}
    
    //2.Страна
	if ($ProfileCountry <> '') {
		if (Main_Strlen ($ProfileCountry) < OBB_MIN_COUNTRY_LENGTH || Main_Strlen ($ProfileCountry) > OBB_MAX_COUNTRY_LENGTH) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCountryLength'];
		}
		else {
			$CountryPattern = '/^[a-zа-я][-a-zа-я ]*[a-zа-я]$/ui';
			if (!preg_match ($CountryPattern, $ProfileCountry)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongCountryFormat'];
			}
		}
		$Country = $ProfileCountry;
	}
    
    //3.Дата рождения
	if ($Birth <> $ProfileBirth) {
		$DatePattern = '/^[0-3][\d][-\.\/][01][\d][-\.\/][12][\d]{3}$/';		
		if (!preg_match ($DatePattern, $ProfileBirth)) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongDateFormat'];
		}
		/* else {
			проверка актуальности даты
		} */
		$Birth = $ProfileBirth;
	}
    
    //4.Домашняя страница
	if ($ProfileSite <> '') {
		if (Main_Strlen ($ProfileSite) < OBB_MIN_HOME_SITE_LENGTH || Main_Strlen ($ProfileSite) > OBB_MAX_HOME_SITE_LENGTH) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongSiteLength'];
		}
		else {
            $LowerProfileSite = Main_Strtolower ($ProfileSite);
			$SitePattern = "/^(https?:\/\/)?(([0-9a-z_!~*'().&=+$%-]+:)?[0-9a-z_!~*'().&=+$%-]+@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z_!~*'()-]+\.)*([0-9a-z][0-9a-z-]{0,61})+[0-9a-z]\.[a-z]{2,6})(:[0-9]{1,4})?((\/?)|(\/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+\/?)$/u";
			if (!preg_match ($SitePattern, $LowerProfileSite)) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongSiteFormat'];
			}
		}
		$Site = $ProfileSite;
	}
    
    //5.Девиз
	if ($ProfileSlogan <> '') {
		$SloganLength = Main_Strlen ($ProfileSlogan);
		if ($SloganLength > OBB_MAX_SLOGAN_LENGTH) {
			$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongSloganLength'];
		}
		else {
			$NumSloganRows = Defence_GetTextNumRows ($ProfileSlogan);
			if ($NumSloganRows > OBB_MAX_SLOGAN_ROWS) {
				$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongSloganRows'];
			}
		}
		$Slogan = $ProfileSlogan;
	}
    
    //6.Ася
    if ($ProfileICQ <> '') {
        $ICQPattern = '/^[\d]{9}$/iu';
        if (!preg_match ($ICQPattern, $ProfileICQ)) {
            $ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongICQFormat'];
        }
		$ICQ = $ProfileICQ;
    }
    
    //7.Дом. телефон
    if ($ProfilePhone <> '') {        
        $MobilePattern = '/^(?:\+7|8){1}[ ]?[\(][\d]{3}[\)][ ]?[\d]{3}[- ]?[\d]{2}[- ]?[\d]{2}$/ui';
        if (!preg_match ($MobilePattern, $ProfilePhone)) {
            $ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongPhoneFormat'];
        }
		$Phone = $ProfilePhone;
    }
    
    //8.Моб. телефон
    if ($ProfileMobile <> '') {        
        $MobilePattern = '/^(?:\+7|8){1}[ ]?[\(][\d]{3}[\)][ ]?[\d]{3}[- ]?[\d]{2}[- ]?[\d]{2}$/ui';
        if (!preg_match ($MobilePattern, $ProfileMobile)) {
            $ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongMobileFormat'];
        }
		$Mobile = $ProfileMobile;
    }

	//9.Почта
	if ($Mail <> $ProfileMail) {
		$CheckMailArray = OBB_Defence_CheckMail ($ProfileMail);
		$IsMailTrue = $CheckMailArray[0];
		if ($IsMailTrue == FALSE) {
			$MailError = $CheckMailArray[1];
			$ProfileEditError[] = $ForumLang['UserProfileErrors'][$MailError];
		}
		$Mail = $ProfileMail;
	}
    
    //10.Аватар    
	if ($ProfileNoUseAvatar == 'yes') {
		$ProfileAvatar = '';
		$AvatarAction  = 'delete';
	}
	else {
		if (OBB_ALLOW_AVATARS == TRUE) {
			if (isset ($_FILES['ProfileAvatar']['name']) && $_FILES['ProfileAvatar']['name'] <> '') {
				$AvatarDataArray = $_FILES['ProfileAvatar'];
				$AvatarObject = new OwnBB_CheckUpload ($FilesArray, OBB_MAX_AVATAR_SIZE, OBB_MAX_AVATAR_WIDTH, OBB_MAX_AVATAR_HEIGHT);
				$AttachArray = $AvatarObject->FullCheckOfUpload ($AvatarDataArray);
				
				$IsTrue = $AttachArray['IsTrue'];
				if ($IsTrue == FALSE) {
					$LangAttach      = $ForumLang['Attach'];
					$FileErrorCode   = $AttachArray['Error'];
					$ProfileEditError[] = $LangAttach[$FileErrorCode];
				}
				else {
					$IsImage = $AttachArray['Image'];
					if ($IsImage <> '1') {
						$ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongAvatar'];
					}
					else {
						$ProfileAvatar = Main_Strtolower ($AttachArray['Ext']);
						$AvatarAction  = 'update';
					}
				}
			}
			else {
				$ProfileAvatar = $Avatar;
				$AvatarAction  = 'no_action';
			}
		}
		else {
			$ProfileAvatar = $Avatar;
			$AvatarAction  = 'no_action';
		}
	}
    
    //11.Пароль
	$PasswordSQL = '';
    if (Main_Strlen ($ProfilePassword) > 0) {		
        $NewPassLength = Main_Strlen ($ProfilePassword);
        if ($NewPassLength < OBB_MIN_PASSWORD_LENGTH || $NewPassLength > OBB_MAX_PASSWORD_LENGTH) {
            $WrongPasswordLength = OBB_Main_ReplaceSymbols ($ForumLang['UserProfileErrors']['WrongPasswordLength'], array('min'=>OBB_MIN_PASSWORD_LENGTH, 'max'=>OBB_MAX_PASSWORD_LENGTH));
            $ProfileEditError[] = $WrongPasswordLength;
        }
        else {
            if ($ProfilePassword <> $ProfilePRepeat) {
                $ProfileEditError[] = $ForumLang['UserProfileErrors']['WrongPasswordEqual'];
            }
			else {
				$HashedPassword = Main_Crypt ($ProfilePassword);
				$PasswordSQL = 'UserPassword = \'' . $HashedPassword . '\', ';
			}
        }
    }
  
    //если ошибок нет
    if (sizeof ($ProfileEditError) < 1) {
		$Transaction = new OwnBB_Transactions ($Config_DBType, $ForumConnection, OBB_TRANSACTION_LOG_DIR);
		$Transaction->StartTransaction ();
		
		//действие с аватаром
		if (!isset ($AvatarAction)) {
			$AvatarAction = 'no_action';
		}
		
        //Великий запрос редактирования пользователя :)
		$EditProfileSQL = 'UPDATE users
						   SET UserSlogan = \'' . Defence_EscapeString ($Config_DBType, $ProfileSlogan) . '\',
							   UserMail = \'' . Defence_EscapeString ($Config_DBType, $ProfileMail) . '\',
							   ' . $PasswordSQL . '
							   UserMailHid = \'' . $ProfileHidMail . '\',
                               UserAdminMail = \'' . $ProfileAdmMail . '\',
                               UserOtherMail = \'' . $ProfileOthMail . '\',
							   UserSex = \'' . $USex . '\',
							   UserBirthDate = \'' . Defence_EscapeString ($Config_DBType, $ProfileBirth) . '\',
							   UserSite = \'' . Defence_EscapeString ($Config_DBType, $ProfileSite) . '\',
							   UserCity = \'' . Defence_EscapeString ($Config_DBType, $ProfileCity) . '\',
							   UserCountry = \'' . Defence_EscapeString ($Config_DBType, $ProfileCountry) . '\',
							   UserICQ = \'' . Defence_EscapeString ($Config_DBType, $ProfileICQ) . '\',
							   UserPhone = \'' . Defence_EscapeString ($Config_DBType, $ProfilePhone) . '\',
							   UserMobile = \'' . Defence_EscapeString ($Config_DBType, $ProfileMobile) . '\',
							   UserAvatar = \'' . Defence_EscapeString ($Config_DBType, $ProfileAvatar) . '\'
						   WHERE UserID = \'' . $UserID . '\'';
		$EditProfileQuery = DB_Query ($Config_DBType, $EditProfileSQL, $ForumConnection);
		if (!$EditProfileQuery) {
			OBB_Main_Log ($EditProfileSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
		}
		
		if ($AvatarAction <> 'no_action') {
			$EditProfileArray = array ('Operation'  => 'u',
									   'FieldArray' =>  array ('UserLogin'     => array ('Value'=>$OldLogin, 'Flag'=>'none', 'Type'=>'string'),
															   'UserSlogan'    => array ('Value'=>$OldSlogan, 'Flag'=>'none', 'Type'=>'string'),
															   'UserMail'	   => array ('Value'=>$OldMail, 'Flag'=>'none', 'Type'=>'string'),
															   'UserMailHid'   => array ('Value'=>$OldMailHid, 'Flag'=>'none', 'Type'=>'string'),
															   'UserAdminMail' => array ('Value'=>$OldAdmMail, 'Flag'=>'none', 'Type'=>'string'),
															   'UserOtherMail' => array ('Value'=>$OldOthMail, 'Flag'=>'none', 'Type'=>'string'),
															   'UserSex'       => array ('Value'=>$OldUSex, 'Flag'=>'none', 'Type'=>'string'),
															   'UserBirthDate' => array ('Value'=>$OldBirth, 'Flag'=>'none', 'Type'=>'string'),
															   'UserSite'      => array ('Value'=>$OldSite, 'Flag'=>'none', 'Type'=>'string'),
															   'UserCity'      => array ('Value'=>$OldCity, 'Flag'=>'none', 'Type'=>'string'),
															   'UserCountry'   => array ('Value'=>$OldCountry, 'Flag'=>'none', 'Type'=>'string'),
															   'UserICQ'       => array ('Value'=>$OldICQ, 'Flag'=>'none', 'Type'=>'string'),
															   'UserPhone'     => array ('Value'=>$OldPhone, 'Flag'=>'none', 'Type'=>'string'),
															   'UserMobile'    => array ('Value'=>$OldMobile, 'Flag'=>'none', 'Type'=>'string'),
															   'UserAvatar'    => array ('Value'=>$OldAvatar, 'Flag'=>'none', 'Type'=>'string')),
									   'WhereArray' => array('UserID'=>array ('Value'=>$UserID, 'Type'=>'int')), 
									   'TableName'  => 'themes'
								      );
				$Transaction->PrepareQueryToRollback ($EditProfileArray);
								  
			$MoveAvatar = TRUE;
			
			$AvatarPath = OBB_AVATAR_DIR . '/' . $UserID . '.';			
			if ($AvatarAction == 'delete') {
				$AvatarPath .= $OldAvatar;
				$AvatarExists  = file_exists ($AvatarPath);
				if ($AvatarExists == TRUE) {
					if (!unlink ($AvatarPath)) {
						$MoveAvatar = FALSE;
					}
				}
			}
			else if ($AvatarAction == 'update') {
				$OldAvaPath = $AvatarPath . $OldAvatar;
				if (file_exists ($OldAvaPath)) {
					if (!unlink ($OldAvaPath)) {
						$MoveAvatar = FALSE;
					}
				}
				$AvatarPath .= $ProfileAvatar;
				$TMPName = $AttachArray['Data'];
				if (!move_uploaded_file ($TMPName, $AvatarPath)) {
					$MoveAvatar = FALSE;
				}
			}
			
			if ($MoveAvatar == FALSE) {
				$Transaction->RollbackTransaction ();
				OBB_Main_Log ('Error while updating/deleting user avatar', OBB_ERROR_LOG_FILE);
			}
		}
		        
		//  -письмо администратору
		if ($Config_Mail['AdminMail'] == '1') {					
			$AdmProfLogin = $TrueLogin;			
			$AdmProfID    = $UserID;
			$AdmProfDate  = Main_ConvertDate (time (), '', $Format = 'd.m.y, H:i');
			$AdmTheme     = $ForumLang['UserProfileAdminMailProfile'] . ' "' . $Config_ForumName . '"';
			$AdminName    = $Config_Mail['FromName'];
			$AdminMail    = $Config_Mail['FromMail'];
			
			$AdminLetter = file_get_contents (OBB_HTML_LANGUAGE_DIR . '/AdminMailProfile.html');
			
			$AdminLetter = str_replace ('{username}', $AdmProfLogin, $AdminLetter);			
			$AdminLetter = str_replace ('{userid}', $AdmProfID, $AdminLetter);
			$AdminLetter = str_replace ('{userdate}', $AdmProfDate, $AdminLetter);
			
			#$RegMailer->SendMail ($AdminName, $AdminMail, $AdmTheme, $AdminLetter);
			file_put_contents (OBB_ERROR_MAIL_DIR . '/Admin_EditProfLog' . $UserID . '.html', $AdminLetter);
		}
		
        //письмо юзеру
		
        //редирект - если нет ошибок
		$RedirectURL = '?action=profile&user_id=' . $UserID;
		$_SESSION['Message'] = 'edit_profile';
		$_SESSION['Redirect'] = $RedirectURL;
		OBB_Main_Redirect ('?action=message');
    }
}

//Формируем массив главной навигации
$NavigProfile = $ForumLang['UserProfileTitle'] . ' \'' . Defence_HTMLSpecials ($TrueLogin) . '\'';
$NavigArray = array (
                     array ($SelfName, $ForumLang['MainPage']),
					 array ('', $NavigProfile)
                    );

//верх
$NavTranslate = $ForumLang['Navig'];
$MainOutput .= Echo_PrintHead ($IsJS='1', $NavTranslate);

//если определены ошибки - выводим блок ошибок
if (isset ($ProfileEditError)) {
	if (sizeof ($ProfileEditError) > 0) {
		$ErrorBlock = Echo_DisplayUserErrors ($ProfileEditError, $ForumLang['UserProfileErrors']['ErrorBlockTitle']);
	}
	else {
		$ErrorBlock = '';
	}
}
else {
	$ErrorBlock = '';
}
$MainOutput .= $ErrorBlock;

//главная навигация				
$MainOutput .= '<div class="MainNavigDiv">' . Echo_Navigation ($NavigArray, $Config_NavigDeleter) . '</div>';

//обшая информвция
$UserIsOnline = $IsOnline == 'yes' ? $ForumLang['UserProfileOnline'] : $ForumLang['UserProfileOffline'];
if ($IsOnline == 'yes') {
	$ActionString = $ForumLang['UserProfileActions'][$LastAction];
}
else {
	$ActionString = $ForumLang['UserProfileLastVisit'] . ' ' . Main_ConvertDate ($LastLogin, $ForumLang['DateArray'], 'd.m.Y') . ' ' . $ForumLang['UserProfileIn'] . ' ' . Main_ConvertDate ($LastLogin, $ForumLang['DateArray'], 'H:i');
}

//Вспомогательные
$Star         = $ProfileOwner == TRUE  ? '<font color="red">*</font>' : '';
$DoHid        = $ProfileOwner == TRUE  ? '<span>' . $ForumLang['UserProfileHidden'] . '</span>' : '';
$IsEmpty      = $ProfileOwner == FALSE ? '<i>' . $ForumLang['UserProfileEmpty']    . '</i>' : '';
$NotAvailable = $ProfileOwner == FALSE ? '<i>' . $ForumLang['UserProfileNotAvail'] . '</i>' : '';

//форма
$MultyPart  = OBB_ALLOW_AVATARS == TRUE && $ProfileOwner == TRUE ? ' enctype="multipart/form-data"' : '';
$FormStart  = $ProfileOwner == TRUE ? '<form action="' . $SelfName . '?action=profile&user_id=' . $UserID . '" method="POST"' . $MultyPart . '>' : '';
$FormEnd    = $ProfileOwner == TRUE ? '</form>' : '';
$EditButton = $ProfileOwner == TRUE ? '<br /><input type="submit" value="' . $ForumLang['UserProfileSubmit'] . '">' : '';
$HiddenVar  = $ProfileOwner == TRUE ? '<input type="hidden" name="EditProfile" value="1">' : '';

//Данные пользователя
//1.Персональная информация
if ($ProfileOwner == TRUE) {
	//  -страна, город, д.р., домашняя страница	
	$PersonalCountry = '<input type="text" name="ProfileCountry" maxlength="' . OBB_MAX_COUNTRY_LENGTH   . '" value="' . Defence_HTMLSpecials ($Country) . '">';
	$PersonalCity    = '<input type="text" name="ProfileCity" maxlength="'    . OBB_MAX_CITY_LENGTH      . '" value="' . Defence_HTMLSpecials ($City)    . '">';
	$PersonalBirth   = '<input type="text" name="ProfileBirth" maxlength="10"                               value="'   . Defence_HTMLSpecials ($Birth)   . '">';	
	$PersonalSite    = '<input type="text" name="ProfileSite" maxlength="'    . OBB_MAX_HOME_SITE_LENGTH . '" value="' . Defence_HTMLSpecials ($Site)    . '">';
	
	//  -девиз
	$PersonalSlogan  = '<textarea name="ProfileSlogan" cols="30" rows="3">' . Defence_HTMLSpecials ($Slogan) . '</textarea>';
	
	//  -пол
	$MaleChecked   = $USex == 'male'   ? ' checked' : '';
	$FemaleChecked = $USex == 'female' ? ' checked' : '';
	$PersonalMale   = '<input type="radio"' . $MaleChecked   . ' name="ProfileSex" value="male"  >&nbsp;' . $ForumLang['UserProfileMale'];
	$PersonalFemale	= '<input type="radio"' . $FemaleChecked . ' name="ProfileSex" value="female">&nbsp;' . $ForumLang['UserProfileFemale'];
	$PersonalSex    = '<span>' . $PersonalMale . $PersonalFemale . '</span>';
}
else {
    //  -д.р.
	$PersonalBirth = Defence_HTMLSpecials ($Birth);
	
	//  -страна, город, девиз
	$PersonalCountry = !OBB_Main_IsEmpty ($Country) ? Defence_HTMLSpecials ($Country) : $IsEmpty;
	$PersonalCity    = !OBB_Main_IsEmpty ($City)    ? Defence_HTMLSpecials ($City)    : $IsEmpty;
    if (!OBB_Main_IsEmpty ($Slogan)) {
        if (OBB_BB_PARSE_ALLOWED) {
            $PersonalSlogan = $signature_bb->parse ($Slogan);
			//$PersonalSlogan = $Slogan;
        }
        else {
            $PersonalSlogan = Defence_HTMLSpecials ($Slogan);
        }
        $PersonalSlogan = nl2br ($PersonalSlogan);
    }
    else {
        $PersonalSlogan = $IsEmpty;
    }
	
	//  -домашняя страница
	$UserSite     = Defence_HTMLSpecials ($Site);
	$PersonalSite = !OBB_Main_IsEmpty ($Site) ? '<a target="_blank" href="' . $UserSite . '">' . $UserSite . '</a>' : $IsEmpty;
	
	//  -пол
	$PersonalSex = $USex == 'male' ? $ForumLang['UserProfileMale'] : $ForumLang['UserProfileFemale'];
	$PersonalSex = '<span>' . $PersonalSex . '</span>';
}

//2.Данные форума
//  -группа, статус, к-во тем, сообщений, номер пользователя 
$StatisticsGroup  = '<span class="' . $UserGroupArr[$GroupID]['Eng'] . '">' . $UserGroupArr[$GroupID]['Des'] . '</span>';
$StatisticsStat   = '<span>' . Echo_GetUserStatus ($NumPosts, OBB_NUM_POSTS_IN_STATUS, OBB_MAX_STATUS, OBB_IMAGE_DIR) . '</span>';
$StatisticsNum    = '<span>' . $UserID    . '</span>';
$StatisticsThemes = '<span>' . $NumThemes . '</span>';
$StatisticsPosts  = '<span>' . $NumPosts  . '</span>';

//  -дата регистрации
$UserRegDate = Main_ConvertDate ($RegDate, $DateArray, 'full');
$UserRegArr  = explode (',', $UserRegDate);
$UserRegDate = $UserRegArr[0] . ', ' . $UserRegArr[1];
$StatisticsReg = '<span>' . $UserRegDate . '</span>';

//  -ссылки на просмотр сообщений и тем
$ShowAllThemes = '<span><a href="javascript:void(0);">' . $ForumLang['UserProfileAllThemes'] . '</a></span>';
$ShowAllPosts  = '<span><a href="javascript:void(0);">' . $ForumLang['UserProfileAllPosts']  . '</a></span>';

//3.Контактные данные
if ($ProfileOwner == TRUE) {
	//  -аська, телефоны
	$ContactsICQ     = '<input type="text" name="ProfileICQ" maxlength="9" value="' . Defence_HTMLSpecials ($ICQ)    . '">';
	$ContactsPhone   = '<input type="text" name="ProfilePhone"  value="' . Defence_HTMLSpecials ($Phone)  . '">';
	$ContactsMobile  = '<input type="text" name="ProfileMobile" value="' . Defence_HTMLSpecials ($Mobile) . '">';
	
	//  -эл. почта
	$HidChecked    = $MailHid == 'yes' ? ' checked' : '';
	$ContactIsHid  = '<input' . $HidChecked . ' type="checkbox" name="ProfileHidMail">';
	$ContactsEMail = '<input type="text" name="ProfileMail" maxlength="' . OBB_MAX_MAIL_LENGTH . '" value="' . Defence_HTMLSpecials ($Mail) . '">';
	
	//  -отсыл письма админом
    $AdmMailCheck    = $AdmMail == 'yes' ? ' checked' : '';
	$TextAdmMail     = $ForumLang['UserProfileAdmMail'];
	$ContactsAdmMail = '<input' . $AdmMailCheck . ' type="checkbox" name="ProfileAdmMail">';
    
    //  -отсыл письма пользователями
    $OthMailCheck    = $OthMail == 'yes' ? ' checked' : '';
	$TextOthMail     = $ForumLang['UserProfileOthMail'];
    $ContactsOthMail = '<input' . $OthMailCheck . ' type="checkbox" name="ProfileOthMail">';
	
    //  -пароль
    $PasswordDiv = '<br />
                    <div>
                        <span>' . $ForumLang['UserProfilePassword'] . '</span>
                        <br />
                        <span><input type="password" name="ProfilePassword"   maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" /></span>
                        <br />
                        <span>' . $ForumLang['UserProfilePassRepeat'] . '</span>
                        <br />
                        <span><input type="password" name="ProfilePassRepeat" maxlength="' . OBB_MAX_PASSWORD_LENGTH . '" value="" /></span>
                    </div>';
    
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
	$ContactsICQ     = !OBB_Main_IsEmpty ($ICQ)    ? Defence_HTMLSpecials ($ICQ)     : $IsEmpty;
	$ContactsPhone   = !OBB_Main_IsEmpty ($Phone)  ? Defence_HTMLSpecials ($Phone)   : $IsEmpty;
	$ContactsMobile  = !OBB_Main_IsEmpty ($Mobile) ? Defence_HTMLSpecials ($Mobile)  : $IsEmpty;
	
	//  -эл. почта
	$HidChecked      = '';
	$ContactIsHid    = '';
	if ($MailHid == 'yes') {
		$ContactsEMail = $NotAvailable;
	}
	else {
		$ContactsEMail = !OBB_Main_IsEmpty ($Mail) ? Defence_HTMLSpecials ($Mail) : $IsEmpty;
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
//  -картинка
if (!OBB_Main_IsEmpty ($Avatar)) {
    if ($ProfileOwner == TRUE) {
        $AvatarDoNot = '<span><input type="checkbox" name="ProfileNoUseAvatar"></span>&nbsp;<span>' . $ForumLang['UserProfileAvatarDel'] . '</span>';
    }
    else {
        $AvatarDoNot = '';
    }
    $AvatarImage = '<span><img src="' . OBB_AVATAR_DIR . '/' . $UserID . '.' . $Avatar . '" border="0" /></span>';
}
else {
    $AvatarDoNot = '';
    $AvatarImage = '<span>' . $ForumLang['UserProfileNoAvatar'] . '</span>';
}

//  -форма загрузки нового аватара
if ($ProfileOwner == TRUE && OBB_ALLOW_AVATARS == TRUE) {	
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
$AvatarAll = '<div>
                  ' . $AvatarImage . '
                  &nbsp;
                  ' . $AvatarDoNot . '
              </div>
              <br />
              <div>
                  ' . $AvatarLoadForm . '
              <div>';

//Начало вывода данных
$MainOutput .= '<tr>
					<td>
						<div>
							<div>
								<b>' . Defence_HTMLSpecials ($TrueLogin) . '</b>					
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
								<span>' . $PersonalBirth . '</span>
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
$MainOutput .= '		<br />
						<div class="Avatar" id="Avatar">
							<div>
								<b>' . $ForumLang['UserProfileAvatar'] . '</b>
							</div>
                            <div>
                                ' . $AvatarAll . '
                            </div>
						</div>';

//Опции получения писем
$MainOutput .= $ContactsComMail;

/*************************
* CAPTCHA
*************************/
                        
$MainOutput .= $HiddenVar;
$MainOutput .= $EditButton;
$MainOutput .= $FormEnd;

//конец вывода данных						
$MainOutput .= '	</td>
				</tr>';

//Футер форума
$MainOutput .= Echo_PrintFoot ();

//вывод
Main_ShowStandartHeader ();
echo $MainOutput;

$b = microtime ();
echo ($b-$a);

?>