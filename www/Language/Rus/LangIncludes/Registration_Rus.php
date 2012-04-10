<?php

//Регистрация
$ForumLang['Registration'] = 'Регистрация';
$ForumLang['RegStepOne'] = 'шаг первый';
$ForumLang['RegStepTwo'] = 'шаг второй';
$ForumLang['RegStepThree'] = 'активация';
$ForumLang['RegRules'] = 'Правила регистрации';
$ForumLang['AgreeWithRules'] = 'Я принимаю правила';
$ForumLang['RegContinue'] = 'Продолжить регистрацию';
$ForumLang['RegPersonMain'] = 'Персональные данные';
$ForumLang['RegSymbolWord'] = 'символов';
$ForumLang['RegLoginTitle'] = 'Логин';
$ForumLang['RegMailTitle'] = 'Электронная почта';
$ForumLang['RegRepeatMailTitle'] = 'Повторите электронную почту';
$ForumLang['RegPasswordTitle'] = 'Пароль';
$ForumLang['RegRepeatPasswordTitle'] = 'Повторите пароль';
$ForumLang['RegSexTitle'] = 'Пол';
$ForumLang['RegSexMTitle'] = 'Мужской';
$ForumLang['RegSexFTitle'] = 'Женский';
$ForumLang['RegBirthDateTitle'] = 'Дата рождения';
$ForumLang['RegAvatar'] = 'Аватар';
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
	//ошибки регистрации//
$ForumLang['RegErrors'] = array ('ErrorBlockTitle'    => 'Ошибки при регистрации',
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
	$ForumLang['ActErrors']['NoUser'] = 'Ошибка активации - пользователь не зарегистрирован';
	$ForumLang['ActErrors']['WrongKey'] = 'Ошибка активации - неверный ключ активации';
	$ForumLang['ActErrors']['AlreadyActivated'] = 'Ошибка активации - пользователь уже активирован';
	$ForumLang['ActErrors']['WrongActivationDate'] = 'Неверная дата активации';
	//ошибки активации
//Регистрация - конец

?>