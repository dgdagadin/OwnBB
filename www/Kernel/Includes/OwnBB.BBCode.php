<?php

/****************************************************************************
***Данный файл отвечает за интерпретацию BB-кодов в разрешенные HTML-теги****
***Скрипт основан на библиотеке NBBC (http://nbbc.sourceforge.net)***********
*****************************************************************************/

//ПОДКЛЮЧЕНИЕ БИБЛИОТЕКИ NBBC
require_once (OBB_BB_LIB_DIR . '/nbbc.php');

//Список используемых BB-тегов
// 1)[b][/b]         - жирный шрифт,        заменяется на <span strong></span>
// 2)[i][/i]         - курсив,              заменяется на <span i></span>
// 3)[u][/u]         - подчеркнутый текст,  заменяется на <span u></span>
// 4)[s][/s]         - перечеркнутый текст, заменяется на <span strike></span>
// 5)[code][/code]   - код,                 заменяется на <div style=\"white-space:pre\"></div>
// 6)[quote][/quote] - цитата,              заменяется на <div></div>
// 7)[url][/url]     - гиперссылка,         заменяется на <a href="\1">(\1 | \2)</a>
// 8)[img][/img]     - картинка,            заменяется на <div style="overflow:auto;"><img src="\1" /></div>
////// 9)[mail][/mail]   - адрес эл. почты,     заменяется на <a href="mailto:\1">(\1 | \2)</a> - ПРИМЕЧАНИЕ - временно удалён
//10)[list][/list]   - список,              заменяется на <ul></ul>
//11)[*]             - элемент списка,      заменяется на <li></li>, используется ТОЛЬКО внутри [list][/list]
//12)[color][/color] - цветной текст,       заменяется на <font color="\1">\2</font>

//ПРИМЕЧАНИЕ - ВСЕ CALLBACK-функции основанны на стандартных ф-циях файла nbbc_lib.php

/****************************
****        Девиз        ****
****************************/
// 1)МАССИВ ТЕГОВ ДЛЯ УДАЛЕНИЯ
$BBCode_Sign_ArrayToDelete = array ('font', 'size', 'sup', 'sub', 'spoiler', 'acronym', 'wiki', 'img', 'rule', 'br', 'left', 'right', 'center', 'indent', 'columns', 'nextcol', 'code', 'quote', 'list', '*', 'mail');
// МАССИВ ТЕГОВ ДЛЯ УДАЛЕНИЯ - КОНЕЦ

// 2)МАССИВ ТЕГОВ
$OBB_SignTags = array (
	'b' => Array(
				'simple_start' => "<span class=\"BBStrong\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'i' => Array(
				'simple_start' => "<span class=\"BBItalic\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'u' => Array(
				'simple_start' => "<span class=\"BBUnderl\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	's' => Array(
				'simple_start' => "<span class=\"BBStrike\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'url' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => 'OBB_BBCode_DoURLSign',
				'class' => 'link',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline'),
				'content' => BBCODE_REQUIRED,
				'content' => BBCODE_REQUIRED,
				'plain_start' => "<a href=\"{\$link}\">",
				'plain_end' => "</a>",
				'plain_content' => Array('_content', '_default'),
				'plain_link' => Array('_default', '_content'),
	),

);
// МАССИВ ТЕГОВ - КОНЕЦ

