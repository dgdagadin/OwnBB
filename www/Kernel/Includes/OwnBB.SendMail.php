<?php

/************************************************************************************
***Данный файл отвечает за отсылку писем по электронной почте************************
***Скрипт основан на библиотеке PHPMailer (http://phpmailer.worxware.com/)***********
*************************************************************************************/

//ПОДКЛЮЧЕНИЕ БИБЛИОТЕКИ PHPMailer
include (OBB_PHPMAILER_LIB_DIR . '/class.phpmailer.php');

//функция отправки письма по электронной почте
function OBB_Mail_Send ($SenderArray, $GetterArray, $Subject, $Body) {
	//запуск
	$mail = new PHPMailer();

	//инициализация
	$SenderAddress = $SenderArray['address'];
	$SenderName    = $SenderArray['name'];
	$GetterAddress = $GetterArray['address'];
	$GetterName    = $GetterArray['name'];

	//настройка имени и адреса отправителя письма
	$mail->SetFrom($SenderAddress, $SenderName, $auto = 1);

	//настройка адреса и имени получателя письма
	$mail->AddAddress($GetterAddress, $GetterName);

	//тема сообщения
	$mail->Subject = $Subject;

	//текст сообщения
	//  --html
	$mail->MsgHTML($Body);

	//  --plain/text
	$TextBody = OBB_Mail_ClearTags ($Body);
	$mail->AltBody = $TextBody;

	//отправка
	if (!$mail->Send()) {
		return (false);
	}
	else {
		return (TRUE);
	}
}

//функция очистки тела письма от тегов
function OBB_Mail_ClearTags ($String) {
	return ($String);
}

?>