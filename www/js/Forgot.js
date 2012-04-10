/***Forgotten password***/
jQuery(document).ready(function($) {
	$("#ForgotForm").submit(function() {
		//Login
		var Login = $('#ForgotLoginID').val();
		if (javascriptStrlen(Login)<1) {
			alert(ForgotEmptyLogin);
			return false;
		}
		else {
			//Mail
			var Mail = $('#ForgotMailID').val();
			if (javascriptStrlen(Mail)<1) {
				alert(ForgotEmptyMail);
				return false;
			}
		}
		return true;
	});
});