// 2)CALLBACK-ФУНКЦИИ
//   1.Заменяет [url][/url]
function OBB_BBCode_DoURLSign($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	$url = is_string($default) ? $default : $bbcode->UnHTMLEncode(strip_tags($content));
	if ($bbcode->IsValidURL($url)) {
		return ('<a target="_blank" href="' . htmlspecialchars($url) . '" class="BBUrl">' . $content . '</a>');
	}
	else {
		return (htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']));
	}
}
// CALLBACK-ФУНКЦИИ - КОНЕЦ

// 4)ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC
function OBB_BBCode_GetParseSign () {
	global $BBCode_Sign_ArrayToDelete, $BBCode_Sign_ArrayToDelete, $OBB_SignTags;

	//  --запуск
	$BBResource = new BBCode;

	//  --смайлы
	$BBResource->SetEnableSmileys(false);

	//  --амперсанд
	$BBResource->SetAllowAmpersand (TRUE);

	//  --определение стандартных BB-тегов
	$BBResource->SetTagMarker('[');

	//  --запрет автоконвертации ссылок
	$BBResource->SetDetectURLs(false);

	//  --запрет "легковесного" режима
	$BBResource->SetPlainMode(false);

	$BBResource->SetIgnoreNewlines (true);

	//  --удаление заданных тегов
	if (is_array ($BBCode_Sign_ArrayToDelete)) {
		foreach ($BBCode_Sign_ArrayToDelete as $key=>$val) {
			$BBResource->RemoveRule($val);
		}
	}

	//удаление img, code, quote, list
	$BBResource->RemoveRule('img');
	$BBResource->RemoveRule('code');
	$BBResource->RemoveRule('quote');
	$BBResource->RemoveRule('list');

	//удаление b, i, u, s, url
	$BBResource->RemoveRule('b');
	$BBResource->RemoveRule('i');
	$BBResource->RemoveRule('u');
	$BBResource->RemoveRule('s');
	$BBResource->RemoveRule('url');

	//добавление b, i, u, s, url
	foreach ($OBB_SignTags as $key=>$val) {
		$BBResource->AddRule($key, $val);
	}

	//  --возврат ресурса парсера
	return ($BBResource);
}
// ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC - КОНЕЦ

/****************************
**** СТАНДАРТНЫЕ ФУНКЦИИ ****
****************************/

// 1)CALLBACK-ФУНКЦИИ

//   --1.Заменяет [quote][/quote]
function OBB_BBCode_DoQuoteStandart($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	if (isset($params['name']) && preg_match ('/^[_а-яА-ЯёЁa-z0-9][-_а-яА-ЯёЁa-z0-9]*$/iu', $params['name'])) {
		$title = '<strong>' . htmlspecialchars(trim($params['name'])). '</strong>' . " писал(а)";

		//формат даты: dd.mm.yyyy, hh:mm - В СТРОГОМ ПОРЯДКЕ
		if (isset($params['date']) && preg_match ('/^[0-3][\d][\.][01][\d][\.][12][\d]{3}, [012][\d]\:[0-5][\d]$/', $params['date'])) {
			$title .= " в " . htmlspecialchars(trim($params['date']));
		}

		$title .= ":";
	}
	else if (!is_string($default)) {
		$title = "<strong>Цитата:</strong>";
	}
	else {
		$title = '<strong>' . htmlspecialchars(trim($default)) . '</strong>' . " писал:";
	}

	return ("<div class=\"BBQuote\"><div class=\"Head\">" . $title . "</div><div class=\"Body\">" . $content . "</div></div>");
}

//   --2.Заменяет [list][/list]
function OBB_BBCode_DoListStandart ($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
	   return (TRUE);
	}
	return ("<ul class=\"BBUl\">$content</ul>");
}

//   --3.Заменяет [img][/img]
function OBB_BBCode_DoImageStandart ($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	$content = trim($bbcode->UnHTMLEncode(strip_tags($content)));
	if (preg_match("/\\.(?:gif|jpeg|jpg|jpe|png)$/", $content)) {
		if (preg_match("/^[a-zA-Z0-9_][^:]+$/", $content)) {
			if (!preg_match("/(?:\\/\\.\\.\\/)|(?:^\\.\\.\\/)|(?:^\\/)/", $content)) {
				return (false);
			}
		}
		else if ($bbcode->IsValidURL($content, false)) {
			return ("<img src=\"" . htmlspecialchars($content) . "\" alt=\"" . htmlspecialchars(basename($content)) . "\" class=\"BBImg\" />");
		}
	}

	return (htmlspecialchars($params['_tag']) . htmlspecialchars($content) . htmlspecialchars($params['_endtag']));
}

