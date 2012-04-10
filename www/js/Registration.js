/***Registration***/
jQuery(document).ready(function($) {
	$("#RegistrationForm").submit(function() {
		//login
		if (javascriptStrlen($('#RegistrationLogin').val())<1) {
			alert(RegEmptyLogin);
			return false;
		}
		
		//pass
		if (IsPsswrd == true) {
			if (javascriptStrlen($('#RegistrationPass').val())<1) {
				alert(RegPasswordIsEmpty);
				return false;
			}
			else {
				if (javascriptStrlen($('#RegistrationRepeatPass').val())<1) {
					alert(RegRepeatPassIsEmpty);
					return false;
				}
			}
		}
		
		//mail
		if (javascriptStrlen($('#RegistrationMail').val())<1) {
			alert(RegEmptyMail);
			return false;
		}
		else {
			if (javascriptStrlen($('#RegistrationRepeatMail').val())<1) {
				alert(RegEmptyRepeatMail);
				return false;
			}
		}
		
		//birth date
		if (javascriptStrlen($('#RegistrationBirthDate').val())<1) {
			alert(RegEmptyDate);
			return false;
		}
		
		//captcha
		if (IsCptch == true) {
			if (javascriptStrlen($('#RegistrationCaptcha').val())<1) {
				alert(EmptyCaptcha);
				return false;
			}
		}
		
		return true;
	});
});