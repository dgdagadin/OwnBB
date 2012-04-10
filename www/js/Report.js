/***Report***/
jQuery(document).ready(function($) {
	$("#ReportForm").submit(function() {
		//report content
		if (javascriptStrlen($('#ReportReasonText').val())<1) {
			alert(ToolEmptyReportReason);
			return false;
		}

		//captcha
		if (IsCptch == true) {
			if (javascriptStrlen($('#ReportCaptcha').val())<1) {
				alert(EmptyCaptcha);
				return false;
			}
		}
		
		return true;
	});
});