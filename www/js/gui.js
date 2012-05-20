function headerSearch1 (object, Val) {
	//alert($(object).val());
	if ($(object).val() == Val+'...') {
		$(object).val('');
	}
}

function headerSearch2 (object, Val) {
	if ($(object).val() == '') {
		$(object).val(Val+'...');
	}
}

function scrollToTop () {
	$('html,body').animate({scrollTop:0},120);
}

function findUserThemes () {
	$('#ProfileSearchMethodIn').val('1');
	$('#UserSearchFormID').submit();
}

function findUserPosts () {alert('aaa');
	$('#ProfileSearchMethodIn').val('2');
	$('#UserSearchFormID').submit();
}

function addTagBold (FormName, InputName) {
	bbfontstyle('[b]', '[/b]', FormName, InputName);
}

function addTagItalic (FormName, InputName) {
	bbfontstyle('[i]', '[/i]', FormName, InputName);
}

function addTagUnderline (FormName, InputName) {
	bbfontstyle('[u]', '[/u]', FormName, InputName);
}

function addTagStrike (FormName, InputName) {
	bbfontstyle('[s]', '[/s]', FormName, InputName);
}

function addTagURL (FormName, InputName) {
	bbfontstyle('[url]', '[/url]', FormName, InputName);
}

function addTagList (FormName, InputName) {
	bbfontstyle('[list][*]', '[/list]', FormName, InputName);
}

function addTagImg (FormName, InputName) {
	bbfontstyle('[img]', '[/img]', FormName, InputName);
}

function addTagCode (FormName, InputName) {
	bbfontstyle('[code]', '[/code]', FormName, InputName);
}

function addTagQuote (FormName, InputName) {
	bbfontstyle('[quote]', '[/quote]', FormName, InputName);
}

function addTagColor (FormName, InputName, Color) {
	bbfontstyle('[color="'+Color+'"]', '[/color]', FormName, InputName);
}

function addSmilie (FormName, InputName, Smilie) {
	insert_text(Smilie, true, false, FormName, InputName);
}

function popupColorBlock () {
	$('#BBEditor_Smiles').css('display','none');
	$('#BBEditor_Color').toggle();
}

function popupSmileBlock (FormName, InputName, SmilesPath) {
	$('#BBEditor_Color').css('display','none');
	$('#BBEditor_Smiles').toggle();
	if (LoadSmiles) {
		LoadSmiles = false;
		$('#BBEditor_Smiles').load(SmilesPath);
	}
}

function addUserBold (FormName, InputName, UserName) {
	//insert_text('[b]'+UserName+'[/b]', false, false, FormName, InputName);
	bbfontstyle('[b]'+UserName+'[/b]', '', FormName, InputName);
}

function hideCharterForums (Class, ImgPath, ImageID) {
	$('.'+Class).toggle();
	if ($('.'+Class).css('display') == 'none') {
		$('#'+ImageID).attr('src', ImgPath+'/expand.gif');
	}
	else {
		$('#'+ImageID).attr('src', ImgPath+'/collapse.gif');
	}
}

function hideCharterForums2 (Class, ImgPath, ImageID) {
	$('.'+Class).toggle();
	if ($('.'+Class).css('display') == 'none') {
		$('#'+ImageID).attr('src', ImgPath+'/cat_maximize.gif');
	}
	else {
		$('#'+ImageID).attr('src', ImgPath+'/cat_minimize.gif');
	}
}

function mousehoverBBEditorButton (object) {
	$(object).css('cursor', 'pointer');
	$(object).css('background-color', '#E4E8DC');
}

function mouseoutBBEditorButton (object) {
	$(object).css('cursor', 'default');
	$(object).css('background-color', '#FFFFFF');
}

function showSearchStatistics () {
	$('#LightboxDiv').css('display', 'block');
}

function hideSearchStatistics () {
	$('#LightboxDiv').css('display', 'none');
}
