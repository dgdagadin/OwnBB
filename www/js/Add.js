/***Adding new post/theme***/
jQuery(document).ready(function($) {
	$("#AddForm").submit(function() {
		var GuestAlert     = '';
		var ThemeAlert     = '';
		var MessageAlert   = '';
		var CaptchaAlert   = '';

		//guest login and mail
		if (IsGst == true) {
			var Login = $('#AddGuestLogin').val();
			var Mail  = $('#AddGuestMail').val();
			if (javascriptStrlen(Login)<1) {
				GuestAlert = AddEmptyLogin;
			}
			else {
				if (javascriptStrlen(Mail)<1) {
					GuestAlert = AddEmptyMail;
				}
			}
		}

		//theme name
		if (IsThm == true) {
			var ThemeName = $('#AddThemeName').val();
			if (javascriptStrlen(ThemeName)<1) {
				ThemeAlert = AddThemeName;
			}
		}

		var Post = $('#PostContent').val();
		if (javascriptStrlen(Post)<1) {
			MessageAlert = AddPostField;
		}

		if (IsCptch == true) {
			var CaptchaCode = $('#AddCaptcha').val();
			if (javascriptStrlen(CaptchaCode)<1) {
				CaptchaAlert = EmptyCaptcha;
			}
		}

		if (javascriptStrlen(GuestAlert)>0)        { alert(GuestAlert);   return false; }
		else if (javascriptStrlen(ThemeAlert)>0)   { alert(ThemeAlert);   return false; } 
		else if (javascriptStrlen(MessageAlert)>0) { alert(MessageAlert); return false; } 
		else if (javascriptStrlen(CaptchaAlert)>0) { alert(CaptchaAlert); return false; } 

		return true;
	});
});