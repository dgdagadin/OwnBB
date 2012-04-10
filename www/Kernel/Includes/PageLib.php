<?php

//постраничный вывод
function PL_PageList ($NumPages, $CurrentPage, $Url, $PageVarName, $NumStart, $NumEnd, $NumRight, $NumLeft) {
	global $ForumLang;

	$LeftRest         = $CurrentPage - 1;
	$DoubleLeftRest   = $CurrentPage - 2;
	$RestCurNum       = $NumPages - $CurrentPage; 
	$NextCurrentPage  = $CurrentPage + 1;
	$NextNextCur_Page = $CurrentPage + 2;

	$CurrentPageTD = '<td class="CurrentPage">' . $CurrentPage . '</td>';
	
	if ($LeftRest < 1) {
		$LeftPart = "";
	}
	else if ($LeftRest >= 1 && $LeftRest < ($NumStart + $NumLeft + 1)) {
		$LeftPart = "";

		for ($i = 1; $i < $CurrentPage; $i++) {
			$LeftPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}
	}
	else if ($LeftRest >= ($NumStart + $NumLeft + 1)) {
		$LeftPart = "";

		for ($i = 1; $i <= $NumStart; $i++) {
			$LeftPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}

		$LeftPart .= "<td class=\"PageDottes\">
						<div class=\"DottesDiv\">...</div>
					</td>";

		for ($i = ($CurrentPage - $NumLeft);  $i < $CurrentPage; $i++) {
			$LeftPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}
	}

	if ($RestCurNum < 1) {
		$RightPart = "";
	}
	else if ($RestCurNum >= 1 && $RestCurNum < ($NumEnd + $NumRight + 1)) {
		$RightPart = "";

		for ($i = $NextCurrentPage; $i <= $NumPages; $i++) {
			$RightPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}
	}
	else if ($RestCurNum >= ($NumEnd + $NumRight + 1)) {
		$RightPart = "";

		for ($i = $NextCurrentPage;  $i <= ($CurrentPage + $NumRight); $i++) {
			$RightPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}

		$RightPart .= "<td class=\"PageDottes\">
							<div class=\"DottesDiv\">...</div>
						</td>";

		for ($i = ($NumPages - $NumEnd + 1); $i <=  $NumPages; $i++) {
			$RightPart .= PL_ReturnHref ($Url, $PageVarName, $i, $i, $ForumLang['Paginator']['ToPageTitle'] . ' ' . $i);
		}
	}

	if ($CurrentPage > 1) {
		$PrevPageVal  = $CurrentPage - 1;
		$PrevPageName = $ForumLang['Paginator']['Prev'];
		$PrevPage = PL_ReturnHref ($Url, $PageVarName, $PrevPageVal, $PrevPageName, $ForumLang['Paginator']['PrevTitle']);
		$StartPageVal  = 1;
		$StartPageName = $ForumLang['Paginator']['Start'];
		$StartPage = PL_ReturnHref ($Url, $PageVarName, $StartPageVal, $StartPageName, $ForumLang['Paginator']['StartTitle']);
	}
	else {
		$PrevPage  = '';
		$StartPage = '';
	}

	if ($CurrentPage < $NumPages) {
		$NextPageVal  = $CurrentPage + 1;
		$NextPageName = $ForumLang['Paginator']['Next'];
		$NextPage = PL_ReturnHref ($Url, $PageVarName, $NextPageVal, $NextPageName, $ForumLang['Paginator']['NextTitle']);
		$EndPageVal  = $NumPages;
		$EndPageName = $ForumLang['Paginator']['End'];
		$EndPage = PL_ReturnHref ($Url, $PageVarName, $EndPageVal, $EndPageName, $ForumLang['Paginator']['EndTitle']);
	}
	else {
		$NextPage = '';
		$EndPage  = '';
	}

	$Return = '<table class="PageTable" cellspacing="2" cellpadding="0" border="0">
					<tr>
						<td class="PageNum">' . $NumPages . ' ' . $ForumLang['Paginator']['Pages'] . '</td>' .$StartPage . $PrevPage . $LeftPart . $CurrentPageTD . $RightPart . $NextPage . $EndPage . '
					</tr>
				</table>';
	return ($Return);
}

//возврат ссылок
function PL_ReturnHref ($Url, $PageVarName, $PageValue, $HrefName, $Title) {
	$Return = "<td class=\"PageHref\"><a title=\"" . $Title . "\" href='" . Defence_HTMLSpecials ($Url . "&" . $PageVarName . "=" . $PageValue) . "'>" . $HrefName . "</a></td>";
	return ($Return);
}

?>