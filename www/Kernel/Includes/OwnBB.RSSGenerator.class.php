<?php

/*********************************************************
 * имя класса: OwnBB_RSS
 * функция класса: генерация RSS-ленты 
 ********************************************************/
 
class OwnBB_RSSGenerator {

   /*
	//////////////////////
	/////СВОЙСТВА/////////
	//////////////////////
	*/

	/**
	 * переменная, содержащая весь XML
	 * (string)
	 */
	var $RSSString;

	/**
	 * Главный заголовок RSS
	 * (string)
	 */
	var $RSSTitle;

	/**
	 * Главная ссылка RSS (на гл. стр. сайта)
	 * (string)
	 */
	var $RSSLink;

	/**
	 * Описание RSS-ленты
	 * (string)
	 */
	var $RSSDescription;

	/**
	 * Язык RSS-ленты
	 * (string)
	 */
	var $RSSLanguage;

	/**
	 * Дата послед. генерирования RSS-ленты
	 * (string)
	 */
	var $RSSLastDate;

	/**
	 * Инструмент генерирования RSS-ленты
	 * (string)
	 */
	var $RSSGenerator;

	/**
	 * Программист (электронная почта)
	 * (string)
	 */
	var $RSSProgrammer;    

	/*
	//////////////////////
	/////МЕТОДЫ///////////
	//////////////////////
	*/

	/**
	 * Конструктор класса - Инициализация. Параметры:
	 *   array  $ConfigArray      массив с данными для RSS
	 * -
	 */

	function __construct ($ConfigData) {
		$this->RSSTitle       = $ConfigData['RSSTitle'];
		$this->RSSLink        = $ConfigData['RSSLink'];
		$this->RSSDescription = $ConfigData['RSSDescription'];
		$this->RSSLastDate    = $ConfigData['RSSLastDate'];
		$this->RSSLanguage    = $ConfigData['RSSLanguage'];
		$this->RSSGenerator   = $ConfigData['RSSGenerator'];
		$this->RSSProgrammer  = $ConfigData['RSSProgrammer'];
		$this->RSSLink        = $ConfigData['RSSLink'];
	}

	/**
	 * Генерирует и выводит RSS-ленту. Параметры:    
	 *   (array)  $RSSItems       массив с данными о всех записях (items) 
	 * -
	 */
	public function GenerateRSS ($RSSItems) {
		$this->OpenHeader ();
		$this->SetMainData ();
		foreach ($RSSItems as $RSSItemKey => $RSSItemValue) {
			$this->AddItem ($RSSItemValue);
		}
		$this->CloseHeader ();
		$this->ShowRSS ();
	}

	/**
	 * Добавляет запись в RSS-ленту. Параметры:
	 *   (array)  $ItemArray      массив с данными записи
	 * -
	 */
	public function AddItem ($ItemArray) {
		$ItemTitle = $ItemArray['ItemTitle'];
		$ItemTitle = $this->CleanCDATA ($ItemTitle);
		$ItemLink  = $ItemArray['ItemLink'];
		$ItemGUID  = $ItemArray['ItemLink'];
		$ItemDate  = $this->RSSDate ($ItemArray['ItemDate']);
		$ItemDescr = $ItemArray['ItemDescr'];
		$ItemDescr = $this->CleanCDATA ($ItemDescr);
		
		$this->RSSString .= '<item>';
		$this->RSSString .= '<title>'                . $ItemTitle . '</title>';
		$this->RSSString .= '<link>'                 . $ItemLink  . '</link>';
		$this->RSSString .= '<description><![CDATA[' . $ItemDescr . ']]></description>';
		$this->RSSString .= '<pubDate>'              . $ItemDate  . '</pubDate>';
		$this->RSSString .= '<guid>'                 . $ItemGUID  . '</guid>';
		$this->RSSString .= '</item>';
	}
	
	/**
	 * Добавляет описание RSS. Параметры:
	 *   -
	 * -
	 */
	public function SetMainData () {
		$RSSTitle       = $this->RSSTitle;
		$RSSLink        = $this->RSSLink;
		$RSSDescription = $this->RSSDescription;
		$RSSLanguage    = $this->RSSLanguage;
		$RSSLastDate    = $this->RSSLastDate;
		$RSSLastDate    = $this->RSSDate ($RSSLastDate);
		$RSSGenerator   = $this->RSSGenerator;
		$RSSProgrammer  = $this->RSSProgrammer;

		$this->RSSString .= '<title>'         . $RSSTitle       . '</title>';
		$this->RSSString .= '<link>'          . $RSSLink        . '</link>';
		$this->RSSString .= '<description>'   . $RSSDescription . '</description>';
		$this->RSSString .= '<language>'      . $RSSLanguage    . '</language>';
		$this->RSSString .= '<generator>'     . $RSSGenerator   . '</generator>';
		$this->RSSString .= '<webMaster>'     . $RSSProgrammer  . '</webMaster>';
		$this->RSSString .= '<lastBuildDate>' . $RSSLastDate    . '</lastBuildDate>';
	}

	/**
	 * Добавляет заголовок RSS. Параметры:
	 *   -
	 * -
	 */
	public function OpenHeader () {
		$this->RSSString .= '<?xml version="1.0" encoding="utf-8"?>';
		$this->RSSString .= '<rss version="2.0">';
		$this->RSSString .= '<channel>';
	}

	/**
	 * Закрывает заголовок RSS. Параметры:
	 *   -
	 * -
	 */
	public function CloseHeader () {
		$this->RSSString .= '</channel>';
		$this->RSSString .= '</rss>';
	}

	/**
	 * Выводит на экран RSS-ленту. Параметры:
	 *   -
	 * -
	 */
	public function ShowRSS () {
		Header ('Content-type: text/xml; charset=utf-8');
		echo ($this->RSSString);
	}

	/**
	 * Преобразует дату в RSS-формат. Параметры:
	 *   (string)  $DateString    строка даты
	 * (string) $Result
	 */
	public function RSSDate ($DateString) {
		$Result = date (DATE_RSS, (int)$DateString);
		return ($Result);
	}

	/**
	 * Очищает текст для CData. Параметры:
	 *   (string)  $String        строка текста
	 * (string) $Result
	 * ПРИМЕЧАНИЕ: ф-ция взята из исходников fluxbb 1.4.3!!!!!!!
	 */
	public function CleanCDATA ($String) {
		$Result = str_replace(']]>', ']]&gt;', $String);
		return ($Result);
	}

}

?>