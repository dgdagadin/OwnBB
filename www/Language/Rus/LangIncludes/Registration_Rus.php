<?php

//Регистрация
$ForumLang['RegAttachAdd'] = 'Выберите файл для прикрепления';
$ForumLang['Registration'] = 'Регистрация';
$ForumLang['RegStepOne'] = 'шаг первый';
$ForumLang['RegStepTwo'] = 'шаг второй';
$ForumLang['RegStepThree'] = 'активация';
$ForumLang['RegRules'] = 'Правила регистрации';
$ForumLang['AgreeWithRules'] = 'Я принимаю правила';
$ForumLang['RegContinue'] = 'Продолжить регистрацию';
$ForumLang['RegReset'] = 'Очистить форму';
$ForumLang['RegPersonMain'] = 'Персональные данные';
$ForumLang['RegSymbolWord'] = 'символов';
$ForumLang['RegLoginTitle'] = 'Имя пользователя';
$ForumLang['RegLoginLimit'] = '{min} - {max} символов';
$ForumLang['RegMailLimit'] = '{min} - {max} символов';
$ForumLang['RegPasswordLimit'] = '{min} - {max} символов';
$ForumLang['RegMailTitle'] = 'Электронная почта';
$ForumLang['RegRepeatMailTitle'] = 'Повторите электронную почту';
$ForumLang['RegPasswordTitle'] = 'Введите пароль';
$ForumLang['RegRepeatPasswordTitle'] = 'Повторите пароль';
$ForumLang['RegSexTitle'] = 'Выберите ваш пол';
$ForumLang['RegSexMTitle'] = 'Мужской';
$ForumLang['RegSexFTitle'] = 'Женский';
$ForumLang['RegBirthDateTitle'] = 'Дата рождения';
$ForumLang['RegBirthLimit'] = 'в формате дд.мм.гггг';
$ForumLang['RegAvatar'] = 'Загрузите ваш аватар';
$ForumLang['RegAvatarAllowed'] = 'Допустимые расширения';
$ForumLang['RegAvatarAllowedSize'] = 'Допустимый размер аватара';
$ForumLang['RegAvatarAllowedGabarits'] = 'Допустимые габариты аватара';
$ForumLang['RegAvatarb'] = 'Байт';
$ForumLang['RegAvatarWidth'] = 'ширина';
$ForumLang['RegAvatarHeight'] = 'высота';
$ForumLang['RegTitleAvatar'] = 'Загрузите картинку';
$ForumLang['RegMax'] = 'максимум';
$ForumLang['RegBytes'] = 'байт';
$ForumLang['RegOther'] = 'Дополнительные опции';
$ForumLang['RegHideMail'] = 'Скрывать электронную почту';
$ForumLang['RegGetAdminMail'] = 'Получать письма от администратора';
$ForumLang['RegGetUserMail'] = 'Получать письма от пользователей';
$ForumLang['RegFootnote'] = 'Поля, обязательные для заполнения';
$ForumLang['RegMailThemeUser'] = 'Регистрация на форуме';
$ForumLang['RegMailThemeAdmin'] = 'Регистрация нового пользователя на форуме';
$ForumLang['RegImportantFields'] = 'Поля, обязательные для заполнения';
	//ошибки регистрации//
$ForumLang['RegErrors'] = array ('ErrorBlockTitle'   => 'Ошибки при регистрации',
								'RegEmptyMail'       => 'Не заполнено поле почты',
								'RegEmptyLogin'      => 'Не заполнено поле логина',
								'RegEmptyRepeatMail' => 'Не заполнено поле повтора почты',
								'RegEmptyDate'       => 'Не заполнено поле даты',
								'RegBadLoginLength'  => 'Длина логина должна составлять минимум {min} и максимум {max} символов',
								'RegBadLoginSymbols' => 'Некорректные логинские символы!',
								'RegLoginExists'     => 'Пользователь с таким логином уже зарегистрирован!',
								'PasswordIsEmpty'    => 'Необходимо ввести пароль',
								'RepeatPassIsEmpty'  => 'Необходимо повторить введенный пароль',
								'WrongPasswordLength'=> 'Длина пароля должна составлять минимум {min} и максимум {max} символов',
								'WrongPasswordEqual' => 'Введенные пароли не совпадают',
								'RegBadMailLength'   => 'Длина электронной почты должна составлять минимум {min} и максимум {max} символов',
								'RegBadMailSymbols'  => 'Некорректный формат электронной почты',
								'RegMailsNoEq'       => 'Введенные электронные почты не совпадают',
								'RegMailExists'      => 'Пользователь с такой электронной почтой уже зарегистрирован',
								'RegWrongDate'       => 'Неправильный формат даты - дата должна быть в виде дд.мм.гггг',
								'RegWrongAvatar'     => 'Загруженный вами в качестве аватара файл не является изображением',
								'BadCaptcha'         => 'Вы ввели неправильно защитный код',
								'EmptyCaptcha'       => 'Вы не ввели защитный код',
								'CaptchaError'       => 'Ошибка защитного кода');

	//ошибки регистрации//
	
	//ошибки активации
	$ForumLang['ActErrors'] = array ();
	$ForumLang['ActErrors']['ErrorBlockTitle'] = 'Ошибки при активации';
	$ForumLang['ActErrors']['NoUser'] = 'Пользователь не зарегистрирован';
	$ForumLang['ActErrors']['WrongKey'] = 'Неверный ключ активации';
	$ForumLang['ActErrors']['AlreadyActivated'] = 'Пользователь уже активирован';
	$ForumLang['ActErrors']['WrongActivationDate'] = 'Неверная дата активации';
	//ошибки активации
//Регистрация - конец

?>