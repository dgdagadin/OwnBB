/***Profile***/
jQuery(document).ready(function($) {
	$("#ProfileForm").submit(function() {
		//pass
		if (IsPsswrd == true && javascriptStrlen($('#ProfilePass').val())>0) {
			if (javascriptStrlen($('#ProfileRepeatPass').val())<1) {
				alert(ProfileEmptyRepeatPass);
				return false;
			}
		}

		//birth date
		if (javascriptStrlen($('#ProfileBirthDate').val())<1) {
			alert(ProfileEmptyDate);
			return false;
		}

		//mail
		if (javascriptStrlen($('#ProfileMail').val())<1) {
			alert(ProfileEmptyMail);
			return false;
		} 

		//captcha
		if (IsCptch == true) {
			if (javascriptStrlen($('#ProfileCaptcha').val())<1) {
				alert(EmptyCaptcha);
				return false;
			}
		}

		return true;
	});
});