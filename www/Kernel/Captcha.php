<?php

//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//подключения
include (OBB_KERNEL_DIR . '/Service.php');
include (OBB_KERNEL_DIR . '/OwnBB.Captcha.class.php');

//создание конфигурационного массива
$Config_Captcha = array ('Width'       => OBB_CAPTCHA_WIDTH, //ширина капчи
						'Height'       => OBB_CAPTCHA_HEIGHT, //высота капчи
						'DotFontSize'  => OBB_CAPTCHA_DOT_FONT_SIZE, //размер точек на фоне
						'MainFontSize' => OBB_CAPTCHA_MAIN_FONT_SIZE, //размер главных символов
						'NumDots'      => OBB_CAPTCHA_NUM_DOTS, //к-во точек на фоне
						'NumLines'     => OBB_CAPTCHA_NUM_LINES, //к-во линий на фоне
						'FontPath'     => OBB_CPATCHA_FONT_PATH,//путь к папке с шрифтами
						'NumLetters'   => OBB_CAPTCHA_NUM_LETTERS //к-во основных символов
						);

$Captcha = new OwnBB_Captcha ($Config_Captcha);
$Captcha->GenerateCapcha ();
exit ();

?>