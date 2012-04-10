<?php

/*********************************************************
 * имя класса: OwnBB_CheckUpload
 * функция класса: загрузка файлов на сервер 
 ********************************************************/

class OwnBB_CheckUpload {

	/*
	//////////////////////
	/////СВОЙСТВА/////////
	//////////////////////
	*/

	/**
	 * массив расширений и их характеристик
	 * (array)
	 */
	var $ExtentionsArray;

	/**
	 * максмальный размер загружаемго файла
	 * (integer)
	 */
	var $MaxFileSize;

	/**
	 * максмальная ширина загружаемой картинки
	 * (integer)
	 */
	var $MaxImageWidth;

	/**
	 * максмальная высота загружаемой картинки
	 * (integer)
	 */
	var $MaxImageHeight;

	/**
	 * является ли файл изображением
	 * (boolean)
	 */
	var $IsImage;

	/**
	 * реальный размер файла
	 * (integer)
	 */
	var $FileTrueSize;

	/**
	 * ширина изображения
	 * (integer)
	 */
	var $ImageWidth;

	/**
	 * высота изображения
	 * (integer)
	 */
	var $ImageHeight;

	/*
	//////////////////////
	/////МЕТОДЫ///////////
	//////////////////////
	*/

	/**
	 * Конструктор класса - Инициализация. Параметры:
	 *   array    $ExtentionsArray     массив расширений и их mime-типов
	 *   integer  $MaxFileSize         максимально допустимый размер загружаемого файла
	 *   integer  $MaxImageWidth       максимально допустимая ширина загружаемой картинки
	 *   integer  $MaxImageHeight      максимально допустимая высота загружаемой картинки
	 * -
	 */
	function __construct ($ExtentionsArray, $MaxFileSize, $MaxImageWidth, $MaxImageHeight) {
		$this->ExtentionsArray = $ExtentionsArray;
		$this->MaxFileSize     = $MaxFileSize;
		$this->MaxImageWidth   = $MaxImageWidth;
		$this->MaxImageHeight  = $MaxImageHeight;
		$this->ImageWidth      = 0;
		$this->ImageHeight     = 0;
	}

	/**
	 * Полная проверка файла. Параметры:
	 *   array    $UploadedFileArray   массив загруженного файла
	 * (array) $Result
	 */
	function FullCheckOfUpload ($UploadedFileArray) {
		$Result = array ();
		$Result['IsTrue'] = false;

		if (!$this->CheckIfUploaded ($UploadedFileArray['tmp_name'])) {
			$Result['Error']  = 'image_not';
			return ($Result);
		}

		$FileName = $UploadedFileArray['name'];
		$TMPName  = $UploadedFileArray['tmp_name']; 
		$FileType = $UploadedFileArray['type'];
		$FileExtension = $this->GetFileExtension ($FileName);

		if (!$this->CheckNumDots ($FileName)) {
			$Result['Error'] = 'too_many_dots';
			return ($Result);
		}

		if (!$this->CheckFileSize ($TMPName)) {
			$Result['Error']  = 'bad_size';
			return ($Result);
		}

		if (!$this->CheckFileType ($FileExtension, $FileType)) {
			$Result['Error'] = 'bad_file_type';
			return ($Result);
		}

		if ($this->IsImage) {
			if (!$this->CheckIfIsImage ($TMPName)) {
				$Result['Error'] = 'image_not';
				return ($Result);
			}

			if (!$this->CheckImageSize ()) {
				$Result['Error'] = 'image_bad_size';
				return ($Result);
			}
		}

		$Result['IsTrue'] = TRUE;
		$Result['Ext']    = $FileExtension;
		$Result['Name']   = $FileName;
		$Result['Type']   = $FileType;
		$Result['Data']   = $TMPName;
		$Result['Size']   = $this->FileTrueSize;
		$Result['Image']  = $this->IsImage;
		if ($this->IsImage) {
			$Result['Width']  = $this->ImageWidth;
			$Result['Height'] = $this->ImageHeight;
		}
		return ($Result);
	}

