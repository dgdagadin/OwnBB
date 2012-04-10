<?php

/*********************************************************
 * имя класса: OwnBB_Mailer
 * функция класса: отрпавка сообщения по электронной почте 
 ********************************************************/

Class OwnBB_Mailer {

	/*
	//////////////////////
	/////СВОЙСТВА/////////
	//////////////////////
	*/

	/**
	 * имя администратора
	 * (string)
	 */
	var $FromName;

	/**
	 * электронная почта администратора
	 * (string)
	 */
	var $FromMail;

	/**
	 * версия PHP
	 * (string)
	 */
	var $PHPVersion;

	/**
	 * кодировка по умолчанию
	 * (string)
	 */
	var $CurrentCharset;

	/**
	 * кодировка для отправки письма
	 * (string)
	 */
	var $TrueCharset;

	/**
	 * mime-тип письма
	 * (string)
	 */
	var $MailMime;

	/*
	//////////////////////
	/////МЕТОДЫ///////////
	//////////////////////
	*/

	/**
	 * Конструктор класса - инициализация. Параметры:
	 *   array  $Config_Mail    настройки мейла в конфиге
	 * -
	 */

	function __construct ($Config_Mail) {
		$this->FromName       = $Config_Mail['FromName'];
		$this->FromMail       = $Config_Mail['FromMail'];
		$this->CurrentCharset = $Config_Mail['CurrentCharset'];
		$this->TrueCharset    = $Config_Mail['TrueCharset'];
		$this->MailMime       = $Config_Mail['MailMime'];
		$this->PHPVersion     = $this->GetPHPVersion ();
	}

	/**
	 * Отсылает письмо получателю. Параметры:
	 *   string  $ToName        имя получателя
	 *   string  $ToMail        адрес эл. почты получателя
	 *   string  $MailTheme     тема письма
	 *   string  $Message       текст письма
	 * (bool)
	 */
	public function SendMail ($ToName, $ToMail, $MailTheme, $Message) {
		$SendHeaders = $this->SetHeaders ();
		$SendFrom    = $this->FromMail;
		$SendTheme   = $this->String2Header ($MailTheme);
		$SendName    = $this->String2Header ($ToName);
		$SendMail    = '<' . $ToMail . '>';
		$SendTo      = $SendName . ' ' . $SendMail;
		$SendMessage = $this->ChangeCharset ($String);
	   
		$MailAction = mail ($SendTo, $SendTheme, $SendMessage, $SendHeaders, '-f' . $SendFrom);
		return ($MailAction);
	}

	/**
	 * Формирует заголовки сообщения. Параметры:    
	 *   -
	 * (string)
	 */
	public function SetHeaders () {
		$MailFromName    = $this->FromName;
		$MailFromAddress = $this->FromMail;
		$MailMime        = $this->MailMime;
		$MailCharset     = $this->TrueCharset;
		$MailPHPVersion  = $this->PHPVersion;

		$MailFromName = $this->String2Header ($MailFromName);

		$Headers  = '';
		$Headers .= "From: " . $MailFromName ."<" . $MailFromAddress . ">\n";        
		$Headers .= "X-Mailer: PHP/" . $MailPHPVersion . "\n";
		if (strcmp ($MailMime, 'text/html') == 0) {
			$Headers .= "MIME-Version: 1.0\n";
		}
		$Headers .= "Content-type: " . $MailMime . "; charset=" . $MailCharset . "";

		return ($Headers);
	}

	/**
	 * Преобразует строку в корректный заголовок. Параметры:    
	 *   string  $String        преобразуемая строка        
	 * (string)
	 */
	public function String2Header ($String) {       
		$String = $this->ChangeCharset ($String);      
		$String = base64_encode ($String);
		$String = '=?' . $TrueChrst . '?B?' . $String . '?=';        
		return ($String);
	}

	/**
	 * Меняет кодировку у строки. Параметры:    
	 *   string  $String        преобразуемая строка        
	 * (string)
	 */
	public function ChangeCharset ($String) {
		$CurChrst  = $this->CurrentCharset;
		$TrueChrst = $this->TrueCharset;
		if ($CurChrst <> $TrueChrst) {
			$String = iconv ($CurChrst, $TrueChrst, $String);
		}
		return ($String);
	}

	/**
	 * Получает версию PHP. Параметры:    
	 *   -
	 * (string)
	 */
	public function GetPHPVersion () {
		return phpversion ();
	}

}

?>