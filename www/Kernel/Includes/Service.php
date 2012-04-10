<?php

//массив для формирования случайной строки
$CharArray = array ('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');

//массив для формирования случайной строки капчи
$CaptchaLetters = array ('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y');

//массив цветов для капчи
$CaptchaColors = array('90', '110', '130', '150', '170', '190', '210', '230', '250');

//массив типов и расширений файлов
$FilesArray = array (
	array ('extension'=>'gif', 
		'mime'=>array ('image/gif'), 
		'image'=>true
		),

	array ('extension'=>'jpeg', 
		   'mime'=>array ('image/jpeg',
						'image/pjpeg'
						), 
		   'image'=>true
		  ),

	array ('extension'=>'jpg', 
		   'mime'=>array ('image/jpg',
						'image/jpeg',
						'image/pjpeg'
						 ), 
		   'image'=>true
		  ),

	array ('extension'=>'png', 
		   'mime'=>array ('image/png',
						'image/x-png'
						 ), 
		   'image'=>true
		  ),

	array ('extension'=>'bmp', 
		   'mime'=>array ('image/bmp',
						'image/x-windows-bmp'
						), 
		   'image'=>true
		  ),

	array ('extension'=>'doc', 
		   'mime'=>array ('application/msword',
						'application/x-msword'), 
		   'image'=>false
		  ),
		  
	array ('extension'=>'xls', 
		   'mime'=>array ('application/vnd.ms-excel', 
						'application/excel',
						'x-excel',
						'x-msexcel'
						 ), 
		   'image'=>false
		  ),
		  
	array ('extension'=>'txt', 
		'mime'=>array ('text/plain'), 
		'image'=>false
	  ),
		  
	array ('extension'=>'zip', 
		   'mime'=>array ('application/x-compressed',
						'application/octet-stream',
						'application/x-compress',
						'application/x-zip-compressed',
						'application/zip',
						'application/x-zip',
						'multipart/x-zip'
					 ), 
		   'image'=>false
		  ),

	array ('extension'=>'rar', 
		   'mime'=>array ('application/x-rar-compressed',
						'application/octet-stream',
						'application/force-download'
						), 
		   'image'=>false
		  ),
);

?>