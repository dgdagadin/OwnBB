/***Editing post/theme***/
jQuery(document).ready(function($) {
	$("#EditForm").submit(function() {
		var MessageAlert = '';
		var CaptchaAlert = '';

		//Post
		var Post = $('#PostContent').val();
		if (javascriptStrlen(Post)<1) {
			MessageAlert = AddPostField;
		}

		//Captcha code
		if (IsCptch == true) {
			var CaptchaCode = $('#EditCapcha').val();
			if (javascriptStrlen(CaptchaCode)<1) {
				CaptchaAlert = EmptyCaptcha;
			}
		}

		if (javascriptStrlen(MessageAlert)>0)      { alert(MessageAlert); return false; } 
		else if (javascriptStrlen(CaptchaAlert)>0) { alert(CaptchaAlert); return false; } 

		return true;
	});
});