//   4.Заменяет [url][/url]
function OBB_BBCode_DoURLStandart($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	$url = is_string($default) ? $default : $bbcode->UnHTMLEncode(strip_tags($content));
	if ($bbcode->IsValidURL($url)) {
		return ('<a target="_blank" href="' . htmlspecialchars($url) . '" class="BBUrl">' . $content . '</a>');
	}
	else {
		return (htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']));
	}
}
// CALLBACK-ФУНКЦИИ - КОНЕЦ

// 2)МАССИВ ТЕГОВ
$OBB_StandartTags = array (
	'b' => Array(
				'simple_start' => "<span class=\"BBStrong\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'i' => Array(
				'simple_start' => "<span class=\"BBItalic\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'u' => Array(
				'simple_start' => "<span class=\"BBUnderl\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	's' => Array(
				'simple_start' => "<span class=\"BBStrike\">",
				'simple_end' => "</span>",
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'plain_start' => "<b>",
				'plain_end' => "</b>",
			),

	'img' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => "OBB_BBCode_DoImageStandart",
				'class' => 'image',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'end_tag' => BBCODE_REQUIRED,
				'content' => BBCODE_REQUIRED,
				'plain_start' => "[image]",
				'plain_content' => Array(),
	),

	'color' => Array(
				'mode' => BBCODE_MODE_ENHANCED,
				'allow' => Array('_default' => '/^#?[a-zA-Z0-9._ -]+$/'),
				'template' => '<span class="BBStndrt" style="color:{$_default/tw} !important">{$_content/v}</span>',
				'class' => 'inline',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
			),
	
	'code' => Array(
				'mode' => BBCODE_MODE_ENHANCED,
				'template' => "<div class=\"BBSource\"><div class=\"Head\">Code:</div><div class=\"Body\" style=\"\">{\$_content/v}</div></div>",
				'class' => 'code',
				'allow_in' => Array('listitem', 'block', 'columns'),
				'content' => BBCODE_VERBATIM,
				'before_tag' => "sns",
				'after_tag' => "sn",
				'before_endtag' => "sn",
				'after_endtag' => "sns",
				'plain_start' => "\n<b>Code:</b>\n",
				'plain_end' => "\n",
	),

	'quote' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => "OBB_BBCode_DoQuoteStandart",
				'allow_in' => Array('listitem', 'block', 'columns'),
				'before_tag' => "sns",
				'after_tag' => "sns",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n<b>Quote:</b>\n",
				'plain_end' => "\n",
	),

	'list' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => 'OBB_BBCode_DoListStandart',
				'class' => 'list',
				'allow_in' => Array('listitem', 'block'),
				'before_tag' => "sns",
				'after_tag' => "sns",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n",
				'plain_end' => "\n",
	),

	'url' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => 'OBB_BBCode_DoURLStandart',
				'class' => 'link',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline'),
				'content' => BBCODE_REQUIRED,
				'plain_start' => "<a href=\"{\$link}\">",
				'plain_end' => "</a>",
				'plain_content' => Array('_content', '_default'),
				'plain_link' => Array('_default', '_content'),
	),

	'*' => Array(
				'simple_start' => "<li>",
				'simple_end' => "</li>",
				'class' => 'listitem',
				'allow_in' => Array('list'),
				'end_tag' => BBCODE_OPTIONAL,
				'before_tag' => "s",
				'after_tag' => "s",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n * ",
				'plain_end' => "\n",
			)
);
// МАССИВ ТЕГОВ - КОНЕЦ

// 3)МАССИВ ТЕГОВ, К-РЫЕ НЕОБХОДИМО УДАЛИТЬ ИЗ СТАНДАРТНОЙ БИБЛИОТЕКИ
$BBCode_Standart_ArrayToDelete = array ('font', 'size', 'spoiler', 'acronym', 'wiki', 'rule', 'br', 'left', 'right', 'center', 'indent', 'columns', 'nextcol', 'mail');
// МАССИВ ТЕГОВ, К-РЫЕ НЕОБХОДИМО УДАЛИТЬ ИЗ СТАНДАРТНОЙ БИБЛИОТЕКИ - КОНЕЦ