	/**
	 * Проверка, загружен ли файл на сервер. Параметры:
	 *   string   $TMPName             название временного файла
	 * (boolean) $Result
	 */
	function CheckIfUploaded ($TMPName) {
		if (!is_uploaded_file ($TMPName)) {
			$Result = FALSE;
		}
		else {
			$Result = TRUE;
		}
		return ($Result);
	}

	/**
	 * Проерка на количество точек в имени файла. Параметры:
	 *   string   $FileName            название файла
	 * (boolean) $Result
	 */
	function CheckNumDots ($FileName) {
		$FileNameArray = explode ('.', $FileName);
		$ElementsCount = sizeof ($FileNameArray);
		$NumOfDots = $ElementsCount - 1;
		if ($NumOfDots > 1) {
			$Result = FALSE;
		}
		else {
			$Result = TRUE;
		}
		return ($Result);
	}

	/**
	 * Сравнение типа файла с допустимым. Параметры:
	 *   string   $FileExtension       расширение файла
	 * (boolean) $Result
	 */
	function CheckFileType ($FileExtension, $FileType) {
		$ExtensionsArray = $this->ExtentionsArray;
		$FileMimeArray = array ();
		foreach ($ExtensionsArray as $ExtensionKey => $ExtensionValue) {
			$Extension = $ExtensionValue['extension'];
			$IsImage   = $ExtensionValue['image'];
			$MimeArray = $ExtensionValue['mime'];

			$this->IsImage = $IsImage;

			if ($FileExtension == $Extension) {
				$FileMimeArray = $MimeArray;
				break;
			}
		}

		if (sizeof ($FileMimeArray) < 1) {
			$Result = FALSE;
		}
		else {
			if (!in_array ($FileType, $FileMimeArray)) {
				$Result = FALSE;
			}
			else {
				$Result = TRUE;
			}
		}
		return ($Result);
	}

	/**
	 * Сравнение размера файла с допустимыми. Параметры:
	 *   string   $TMPName             название временного файла
	 * (boolean) $Result
	 */
	function CheckFileSize ($TMPName) {
		$FileTrueSize = filesize ($TMPName);
		if ($FileTrueSize == 0 || $FileTrueSize > $this->MaxFileSize) {
			$Result = FALSE;
		}
		else {
			$this->FileTrueSize = $FileTrueSize;
			$Result = TRUE;
		}
		return ($Result);
	}

	/**
	 * Проверка, является ли файл изображением. Параметры:
	 *   string   $TMPName             название временного файла
	 * (boolean) $Result
	 */
	function CheckIfIsImage ($TMPName) {
		$ImageSizeArray = getimagesize ($TMPName);
		if (!$ImageSizeArray) {
			$Result = FALSE;
		}
		else {
			$ImgWidth  = $ImageSizeArray[0];
			$ImgHeight = $ImageSizeArray[1];
			$this->ImageWidth  = $ImgWidth;
			$this->ImageHeight = $ImgHeight;
			$Result = TRUE;
		}
		return ($Result);
	}

	/**
	 * Проверка на максимально допустимые габариты изображения. Параметры:    
	 *   -
	 * (boolean) $Result
	 */
	function CheckImageSize () {
		$MaxImageHeight = $this->MaxImageHeight;
		$MaxImageWidth  = $this->MaxImageWidth;
		$ImageHeight    = $this->ImageHeight;
		$ImageWidth     = $this->ImageWidth;

		if ($ImageHeight > $MaxImageHeight || $ImageWidth > $MaxImageWidth) {
			$Result = FALSE;
		}
		else {
			$Result = TRUE;
		}
		return ($Result);
	}

	/**
	 * Получение расширения файла. Параметры:    
	 *   string   $FileName            название файла
	 * (string) $Result
	 */	
	function GetFileExtension ($FileName) {
		$FilePathParts  = pathinfo ($FileName);
		$Result         = isset ($FilePathParts['extension']) ? Main_Strtolower ($FilePathParts['extension']) : '';
		return ($Result);
	}


}

?>