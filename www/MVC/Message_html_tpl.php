<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Expires" content="Mon, 26 Jul 2004 05:00:00 GMT" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Cache-Control" content="no-cache" />
		<meta http-equiv="Cache-Control" content="no-store" />
		<meta http-equiv="Cache-Control" content="0" />
		<meta name="document-State" content="dynamic" />
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
		<title><?php echo $ForumLang['MessageTitle']; ?></title>
		<link rel="stylesheet" type="text/css" href="<?php echo OBB_CSS_DIR; ?>/error.css" />
		<script type="text/javascript">
			function locate(){
				document.location.href="<?php echo $RedirectURL; ?>";
			}
			setTimeout("locate()", 5000)
		</script>
	</head>
	<body>
		<div class="MessageContainer">
			<table style="width:100%;" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td class="MessageTop">
<?php echo $ForumLang['MessageTitle']; ?>
					</td>
				</tr>
				<tr>
					<td class="MessageData">
						<a href="<?php echo $RedirectURL; ?>"><?php echo $InfoBox; ?></a>
					</td>
				</tr>
				<tr>
					<td class="MessageBottom" colspan="1">
						<div><!-- --></div>
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>