// 4)ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC
function OBB_BBCode_GetParseStandart () {
	global $BBCode_Standart_ArrayToDelete, $OBB_StandartTags;

	//  --запуск
	$BBResource = new BBCode;

	//  --смайлы
	$BBResource->SetSmileyURL(OBB_SMILES_DIR);
	$BBResource->SetSmileyDir(OBB_SMILES_DIR);

	//  --амперсанд
	$BBResource->SetAllowAmpersand (TRUE);

	//  --определение стандартных BB-тегов
	$BBResource->SetTagMarker('[');

	//  --запрет автоконвертации ссылок
	$BBResource->SetDetectURLs(false);

	//  --запрет "легковесного" режима
	$BBResource->SetPlainMode(false);

	$BBResource->SetIgnoreNewlines (false);

	//  --удаление заданных тегов
	if (is_array ($BBCode_Standart_ArrayToDelete)) {
		foreach ($BBCode_Standart_ArrayToDelete as $key=>$val) {
			$BBResource->RemoveRule($val);
		}
	}

	//удаление img, code, quote, list, b, u, i, s, url, color
	$BBResource->RemoveRule('img');
	$BBResource->RemoveRule('code');
	$BBResource->RemoveRule('quote');
	$BBResource->RemoveRule('list');
	$BBResource->RemoveRule('b');
	$BBResource->RemoveRule('u');
	$BBResource->RemoveRule('i');
	$BBResource->RemoveRule('s');
	$BBResource->RemoveRule('url');
	$BBResource->RemoveRule('color');

	//добавление img, code, quote и list
	foreach ($OBB_StandartTags as $key=>$val) {
		$BBResource->AddRule($key, $val);
	}

	//  --возврат ресурса парсера
	return ($BBResource);
}
// ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC - КОНЕЦ



/****************************
****     RSS ФУНКЦИИ     ****
****************************/

// 1)CALLBACK-ФУНКЦИИ

//   --1.Заменяет [quote][/quote]
function OBB_BBCode_DoQuoteRSS($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	if (isset($params['name']) && preg_match ('/^[_а-яА-ЯёЁa-z0-9][-_а-яА-ЯёЁa-z0-9]*$/iu', $params['name'])) {
		$title = htmlspecialchars(trim($params['name'])) . " писал(а)";

		//формат даты: dd.mm.yyyy, hh:mm - В СТРОГОМ ПОРЯДКЕ
		if (isset($params['date']) && preg_match ('/^[0-3][\d][\.][01][\d][\.][12][\d]{3}, [012][\d]\:[0-5][\d]$/', $params['date'])) {
			$title .= " в " . htmlspecialchars(trim($params['date']));
		}

		$title .= ":";
	}
	else if (!is_string($default)) {
		$title = "Цитата:";
	}
	else {
		$title = htmlspecialchars(trim($default)) . " писал:";
	}

	return ("<div><b>" . $title . "</b><div>" . $content . "</div></div>");
}

//   --2.Заменяет [list][/list]
function OBB_BBCode_DoListRSS ($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
	   return (TRUE);
	}
	return ("<div>" . $content . "</div><br />");
}

//   --3.Заменяет [img][/img]
function OBB_BBCode_DoImageRSS ($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	$content = trim($bbcode->UnHTMLEncode(strip_tags($content)));
	if (preg_match("/\\.(?:gif|jpeg|jpg|jpe|png)$/", $content)) {
		if (preg_match("/^[a-zA-Z0-9_][^:]+$/", $content)) {
			if (!preg_match("/(?:\\/\\.\\.\\/)|(?:^\\.\\.\\/)|(?:^\\/)/", $content)) {
				return (false);
			}
		}
		else if ($bbcode->IsValidURL($content, false)) {
			return ("<div><b>Изображение:</b>\n<img src=\"" . htmlspecialchars($content) . "\" alt=\"" . htmlspecialchars(basename($content)) . "\" /></div>");
		}
	}

	return (htmlspecialchars($params['_tag']) . htmlspecialchars($content) . htmlspecialchars($params['_endtag']));
}

