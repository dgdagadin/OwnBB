/***Authorization***/
jQuery(document).ready(function($) {
	$("#LoginForm").submit(function() {
		var Login = $('#LoginID').val();
		if (javascriptStrlen(Login)<1) {
			alert(LoginEmptyLogin);
			return false;
		}
		else {
			var Pass = $('#PasswordID').val();
			if (javascriptStrlen(Pass)<1) {
				alert(LoginEmptyPass);
				return false;
			}
		}
		return true;
	});
});