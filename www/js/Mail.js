/***Mail***/
jQuery(document).ready(function($) {
	$("#MailForm").submit(function() {
		//mail content
		if (javascriptStrlen($('#MailContentText').val())<1) {
			alert(ToolEmptyMailLetter);
			return false;
		}

		//captcha
		if (IsCptch == true) {
			if (javascriptStrlen($('#MailCaptcha').val())<1) {
				alert(EmptyCaptcha);
				return false;
			}
		}
		
		return true;
	});
});