//   --4.Заменяет [url][/url]
function OBB_BBCode_DoURLRSS($bbcode, $action, $name, $default, $params, $content) {
	if ($action == BBCODE_CHECK) {
		return (TRUE);
	}

	$url = is_string($default) ? $default : $bbcode->UnHTMLEncode(strip_tags($content));
	if ($bbcode->IsValidURL($url)) {
		return ('<a target="_blank" href="' . htmlspecialchars($url) . '">' . $content . '</a>');
	}
	else {
		return (htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']));
	}
}
// CALLBACK-ФУНКЦИИ - КОНЕЦ

// 2)МАССИВ ТЕГОВ
$OBB_RSSTags = array (
	'quote' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => "OBB_BBCode_DoQuoteRSS",
				'allow_in' => Array('listitem', 'block', 'columns'),
				'before_tag' => "sns",
				'after_tag' => "sns",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n<b>Quote:</b>\n",
				'plain_end' => "\n",
	),

	'code' => Array(
				'mode' => BBCODE_MODE_ENHANCED,
				'template' => "\n<div><b>Code:</b>\n{\$_content/v}</div>\n",
				'class' => 'code',
				'allow_in' => Array('listitem', 'block', 'columns'),
				'content' => BBCODE_VERBATIM,
				'before_tag' => "sns",
				'after_tag' => "sn",
				'before_endtag' => "sn",
				'after_endtag' => "sns",
				'plain_start' => "\n<b>Code:</b>\n",
				'plain_end' => "\n",
	),

	'img' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => "OBB_BBCode_DoImageRSS",
				'class' => 'image',
				'allow_in' => Array('listitem', 'block', 'columns', 'inline', 'link'),
				'end_tag' => BBCODE_REQUIRED,
				'content' => BBCODE_REQUIRED,
				'plain_start' => "[image]",
				'plain_content' => Array(),
	),

	'list' => Array(
				'mode' => BBCODE_MODE_CALLBACK,
				'method' => 'OBB_BBCode_DoListRSS',
				'class' => 'list',
				'allow_in' => Array('listitem', 'block', 'columns'),
				'before_tag' => "sns",
				'after_tag' => "sns",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n",
				'plain_end' => "\n",
	),

	'*' => Array(
				'simple_start' => "&nbsp;&nbsp;* ",
				'simple_end' => "\n",
				'class' => 'listitem',
				'allow_in' => Array('list'),
				'end_tag' => BBCODE_OPTIONAL,
				'before_tag' => "s",
				'after_tag' => "s",
				'before_endtag' => "sns",
				'after_endtag' => "sns",
				'plain_start' => "\n * ",
				'plain_end' => "\n",
	)
);
// МАССИВ ТЕГОВ - КОНЕЦ

// 3)МАССИВ ТЕГОВ, К-РЫЕ НЕОБХОДИМО УДАЛИТЬ ИЗ СТАНДАРТНОЙ БИБЛИОТЕКИ
$BBCode_RSS_ArrayToDelete = array ('font', 'size', 'spoiler', 'acronym', 'wiki', 'rule', 'br', 'left', 'right', 'center', 'indent', 'columns', 'nextcol', 'mail');
// МАССИВ ТЕГОВ, К-РЫЕ НЕОБХОДИМО УДАЛИТЬ ИЗ СТАНДАРТНОЙ БИБЛИОТЕКИ - КОНЕЦ


// 4)ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC
function OBB_BBCode_GetParseRSS () {
	global $BBCode_RSS_ArrayToDelete, $OBB_RSSTags;

	//  --запуск
	$BBResource = new BBCode;

	//  --смайлы - выключить
	//$BBResource->SetEnableSmileys(false);
	$BBResource->SetSmileyURL(OBB_SMILES_DIR);
	$BBResource->SetSmileyDir(OBB_SMILES_DIR);

	//  --амперсанд
	$BBResource->SetAllowAmpersand (TRUE);

	//  --определение стандартных BB-тегов
	$BBResource->SetTagMarker('[');

	//  --запрет автоконвертации ссылок
	$BBResource->SetDetectURLs(false);

	//  --запрет "легковесного" режима
	$BBResource->SetPlainMode(false);

	$BBResource->SetIgnoreNewlines (true);

	//  --удаление заданных тегов
	if (is_array ($BBCode_RSS_ArrayToDelete)) {
		foreach ($BBCode_RSS_ArrayToDelete as $key=>$val) {
			$BBResource->RemoveRule($val);
		}
	}

	//удаление img, code, quote и list
	$BBResource->RemoveRule('img');
	$BBResource->RemoveRule('code');
	$BBResource->RemoveRule('quote');
	$BBResource->RemoveRule('list');

	//добавление img, code, quote и list
	foreach ($OBB_RSSTags as $key=>$val) {
		$BBResource->AddRule($key, $val);
	}

	//  --возврат ресурса парсера
	return ($BBResource);
}
// ФУНКЦИЯ ОПРЕДЕЛЕНИЯ РЕСУРСА BBCode NBBC - КОНЕЦ

?>