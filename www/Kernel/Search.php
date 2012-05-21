<?php

$a = microtime();
//Проверка определяющей переменной
if (!isset ($VALIDATION) || $VALIDATION <> '1') {
	exit ('Hi, hacker!');
}

//возможно ли производить поиск
if ($_SESSION['UserData']['UserType'] <> 'admin') {
	$ErrorCode = NULL;
	if (!OBB_SEARCH_ALLOWED) {
		$ErrorCode = 'common_no_search';
		$ErrorFlag = 'STANDART';
	}
	else if (!$UserGroups_Permissions['SearchAllowed']) {
		$ErrorCode = 'common_no_access';
		$ErrorFlag = $_SESSION['UserData']['UserType'] == 'guest' ? 'NO_ACCESS' : 'STANDART';
	}
	if (!OBB_Main_IsEmpty ($ErrorCode)) {
		OBB_Main_ShowError ($ErrorCode, $ErrorFlag, $ForumLang['Errors']);
	}
}

//подключение
include (OBB_KERNEL_DIR . '/CacheFunctions.php');
include (OBB_KERNEL_DIR . '/PageLib.php');
include (OBB_KERNEL_DIR . '/OwnBB.BBCode.php');

//Подключение файла языка
include (OBB_LANGUAGE_DIR . '/Search_' . $Config_Lang . '.php');

//////ФУНКЦИИ ПАРСИНГА//////
$bbcode = OBB_BBCode_GetParseStandart ();
///////ФУНКЦИИ ПАРСИНГА//////

//Функция обновления онлайн-информации о пользователе/госте
OBB_Main_UpdateOnlineInfo ($_SESSION['UserData'], $UserIP, 'search');

//массив ощибок
$SearchErrorArray = array ();

//Главная переменнвя вывода
$MainOutput = '';

//навигация
$NavigArray = array (array ($SelfName, $ForumLang['MainPage']));

if (isset ($_GET['search_id'])) {
	//проверка ид-ра поиска
	if (!ctype_digit ($_GET['search_id'])) {
		OBB_Main_ShowError ('search_wrong_id', 'STANDART', $ForumLang['Errors']);
	}
	$SearchID = intval ($_GET['search_id']);

	//проверка на сущ-е такого поиска
	$GetSearchSQL = 'SELECT * FROM search_data WHERE SearchID = \'' . $SearchID . '\'';
	$GetSearchQuery = DB_Query($Config_DBType, $GetSearchSQL, $ForumConnection);
	if (!$GetSearchQuery) {
		OBB_Main_Log ($GetSearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	if (DB_NumRows ($Config_DBType, $GetSearchQuery) == 0) {
		OBB_Main_ShowError ('search_no_search_data_exists', 'STANDART', $ForumLang['Errors']);
	}

	//присвоение
	$SearchRow = DB_FetchAssoc ($Config_DBType, $GetSearchQuery);
	$SearchDate      = $SearchRow['SearchDate'];
	$SearchWord      = $SearchRow['SearchWord'];
	$SearchTrueWord  = $SearchRow['SearchTrueWord'];
	$SearchUser      = $SearchRow['SearchUser'];
	$SearchFullUser  = $SearchRow['SearchFullUser'];
	$SearchMethod    = $SearchRow['SearchMethod'];
	$SearchMethodIn  = $SearchRow['SearchMethodIn'];
	$SearchInForums  = $SearchRow['SearchInForums'];
	$SearchSortBy    = $SearchRow['SearchSortBy'];
	$SearchSortHow   = $SearchRow['SearchSortHow'];
	$SearchHighlight = $SearchRow['SearchHighlight'];
	$SearchGroupID   = $SearchRow['SearchGroupID'];

	//проверка на дату создания поисковой информации
	if ((time () - $SearchDate) > OBB_MAIN_SEARCH_ACTUAL_TIME * 3600) {
		OBB_Main_ShowError ('search_no_actual', 'STANDART', $ForumLang['Errors']);
	}

	//проверка группы - для правильного формирования списка доступных форумов
	if ($_SESSION['UserData']['UserType'] <> 'admin') {
		if (intval ($_SESSION['UserData']['GroupID']) <> intval ($SearchGroupID)) {
			OBB_Main_Redirect ('?action=search&s_id=' . $SearchID);
		}
	}

	//в чем искать (в сообщениях или заголовках тем)
	$SearchInThemes = $SearchMethodIn == '1' ? TRUE : FALSE;

	//условия поиска
	//  -строка форумов поиска
	$ForumsTable = $SearchInThemes ? 'themes' : 'posts';
	$ForumsString = Main_Strlen ($SearchInForums)>0 ? $SearchInForums : "''";
	$ForumsString = ' AND (' . $ForumsTable . '.ForumID IN (' . $ForumsString . '))';

	//  -строка поиска
	$SearchTrueWord = $SearchTrueWord;

	//  -строка слова поиска и/или фильтра по имени пользователя
	$UserAndWordArr = array ();

	//Формирование поискового запроса
	//  -1)фильтр по имени пользователя
	if (Main_Strlen ($SearchUser) > 0) {
		$SearchUserString = Main_Strtolower ($SearchUser);
		if (Main_Strtolower ($SearchFullUser) == 'yes') {
			$SearchUserPercent = '';
		}
		else {
			$SearchUserPercent = '%';
		}
		$UserNameTable = $SearchInThemes ? 'themes' : 'posts';
		$SearchUserString = '(LOWER(' . $UserNameTable . '.UserName) LIKE \'' . $SearchUserPercent . Defence_EscapeString ($Config_DBType, $SearchUserString) . $SearchUserPercent . '\')';
		$UserAndWordArr[] = $SearchUserString;
		$EmptyLogin = false;
	}
	else {
		$EmptyLogin = TRUE;
	}

	//  -2)слово поиска
	if (Main_Strlen ($SearchWord) > 0) {
		//поиск в заголовках тем
		if ($SearchInThemes) {
			switch ($SearchMethod) {
				case "1":
				default: {
					$SearchWordString = Main_Strtolower ($SearchTrueWord);
					$SearchWordString = '(LOWER(ThemeName) LIKE \'%' . Defence_EscapeString ($Config_DBType, $SearchWordString) . '%\')';
					break;
				}

				case "2": 
				case "3": {
					if ($SearchMethod == '2') {
						$SearchWordOperator = 'OR';
					}
					else if ($SearchMethod == '3') {
						$SearchWordOperator = 'AND';
					}

					//разбитие строки поиска в массив
					$TempSearchWordArray = explode (' ', $SearchTrueWord);

					//прогон массива для экранирования спецсимволов против ИНЪЕКЦИИ
					$TrueSearchWordArray = array ();
					foreach ($TempSearchWordArray as $TempKey=>$TempValue) {
						$TempValue = Main_Strtolower ($TempValue);
						$TrueSearchWordArray[] = 'LOWER(ThemeName) LIKE "%' . Defence_EscapeString ($Config_DBType, $TempValue) . '%"';
					}
					$SearchWordString = '(' . implode (' ' . $SearchWordOperator . ' ', $TrueSearchWordArray) . ')';
					break;
				}
			}
		}
		//поиск в сообщениях
		else {
			switch ($SearchMethod) {
				case "1": 
				default: {
					$SearchWordString = Main_Strtolower ($SearchTrueWord);
					$SearchWordString = '\'"' . Defence_EscapeString ($Config_DBType, $SearchWordString) . '"\' IN BOOLEAN MODE';
					break;
				}

				case "2": 
				case "3": {
					if ($SearchMethod == '2') {
						$SearchWordOperator = '';
					}
					else if ($SearchMethod == '3') {
						$SearchWordOperator = '+';
					}

					//деление строки на массив через пробелы
					$TempSearchWordArray = explode (' ', $SearchTrueWord);

					$TrueSearchWordArray = array ();
					foreach ($TempSearchWordArray as $TempKey=>$TempValue) {
						$TempValue = Main_Strtolower ($TempValue);
						$TrueSearchWordArray[] = $SearchWordOperator . Defence_EscapeString ($Config_DBType, $TempValue);
					}

					$SearchWordString = '\'' . implode (' ', $TrueSearchWordArray) . '\' IN BOOLEAN MODE';
					break;
				}
			}

			//match... against...
			$SearchWordString = ' MATCH (search_content.SearchPostContent) AGAINST (' . $SearchWordString . ')';
		}
		$UserAndWordArr[] = $SearchWordString;
	}    
	//Формирование поискового запроса - КОНЕЦ

	//  -3)объединение ключ. слова и фильтра по пользователю
	$UserAndWordCond = implode (' AND ', $UserAndWordArr);

	//  -3)сортировка
	$SortDateField = $SearchInThemes ? 'ThemeDate' : 'PostDate';
	$SortUserField = $SearchInThemes ? 'UserName'  : 'UserName';
	$SortByField   = $SearchSortBy  == '1' ? $SortDateField : $SortUserField;
	$SortHowField  = $SearchSortHow == '1' ? 'ASC' : 'DESC';
$vvvv = microtime();
	//  -4)Определение к-ва записей, найденых при поиске (для постраничной навигации)
	if ($SearchInThemes) {
		$GetNumSQL = 'SELECT COUNT(*) AS SearchNumRecords FROM themes WHERE ' . $UserAndWordCond . $ForumsString;
		$NumSearchPerPage = OBB_SEARCH_THEMES_PER_PAGE;
	}
	else {
		$GetNumSQL = 'SELECT COUNT(*) AS SearchNumRecords FROM search_content LEFT JOIN posts ON search_content.PostID = posts.PostID WHERE ' . $UserAndWordCond . str_replace ('posts', 'search_content', $ForumsString);
		$NumSearchPerPage = OBB_SEARCH_POSTS_PER_PAGE;
	}
	$GetNumQuery = DB_Query($Config_DBType, $GetNumSQL, $ForumConnection);
	if (!$GetNumQuery) {
		OBB_Main_Log ($GetNumSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	//echo microtime()-$vvvv;
	$SearchNumRecords = DB_Result ($Config_DBType, $GetNumQuery, 0, 0);
	$SearchNumPages = $SearchNumRecords > 0 ? ceil ($SearchNumRecords / $NumSearchPerPage) : 1;

	//  5)Oпределение текущей страницы
	$CurrentPage = !isset ($_GET['page']) || !ctype_digit ($_GET['page']) ? 1 : intval ($_GET['page']);
	if ($CurrentPage < 1) {
		$CurrentPage = 1;
	}
	else if ($CurrentPage > $SearchNumPages) {
		$CurrentPage = $SearchNumPages;
	}

	//  -6)Постаничная навигация
	if ($SearchNumRecords > 0) {
		$PageURL = $SelfName . '?action=search&search_id=' . $SearchID;
		$PageList = PL_PageList ($SearchNumPages, $CurrentPage, $PageURL, 'page', '5', '5', '5', '5');
		$PageList = '<div>' . $PageList . '</div>';
	}
	else {
		$PageList = '';
	}

	//  -7)подготавливаем слова для подсветки
	if (Main_Strlen ($SearchTrueWord) > 0) {
		if ($SearchMethod <> '1') {
			$SearchWordArray = explode (' ', $SearchTrueWord);
			$SearchWordArray = array_unique ($SearchWordArray);
			$HighlightArray = array ();
			foreach ($SearchWordArray as $key=>$val) {
				$val = OBB_Search_PrepareToHighlight ($val);
				$HighlightArray[] = $val;
			}
		}
		else {
			$HighlightArray = array (OBB_Search_PrepareToHighlight ($SearchTrueWord));
		}
	}
	else {
		$HighlightArray = array ();
	}

	//  -8)Подготовка имени автора сообщения для подсветки (на всякий случай)
	if (Main_Strlen ($SearchUser) > 0) {
		$HighlightAuthor = array (OBB_Search_PrepareToHighlight ($SearchUser));
	}
	else {
		$HighlightAuthor = array ();
	}

	//  -8)Выборка и вывод результатов поиска
	if ($SearchNumRecords > 0) {
		$SearchHTML = '';

		$SearchStatisticsArray = array (
									'Results'  =>$SearchNumRecords,
									'Phrase'   =>$SearchWord,
									'User'     =>$SearchUser,
									'FullUser' =>$SearchFullUser,
									'Method'   =>$SearchMethod,
									'MethodIn' =>$SearchMethodIn,
									'SortBy'   =>$SearchSortBy,
									'SortHow'  =>$SearchSortHow,
									'Highlight'=> $SearchHighlight
								);

		/*  ЕСЛИ ВЫБРАН ПОИСК В ЗАГОЛОВКАХ ТЕМ */
		if ($SearchInThemes) {
			if ($_SESSION['UserData']['UserType'] <> 'guest') {
				$UserViewField = ', users_to_themes.ViewDate AS SViewDate';
				$UserViewJoin  = 'LEFT JOIN users_to_themes ON themes.ThemeID = users_to_themes.ThemeID AND users_to_themes.UserID = \'' . intval ($_SESSION['UserData']['UserID']) . '\' ';
			}
			else {
				$UserViewField = '';
				$UserViewJoin = '';
			}

			//основной запрос :)
			$MainSearchSQL = 'SELECT themes.ThemeID AS SThemeID,
									themes.Themename AS SThemeName,
									themes.ThemeDate AS SThemeDate,
									themes.ThemeSmile AS SThemeSmile,
									themes.ThemeBlock AS SThemeBlock,
									themes.ThemeNumPosts AS SThemeNumPosts,
									themes.ThemeNumAttaches AS SThNumAttaches, 
									themes.ThemeImportant AS SThemeImportant,
									themes.ThemeUpDate AS SThemeUpDate,
									themes.UpdatePostID AS SUpdatePostID,
									themes.UpdateUserID AS SUpdateUserID,
									themes.ThemeUpdateUserName AS SThemeUpdateUserName,
									themes.UserID AS SUserID,
									themes.UserName AS SUserName,
									themes.ForumID AS SThemeForumID,
									forums_list.ForumName AS SForumName
									' . $UserViewField . '
							FROM themes
							LEFT JOIN forums_list ON forums_list.ForumID = themes.ForumID
							' . $UserViewJoin . '
							WHERE ' . $UserAndWordCond . $ForumsString . ' 
							ORDER BY themes.' . $SortByField . ' ' . $SortHowField . ', themes.ForumID ASC 
							LIMIT ' . $NumSearchPerPage * ($CurrentPage - 1) . ', ' . $NumSearchPerPage;
			$MainSearchQuery = DB_Query($Config_DBType, $MainSearchSQL, $ForumConnection);
			if (!$MainSearchQuery) {
				OBB_Main_Log ($MainSearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			$SearchHTML .= '<table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
								<tr class="MainColumnRow">
									<td colspan="6" class="MainColumnName">
										<span>' . $ForumLang['SearchResultsTitle'] . '</span>
										<a onclick="javascript:showSearchStatistics();" title="' . $ForumLang['SearchResultsStatistics'] . '" href="javascript:void(0);">
											<img alt="" title="' . $ForumLang['SearchResultsStatistics'] . '" src="' . OBB_IMAGE_DIR . '/info_button.png" style="vertical-align:middle;" />
										</a>
									</td>
								</tr>
								<tr>
									<td style="border-top:1px solid #FFFFFF; width:40px;" class="ForumCharter">&nbsp;</td>
									<td style="border-top:1px solid #FFFFFF; width:16px;" class="ForumCharter">&nbsp;</td>
									<td class="ForumCharter" style="border-top:1px solid #FFFFFF;">
										<span class="ThemesTitle">' . $ForumLang['SearchThemeNameTitle']  . '</span>
									</td>
									<td class="ForumCharter" style="border-top:1px solid #FFFFFF; width:185px;">
										<span class="ThemesTitle">' . $ForumLang['SearchForumNameTitle']  . '</span>
									</td>
									<td class="ForumCharter" style="text-align:center; border-top:1px solid #FFFFFF; width:80px;">
										<span class="ThemesPosts">' . $ForumLang['SearchNumPostsTitle']   . '</span>
									</td>
									<td class="ForumCharter"  style="border-top:1px solid #FFFFFF; width:285px;">
										<span class="ThemesTitle">' . $ForumLang['SearchLastUpdateTitle'] . '</span>
									</td>
								</tr>';

			while ($MainSearchRow = DB_FetchAssoc ($Config_DBType, $MainSearchQuery)) {
				$SThemeID        = $MainSearchRow['SThemeID'];
				$SThemeName      = $MainSearchRow['SThemeName'];
				$SThemeDate      = $MainSearchRow['SThemeDate'];
				$SThemeSmile     = $MainSearchRow['SThemeSmile'];
				$SThemeNumPosts  = $MainSearchRow['SThemeNumPosts'];
				$SThemeUpDate    = $MainSearchRow['SThemeUpDate'];
				$SThemeImportant = $MainSearchRow['SThemeImportant'];
				$SThemeAttaches  = $MainSearchRow['SThNumAttaches'];
				$SThemeBlock     = $MainSearchRow['SThemeBlock'];
				$SUpdatePostID   = $MainSearchRow['SUpdatePostID'];
				$SUpdateUserID   = $MainSearchRow['SUpdateUserID'];
				$SUpdateUserName = $MainSearchRow['SThemeUpdateUserName'];
				$SUserID         = $MainSearchRow['SUserID'];
				$SUserName       = $MainSearchRow['SUserName'];
				$SThemeForumID   = $MainSearchRow['SThemeForumID'];
				$SForumName      = $MainSearchRow['SForumName'];
				if ($_SESSION['UserData']['UserType'] <> 'guest') {
					$SViewDate = $MainSearchRow['SViewDate'];
				}
				else {
					$SViewDate = '';
				}

				//Формирование главных эл-тов строки с темой
				//  --1.Иконка состояния темы
				$SearchThemeStatus = Echo_GetThemeIcon ($SThemeNumPosts, $SViewDate, $SThemeUpDate, $SThemeBlock, TRUE);
				//  --1.Иконка состояния темы - конец

				//  --2.Иконка темы
				if ($SThemeSmile <> '0') {
					$ThemeEmotIcon = $SThemeSmile . '.gif';
				}
				else {
					$ThemeEmotIcon = 'default.png';
				}
				$SearchThemeEmotion = '<img alt="" src="' . OBB_IMAGE_DIR . '/ThemeIcons/' . $ThemeEmotIcon . '" />';
				// --2.Иконка темы - конец

				//  --2.Название темы
				$SearchThemeName = Defence_HTMLSpecials ($SThemeName);
				//if (Main_Strlen ($SearchWord <> '' && OBB_SEARCH_HIGHLIGHT && $SearchHighlight == 'yes')) {
				//	//подсветка слов(а) поиска
				//	$SearchThemeName = OBB_Search_Highlight ($HighlightArray, $SearchThemeName, TRUE);
				//}
				$ThemeEscapedHref = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . intval ($SThemeForumID) . '&id_theme=' . intval ($SThemeID));
				$SearchThemeLink = '<a class="ThemeLink" href="' . $ThemeEscapedHref . '">' . $SearchThemeName . '</a>';
				//  --2.Название темы - конец

				//  --3.Автор темы и время ее создания
				//  --3.1 -автор темы
				$SearchUserName = Defence_HTMLSpecials ($SUserName);
				//if (Main_Strlen ($SearchUser) > 0 && OBB_SEARCH_HIGHLIGHT) {
				//	$SearchUserName = preg_replace ('/(' . preg_quote ($SearchUser, "/") . ')/ui', '<font color="red">\1</font>', $SearchUserName);
				//}
				$SUserID = intval ($SUserID);
				if ($SUserID > 0) {
					$AuthorProfileURL = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $SUserID);
					$SUserAStart = '<a title="' . $ForumLang['SearchThemeAuthor'] . '" class="UserNameHref" href="' . $AuthorProfileURL . '">';
					$SUserAEnd   = '</a>';
				}
				else {
					$SUserAStart = '<span title="' . $ForumLang['SearchThemeAuthor'] . '" class="UserNameSpan">';
					$SUserAEnd   = '</span>';
				}
				$SearchUserName = $SUserAStart . $SearchUserName . $SUserAEnd;

				//  --3.2 -время создания темы
				$SearchThemeDate = Main_ConvertDate ($SThemeDate, $ForumLang['DateArray'], 'd.m.Y, H:i');
				$SearchThemeDate = '<acronym class="CreateAcr" title="' . Main_ConvertDate ($SThemeDate, $ForumLang['DateArray']) . '">' . $SearchThemeDate . '</acronym>';
				//  --3.Автор темы и время ее создания - конец

				//  --4.Если тема вaжная
				$SThemeImportant = Main_Strtolower ($SThemeImportant);
				if ($SThemeImportant == 'yes') {
					$SearchThemeImportant = '<img alt="" title="' . $ForumLang['SearchThemeImportant'] . '" class="ThemeIsImportantImg" src="' . OBB_IMAGE_DIR . '/theme_important.png" />';
				}
				else {
					$SearchThemeImportant = '';
				}

				//  --5.К-во вложений
				$ThemeNumAttaches = intval ($SThemeAttaches);
				if ($ThemeNumAttaches > 0) {
					$SearchNumAttaches = '<img alt="" class="NumAttaches" title="' . $ForumLang['SearchNumAttaches'] . ':' . $ThemeNumAttaches . '" src="' . OBB_IMAGE_DIR . '/attach.png" />';
				}
				else {
					$SearchNumAttaches = '';
				}

				//5.Страницы темы (если больше одной)
				$SearchNumPosts = intval ($SThemeNumPosts);
				$NumPagesInTheme = ceil ($SearchNumPosts/OBB_NUM_POSTS_PER_PAGE);
				if ($NumPagesInTheme > 1) {
					$IntForumID = intval ($SThemeForumID);
					$IntThemeID = intval ($SThemeID);
					$SearchPagesURL = $SelfName . '?action=themeview&id_forum=' . $IntForumID . '&id_theme=' . $IntThemeID;
					$SearchThemePages = '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($SearchPagesURL . '&page=1') . '">1</a></span><span class="SmallLink"><a href="' . Defence_HTMLSpecials ($SearchPagesURL . '&page=2') . '">2</a></span>';
					if ($NumPagesInTheme >= 3) {
						$SearchThemePages .= '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($SearchPagesURL . '&page=3') . '">3</a></span>';
						if ($NumPagesInTheme == 4) {
							$SearchThemePages .= '<span class="SmallLink"><a href="' . Defence_HTMLSpecials ($SearchPagesURL . '&page=4') . '">4</a></span>';
						}
						else if ($NumPagesInTheme > 4) {
							$SearchThemePages .= '<span class="Dottes">...</span><span class="SmallLink"><a href="' . Defence_HTMLSpecials ($SearchPagesURL . '&page=' . $NumPagesInTheme) . '">' . $NumPagesInTheme . '</span>';
						}
					}
					$SearchThemePages = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										<a class="ImgHref" href="' . Defence_HTMLSpecials ($SearchPagesURL) . '">
											<img alt="" title="' . $ForumLang['SearchThemesManyPages'] . '" src="' . OBB_IMAGE_DIR . '/pages_icon.gif" />
										</a>' . $SearchThemePages;
				}
				else {
					$SearchThemePages = '';
				}

				//  -Объединение названия темы и имени автора темы
				$SearchThemeUser = '<div class="ThemeNameDiv">
										' . $SearchThemeImportant . $SearchNumAttaches . $SearchThemeLink . '
									</div>
									<div class="ThemeADPDDiv">
										<div class="ThemeAuthorDate">' . $SearchUserName . '&nbsp;-&nbsp;' . $SearchThemeDate . $SearchThemePages . '</div>
									</div>';

				//  --4.Ссылка на форум
				$SearchForumID   = intval ($SThemeForumID);
				$SearchForumName = Defence_HTMLSpecials ($SForumName);
				$SearchForumHref = Defence_HTMLSpecials ($SelfName . '?action=forumview&id_forum=' . $SearchForumID);
				$SearchForumName = '<div style="color:#444; font-weight:normal;">
										<a class="SearchForumName" style="color:#444;" href="' . $SearchForumHref . '">' . $SearchForumName . '</a>
									</div>';
				//  --4.Ссылка на форум - конец

				//  --5.К-во постов
				$SearchNumPosts = intval ($SThemeNumPosts);
				//  --5.К-во постов - конец

				//  --6.Последнее обновление
				$SUpdatePostID = intval($SUpdatePostID);
				$SearchThemeUpDateTime = Main_ConvertDate ($SThemeUpDate, $ForumLang['DateArray']);
				$SearchThemeUpDate = '<acronym class="UpdateAcr" title="' . $SearchThemeUpDateTime . '">' . $SearchThemeUpDateTime . '</acronym>';
				$SearchUpdateUserID = intval ($SUpdateUserID);
				$SearchUpdaterName = Defence_HTMLSpecials ($SUpdateUserName);
				$UpdaterProfileHref = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $SearchUpdateUserID);
				if ($SearchUpdateUserID > 0) {
					$SearchUpdateHrefStart = '<a class="UpdateUserLink" title="' . $SearchUpdaterName . '" href="' . $UpdaterProfileHref . '">';
					$SearchUpdateHrefEnd   = '</a>';
				}
				else {
					$SearchUpdateHrefStart = '<span class="UpdateUserText">';
					$SearchUpdateHrefEnd   = '</span>';
				}
				$SearchUpdaterName = $SearchUpdateHrefStart . $SearchUpdaterName . $SearchUpdateHrefEnd;
				$SearchUpdaterName = '<span class="UpdateAuthorTitle">' . $ForumLang['SearchThemeShortAuthor'] . ':</span>&nbsp;' . $SearchUpdaterName;
				$SearchGotoHref  = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $SearchForumID . '&id_theme=' . intval ($SThemeID) . '&last_post=' . intval($SUpdatePostID));
				$SearchGotoHref .= '#pid' . $SUpdatePostID;
				$SearchGotoHref = '<a title="' . $ForumLang['SearchGotoLastPost'] . '" href="' . $SearchGotoHref . '"><img alt="" title="' . $ForumLang['SearchGotoLastPost'] . '" src="' . OBB_IMAGE_DIR . '/last_post.gif" /></a>';
				$SearchThemeUpdate = '  <div class="UpdateTime">
											' . $SearchGotoHref . '&nbsp;' . $SearchThemeUpDate . '
										</div>
										<div style="width:273px; padding:0; margin:0 !important;" class="UpdateAuthor">
											' . $SearchUpdaterName . '
										</div>';
				//  --6.Последнее обновление - конец

				//Объединение в строку
				$SearchHTML .= '<tr class="ForumMainTR">
									<td class="ForumIconTD">'
										. $SearchThemeStatus  .
									'</td>
									<td class="ForumIconTD">'
										. $SearchThemeEmotion  .
									'</td>
									<td class="ForumNameTD ThemeNameTD">'
										. $SearchThemeUser    . 
									'</td>
									<td style="background:#D9E0EA;" class="ForumNameTD ThemeNameTD">'
										. $SearchForumName    .
									'</td>
									<td class="ForumNumThemesTD">'
										. $SearchNumPosts     .
									'</td>
									<td style="padding:5px 0px 5px 9px !important;" class="ForumUpdateTD">'
										. $SearchThemeUpdate  .
									'</td>
								</tr>';
			}

			$SearchHTML .= '	<tr>
									<td class="ForumsTablePreBottom" colspan="6">
										<div style="height:10px;"><!-- --></div>
									</td>
								</tr>
							</table>';
		}
		/*  ЕСЛИ ВЫБРАН ПОИСК В ЗАГОЛОВКАХ ТЕМ - КОНЕЦ */

		/*  ЕСЛИ ВЫБРАН ПОИСК В СООБЩЕНИЯХ */
		else {$qqqq = microtime ();
			$MainSearchSQL = 'SELECT posts.PostID AS SPostID,
									posts.PostText AS SPostText,
									posts.PostDate AS SPostDate,
									posts.PostSmilesAllow AS SPostSmiles,
									posts.UserID AS SUserID,
									posts.UserName AS SUserName,
									posts.ForumID AS SForumID,
									posts.ThemeID AS SThemeID,
									forums_list.ForumName AS SForumName,
									themes.ThemeName AS SThemeName,
									themes.ThemeAllowSmiles AS SThemeSmiles
							FROM posts
							LEFT JOIN forums_list ON (posts.ForumID = forums_list.ForumID)
							LEFT JOIN themes ON (posts.ThemeID = themes.ThemeID)
							LEFT JOIN search_content ON search_content.PostID = posts.PostID 
							WHERE ' . $UserAndWordCond . $ForumsString . ' 
							ORDER BY posts.' . $SortByField . ' ' . $SortHowField . ', posts.ForumID ASC, posts.ThemeID ASC 
							LIMIT ' . $NumSearchPerPage * ($CurrentPage - 1) . ', ' . $NumSearchPerPage;
			$MainSearchQuery = DB_Query($Config_DBType, $MainSearchSQL, $ForumConnection);
			if (!$MainSearchQuery) {
				OBB_Main_Log ($MainSearchSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			$SearchHTML .= '<table class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
								<tr class="MainColumnRow">
									<td class="MainColumnName">
										<span>' . $ForumLang['SearchResultsTitle'] . '</span>
										<a onclick="javascript:showSearchStatistics();" title="' . $ForumLang['SearchResultsStatistics'] . '" href="javascript:void(0);">
											<img alt="" title="' . $ForumLang['SearchResultsStatistics'] . '" src="' . OBB_IMAGE_DIR . '/info_button.png" style="vertical-align:middle;" />
										</a>
									</td>
								</tr>';

//echo microtime()-$qqqq;
			while ($MainSearchRow = DB_FetchAssoc ($Config_DBType, $MainSearchQuery)) {
				$SPostID      = $MainSearchRow['SPostID'];
				$SPostText    = $MainSearchRow['SPostText'];
				$SPostDate    = $MainSearchRow['SPostDate'];
				$SPostSmiles  = $MainSearchRow['SPostSmiles'];
				$SUserID      = $MainSearchRow['SUserID'];
				$SUserName    = $MainSearchRow['SUserName'];
				$SForumID     = $MainSearchRow['SForumID'];
				$SThemeID     = $MainSearchRow['SThemeID'];
				$SForumName   = $MainSearchRow['SForumName'];
				$SThemeName   = $MainSearchRow['SThemeName'];
				$SThemeSmiles = $MainSearchRow['SThemeSmiles'];

				//преобразование некоторых переменных
				$SPostID  = intval ($SPostID);
				$SUserID  = intval ($SUserID);
				$SForumID = intval ($SForumID);
				$SThemeID = intval ($SThemeID);

				//ВЕРХ
				//  --1)Автор сообщения
				$AuthorName = Defence_HTMLSpecials ($SUserName);
				if ($SUserID) {
					$AuthorProfileLink = Defence_HTMLSpecials ($SelfName . '?action=profile&user_id=' . $SUserID);
					$SearchAuthorHrefStart = '<a class="AuthornameHref" href="' . $AuthorProfileLink . '">';
					$SearchAuthorHrefEnd   = '</a>';
				}
				else {
					$SearchAuthorHrefStart = '<span class="AuthornameSpan">';
					$SearchAuthorHrefEnd   = '</span>';
				}
				//if (Main_Strlen ($SearchUser) > 0 && OBB_SEARCH_HIGHLIGHT) {
				//$UserName = preg_replace ('/(' . preg_quote ($SearchUser, "/") . ')/ui', '<font color="red">\1</font>', $UserName);
				//}
				//$SearchUserName = '<span>' . $ForumLang['SearchThemeAuthor'] . '</span>:&nbsp;' . $SUserHrefS . '<span>' . $UserName . '</span>' . $SUserHrefS;
				$SearchFullAuthorName = $SearchAuthorHrefStart . $AuthorName . $SearchAuthorHrefEnd;
                $SearchAuthorName = '<span class="AuthornameTitle">' . $ForumLang['SearchPostDate'] . ':</span>';
				$SearchAuthorName .= '&nbsp;' . $SearchFullAuthorName;
				//  --1)Автор сообщения - конец

				//  --2)Дата сообщения
				$SearchDateTime  = Main_ConvertDate ($SPostDate, $ForumLang['DateArray'], 'd.m.Y, H:i');
				$SearchPostDate  = '<img class="Image" alt="" title="" src="' . OBB_IMAGE_DIR . '/post_date.png" />';
                $SearchPostDate .= '<span class="SearchDateTitle">' . $ForumLang['SearchPostDateTitle'] . ':</span>';
				$SearchPostDate .= '<span class="SearchDateTime">' . $SearchDateTime . '</span>';
				//  --2)Дата сообщения - конец

				//  --3)Ссылка на сообщение
				$PostPermHref  = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $SForumID . '&id_theme=' . $SThemeID . '&last_post=' . $SPostID);
				$PostPermHref .=  '#pid' . $SPostID;
				$SearchPermalink = '<a class="SearchPostPermalink" href="' . $PostPermHref . '">' . $ForumLang['SearchPostPermalink'] . '</a>';
				//  --3)ССылка на сообщение - конец

				//  --4)Объединение автора сообщения, даты создания сообщения и постоянной ссылки на сообщение
				$SearchPostTop = '  <div style="float:left;">
										' . $SearchAuthorName . '
										' . $SearchPostDate . '
									</div>
									<div style="float:right;">
										' . $SearchPermalink . '
									</div>';
				//  --4)Объединение автора сообщения, даты создания сообщения и постоянной ссылки на сообщение - конец
				//ВЕРХ - КОНЕЦ

				//СЕРЕДИНА
				//  --1)Парсинг сообщения
				if (!OBB_GET_SEARCH_MESSAGE_CACHE) {
					if (OBB_BB_SEARCH_PARSE_ALLOWED) {
						$AllowSmiles = OBB_ALLOW_SMILES && $SThemeSmiles == 'yes' && $SPostSmiles == 'yes';
						$bbcode->SetEnableSmileys($AllowSmiles);
						$SPostText = $bbcode->parse ($SPostText);
					}
					else {
						$SPostText = Defence_HTMLSpecials ($SPostText);
						$SPostText = nl2br ($SPostText);
					}
					//$SPostText = nl2br ($SPostText);
				}
				else {
					$PathToCacheDir = 'PostsCache/forum_' . $SForumID . '/theme_' . $SThemeID;
					$CacheFilePath = OBB_CACHE_DIR . '/' . $PathToCacheDir . '/post_' . $SPostID;
					if (file_exists ($CacheFilePath)) {
						$SPostText = OBB_Cache_GetCacheFile ($CacheFilePath);
					}
					else {
						if (OBB_BB_SEARCH_PARSE_ALLOWED) {
							$AllowSmiles = OBB_ALLOW_SMILES && $SThemeSmiles == 'yes' && $SPostSmiles == 'yes';
							$bbcode->SetEnableSmileys($AllowSmiles);
							$SPostText = $bbcode->parse ($SPostText);
						}
						else {
							$SPostText = Defence_HTMLSpecials ($SPostText);
							$SPostText = nl2br ($SPostText);
						}
						//$SPostText = nl2br ($SPostText);
					}
				}
				//  --1)Парсинг сообщения - конец

				//  --2)Подсветка искомой фразы
				if (Main_Strlen ($SearchTrueWord) > 0 && OBB_SEARCH_HIGHLIGHT && $SearchHighlight == 'yes') {
					$SPostText = OBB_Search_Highlight ($HighlightArray, $SPostText, false);
				}
				//  --2)Подсветка искомой фразы - конец

				//  --3)Заключение сообщения в блок
				$SearchPostText = $SPostText;
				$SearchPostText = '<div class="SearchPostContentDiv">' . $SearchPostText . '</div>';
				//  --3)Заключение сообщения в блок - конец
				
				//  --4)Объединение
				$SearchPostMiddle = $SearchPostText;
				//  --4)Объединение - конец
				//СЕРЕДИНА - КОНЕЦ

				//НИЗ
				//  --1)Ссылка на форум
				$SearchForumLink = Defence_HTMLSpecials ($SelfName . '?action=forumview&id_forum=' . $SForumID);
				$SearchForumName = $ForumName = Defence_HTMLSpecials ($SForumName);
				$SearchForumHref = '<a class="ForumThemeHref" title="" href="' . $SearchForumLink . '">' . $SearchForumName . '</a>';
				//  --1)Ссылка на форум - конец

				//  --2)Ссылка на тему
				$SearchThemeLink = Defence_HTMLSpecials ($SelfName . '?action=themeview&id_forum=' . $SForumID . '&id_theme=' . $SThemeID);
				$SearchThemeName = Defence_HTMLSpecials ($SThemeName);
				$SearchThemeHref = '<a class="ForumThemeHref" title="" href="' . $SearchThemeLink . '">' . $SearchThemeName . '</a>';
				//  --2)Ссылка на тему - конец

				//  --3)Кнопка "Вверх"
				$SearchTopButton = '<a title="' . $ForumLang['SearchTop'] . '" href="javascript:void(0);" onclick="scrollToTop();">
										<img title="' . $ForumLang['SearchTop'] . '" style="vertical-align:middle;" alt="" src="' . OBB_IMAGE_DIR . '/top.png" />
									</a>';
				//  --3)Кнопка "Вверх" - конец

				//  --4)Объединенне
				$SearchPostBottom = '<table style="width:100%;" cellspacing="0" cellpadding="0" border="0">
										<tr>
											<td style="padding:5px 5px 5px 10px;">
												' . $SearchForumHref . '<span class="ForumThemeSeparator">&gt;&gt;</span>' . $SearchThemeHref . '
											</td>
											<td style="padding:5px; width:30px; vertical-align:middle; text-align:center;">
												' . $SearchTopButton . '
											</td>
										</tr>
									</table>';
				//НИЗ - КОНЕЦ

				//РАЗДЕЛИТЕЛЬ
				$SearchResultDivider = '<div style="height:12px;" class="SearchDivider"><!-- --></div>';
				//РАЗДЕЛИТЕЛЬ - КОНЕЦ

				//Оъединение в строку
				$SearchHTML .= ' <tr>
									<td class="SearchAuthorDatePermalink">' . $SearchPostTop . '</td>
								</tr>';
				$SearchHTML .= ' <tr>
									<td class="SearchPostContent">' . $SearchPostMiddle . '</td>
								</tr>';
				$SearchHTML .= ' <tr>
									<td class="SearchForumTheme">' . $SearchPostBottom . '</td>
								</tr>';
				$SearchHTML .= ' <tr>
									<td>' . $SearchResultDivider . '</td>
								</tr>';
			}
			$SearchHTML .= '</table>';
		}
		/*  ЕСЛИ ВЫБРАН ПОИСК В СООБЩЕНИЯХ - КОНЕЦ */
	}
	//если не найдено ни одного эл-та
	else {
		$SearchHTML = '<div class="NoThemes">' . $ForumLang['SearchNoResults'] . '</div>';
	}

	//Вывод результатов поиска

	//JS-массив
	$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', '')
					);

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['SearchResults'], array('forumname'=>$Config_ForumName));

	//  -формирование пункта навигации
	// $NavigWordsArray = array ();
	// $SearchResultsWord = $ForumLang['SearchResults'] . '&nbsp;';
	// if ($SearchInThemes) { $SearchResultsWord .= $ForumLang['SearchResultsThemes']; $SearchObject = 'SearchCountResultsThemes'; }
	// else { $SearchResultsWord .= $ForumLang['SearchResultsPosts']; $SearchObject = 'SearchCountResultsPosts'; }
	// if (Main_Strlen ($SearchWord) > 0) { $NavigWordsArray[] = $ForumLang['SearchResultsContain'] . '&nbsp;"' . Defence_HTMLSpecials ($SearchWord) . '"'; }
	// if (Main_Strlen ($SearchUser) > 0) { $NavigWordsArray[] = $ForumLang['SearchResultsPosted']  . '&nbsp;"' . Defence_HTMLSpecials ($SearchUser) . '"'; }
	// $SearchResultsWord .= ',&nbsp;' . implode ('&nbsp;' . $ForumLang['SearchResultsAnd'] . '&nbsp;', $NavigWordsArray);
	// $SearchResultsWord .= '&nbsp;(' . OBB_Main_ReplaceSymbols ($ForumLang[$SearchObject], array('num'=>$SearchNumRecords)) . ')';
	$SearchResultsWord = $ForumLang['SearchResultsTitle'];

	//  -добавление в навигационный массив
	$NavigArray[] = array('', $SearchResultsWord);

	//  -верх
	$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'ThemeDelimiterDiv');

	//  -постраничная навигация
	$MainOutput .= $PageList;

	//разделительный div
	$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

	//  -основное содержимое
	$MainOutput .= $SearchHTML;

	//разделительный div
	$MainOutput .= '<div class="AddAndPageDivider"><!-- --></div>';

	//  -постраничная навигация (нижняя)
	$MainOutput .= $PageList;

	//  -Футер форума
	$MainOutput .= Echo_PrintFoot ();
	//Вывод результатов поиска - конец
}
else {
	$NavigArray[] = array('', $ForumLang['Search']);

	//переменные
	$SearchMethodArray   = array ('1', '2', '3');
	$SearchMethodInArray = array ('1', '2');
	$SearchSortByArray   = array ('1', '2');
	$SearchSortHowArray  = array ('1', '2');
	$SearchWordVar     = isset ($_POST['SearchWord']) ? trim ($_POST['SearchWord']) : '';
	$SearchUserVar     = isset ($_POST['SearchUser']) ? trim ($_POST['SearchUser']) : '';
	$SearchFullUserVar = isset ($_POST['SearchFullUser']) && $_POST['SearchFullUser']  == TRUE  ? 'yes' : 'no';
	$SearchMethodVar   = isset ($_POST['SearchMethod'])   && in_array ($_POST['SearchMethod'], $SearchMethodArray) ? $_POST['SearchMethod'] : '1';
	$SearchMethodInVar = isset ($_POST['SearchMethodIn']) && in_array ($_POST['SearchMethodIn'], $SearchMethodInArray) ? $_POST['SearchMethodIn'] : '1';
	$SearchInForumsVar = isset ($_POST['SearchInForums']) ? $_POST['SearchInForums'] : array();
	$SearchSortByVar   = isset ($_POST['SearchSortBy']) && in_array ($_POST['SearchSortBy'], $SearchSortByArray) ? $_POST['SearchSortBy'] : '1';
	$SearchSortHowVar  = isset ($_POST['SearchSortHow']) && in_array ($_POST['SearchSortHow'], $SearchSortByArray) ? $_POST['SearchSortHow'] : '1';
	if (isset ($_POST['search'])) {
		$SearchHighlight = isset ($_POST['SearchHighlight']) && $_POST['SearchHighlight'] == TRUE  ? 'yes' : 'no';
	}
	else {
		$SearchHighlight = 'yes';
	}
	
	//если нажата кнопка "Submit"
	if (isset ($_POST['search'])) {
		//проверка
		//1)промежутки между поиском
		//  --если не администратор
		//  --если включены интервалы времени между поисками по форуму
		if ($_SESSION['UserData']['UserType'] <> 'admin' && OBB_SEARCH_WAIT) {
			if ($_SESSION['UserData']['UserType'] == 'guest') {
				$SQL = 'SELECT GuestLastSearch FROM guest_activity WHERE GuestIPAddress = \'' . Defence_EscapeString ($Config_DBType, $UserIP) . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$LastSearch = DB_Result ($Config_DBType, $Query, 0, 0);
				$TrueSearchTime = time() - $LastSearch;
				$SearchTimeLimit = OBB_GUEST_NEW_SEARCH;
			}
			else {
				$SQL = 'SELECT UserLastSearch FROM user_activity WHERE UserID = \'' . intval ($_SESSION['UserData']['UserID']) . '\'';
				$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
				if (!$Query) {
					OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
				}
				$LastSearch = DB_Result ($Config_DBType, $Query, 0, 0);
				$SearchTimeLimit = OBB_USER_NEW_SEARCH;
			}
		}
		else {
			$LastSearch = NULL;
			$SearchTimeLimit = 0;
		}
		$TrueSearchTime = time() - $LastSearch;
		if (!OBB_Main_IsEmpty ($LastSearch) && $TrueSearchTime < $SearchTimeLimit) {
			$Time = $SearchTimeLimit - $TrueSearchTime;
			$SearchBadTime = OBB_Main_ReplaceSymbols ($ForumLang['SearchErrors']['BadSearchTime'], array('num_seconds'=>$Time));
			$SearchErrorArray[] = $SearchBadTime;
		}
		else {
			if (Main_Strlen ($SearchUserVar) == 0 && Main_Strlen ($SearchWordVar) == 0) {
				$SearchErrorArray[] = $ForumLang['SearchErrors']['BadSearchConditions'];
			}
			else {
			//2)логин
				if (Main_Strlen ($SearchUserVar) > 0) {
					$RegPattern = '/^[_а-яА-ЯёЁa-z0-9][-_а-яА-ЯёЁa-z0-9]*$/iu';
					if (!preg_match ($RegPattern, $SearchUserVar)) {
						$SearchErrorArray[] = $ForumLang['SearchErrors']['BadUserNameSymbols'];
					}
					else {
						if (Main_Strlen ($SearchUserVar) > OBB_MAX_LOGIN_LENGTH) {
							$SearchErrorArray[] = $ForumLang['SearchErrors']['BadUserNameLength'];
						}
					}
				}

				//3)Строка поиска
				if (Main_Strlen ($SearchWordVar) > 0) {
					//проверка на наличие вредоносных символов
					if (preg_match ('/[-+<>()*~]/ui', $SearchWordVar)) {
						$SearchErrorArray[] = $ForumLang['SearchErrors']['BadSymbols'];
					}
					else {
						//проверяем длину поисковой фразы
						if (Main_Strlen ($SearchWordVar) < OBB_MIN_FULL_SEARCH_WORD || Main_Strlen ($SearchWordVar) > OBB_MAX_SEARCH_WORD) {
							$SearchWordError = OBB_Main_ReplaceSymbols ($ForumLang['SearchErrors']['BadSearchWordLength'], array('min'=>OBB_MIN_FULL_SEARCH_WORD, 'max'=>OBB_MAX_SEARCH_WORD));
							$SearchErrorArray[] = $SearchWordError;
						}
						else {
							//если И/ИЛИ - длину отдельных слов
							if ($SearchMethodVar == '2' || $SearchMethodVar == '3') {
								$TempSearchWordArray = preg_split ('/[\s]+/', $SearchWordVar);
								$SearchOneWordError = OBB_Main_ReplaceSymbols ($ForumLang['SearchErrors']['BadOneWordLength'], array('min'=>OBB_MIN_ONE_SEARCH_WORD));
								$GoodWordLength = TRUE;
								foreach ($TempSearchWordArray as $key=>$val) {
									if (Main_Strlen ($val) < OBB_MIN_ONE_SEARCH_WORD) {
										$GoodWordLength = false;
										break;
									}
								}

								if (!$GoodWordLength) {
									$SearchErrorArray[] = $SearchOneWordError;
								}
							}
						}
					}
				}
			}
		}

		//если ошибок не обнаружено
		if (sizeof ($SearchErrorArray) == 0) {
			//1).подготовка массива форумов для поиска
			//  --подготовка массива ВСЕХ форумов, доступ к которым разрешен данному пользователю
			//  ПРИМЕЧАНИЕ: администратору доступны все форумы
			//  ПРИМЕЧАНИЕ: сейчас форумы берутся из БД, впоследствии - будут браться из кэша
			$TrueForumsArray = array ();
			$TempForumsArray = array ();
			$GetForumsSQL = 'SELECT ForumID, ForumMinStatus, ForumGuestView FROM forums_list';
			$GetForumsQuery = DB_Query($Config_DBType, $GetForumsSQL, $ForumConnection);
			if (!$GetForumsQuery) {
				OBB_Main_Log ($GetForumsSQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}
			while ($Row = DB_FetchAssoc ($Config_DBType, $GetForumsQuery)) {
				$ForumID        = $Row['ForumID'];
				$ForumMinStatus = $Row['ForumMinStatus'];
				$ForumGuestView = $Row['ForumGuestView'];
				$AllowCurrentForum = TRUE;
				if (
					($_SESSION['UserData']['UserType'] == 'guest' && $ForumGuestView == 'no')
					||
					($_SESSION['UserData']['UserType'] <> 'admin' && Access_CheckUserGroup ($ForumMinStatus) == false)
				) 
				{
					$AllowCurrentForum = false;
				}
				if ($AllowCurrentForum) {
					$TempForumsArray[] = intval ($ForumID);
				}
			}

			//   --выбор только тех форумов, которые были выбраны при поиске
			if (!empty ($SearchInForumsVar) && !in_array ('-1', $SearchInForumsVar)) {
				foreach ($SearchInForumsVar as $key=>$val) {
					if (in_array($val, $TempForumsArray)) {
						$TrueForumsArray[] = $val;
					}
				}
			}
			else {
				$TrueForumsArray = $TempForumsArray;
			}

			//  --сериализация строки форума
			$SearchInForumsStr = implode (',', $TrueForumsArray);
			//1).подготовка массива форумов для поиска - КОНЕЦ

			//2).подготовка слов поиска
			if (Main_Strlen ($SearchWordVar) > 0) {
				if ($SearchMethodVar == '1') {
					$SearchWordInsert = $SearchWordVar;
					//убираем лишние пробелы между словами и опасные для FULLTEXT-SEARCH IN BOOL. MODE символы, если только поиск по сообщениям
					if ($SearchMethodInVar == '2') {
						$SearchWordInsert = preg_replace ('/\s{2,}/ui', ' ', $SearchWordVar);
					}
				}
				else {
					//разбиваем фразу поиска на пробелы
					$SearchWordArray = preg_split ('/\s+/ui', $SearchWordVar);

					//собираем фразу без лишних пробелов
					$SearchWordInsert = implode (' ', $SearchWordArray);
				}
			}
			else {
				$SearchWordInsert = '';
			}
			//2).подготовка слов поиска - КОНЕЦ

			//3).добавление данных поиска в таблицу поиска
			$SQL = 'INSERT INTO search_data 
					(SearchDate, 
					SearchWord, 
					SearchTrueWord,
					SearchUser, 
					SearchFullUser, 
					SearchMethod, 
					SearchMethodIn, 
					SearchInForums, 
					SearchSortBy, 
					SearchSortHow,
					SearchHighlight,
					SearchGroupID) 
					VALUES 
					(\'' . time () . '\', 
					\'' . Defence_EscapeString ($Config_DBType, $SearchWordVar) . '\',
					\'' . Defence_EscapeString ($Config_DBType, $SearchWordInsert) . '\',
					\'' . Defence_EscapeString ($Config_DBType, $SearchUserVar) . '\',
					\'' . $SearchFullUserVar . '\',
					\'' . $SearchMethodVar . '\',
					\'' . $SearchMethodInVar . '\',
					\'' . Defence_EscapeString ($Config_DBType, $SearchInForumsStr) . '\',
					\'' . $SearchSortByVar . '\',
					\'' . $SearchSortHowVar . '\',
					\'' . $SearchHighlight . '\',
					\'' . intval ($_SESSION['UserData']['GroupID']) . '\')';
					
			$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//идентификатор поиска
			$LastSearchID = DB_LastID ($Config_DBType, $ForumConnection);

			//обновление последнего поиска гостя/пользователя
			if ($_SESSION['UserData']['UserType'] == 'guest') {
				$SQL = 'UPDATE guest_activity SET GuestLastSearch = \'' . time() . '\'';
			}
			else {
				$SQL = 'UPDATE user_activity SET UserLastSearch = \'' . time() . '\'';
			}
			$Query = DB_Query($Config_DBType, $SQL, $ForumConnection);
			if (!$Query) {
				OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
			}

			//переадресация
			$RedirectURL = '?action=search&search_id=' . intval ($LastSearchID);
			OBB_Main_Redirect ($RedirectURL);
		}
	}

	//массив разделов и форумов для поиска
	//ПРИМЕЧАНИЕ: сейчас данные берутся из БД, впоследствии - из КЭШа
	$ForumsArray = array ();
	$SQL = 'SELECT charters.CharterID AS IDCharter,
				charters.CharterName AS NameCharter,
				forums_list.ForumID AS IdForum,
				forums_list.ForumName AS NameForum
			FROM charters
			INNER JOIN forums_list ON charters.CharterID = forums_list.CharterID
			ORDER BY charters.CharterPosition, charters.CharterID, forums_list.ForumID';
	$ForumListQuery = DB_Query ($Config_DBType, $SQL, $ForumConnection);
	if (!$ForumListQuery) {
		OBB_Main_Log ($SQL . "\r\n" . DB_Error ($Config_DBType), OBB_ERROR_LOG_FILE);
	}
	if (DB_NumRows ($Config_DBType, $ForumListQuery) > 0) {
		while ($ForumListRow = DB_FetchAssoc ($Config_DBType, $ForumListQuery)) {
			//для облегчения
			$IDCharter   = $ForumListRow['IDCharter'];
			$NameCharter = $ForumListRow['NameCharter'];
			$IdForum     = $ForumListRow['IdForum'];
			$NameForum   = $ForumListRow['NameForum'];

			$ForumsArray[$IDCharter.'_'. $NameCharter][$IdForum] = $NameForum;
		}
	}

	//значения элементов формы
	$SearchWordValue     = Defence_HTMLSpecials ($SearchWordVar);
	$SearchMethodVal1    = $SearchMethodVar   == '1'   ? ' selected="selected"' : '';
	$SearchMethodVal2    = $SearchMethodVar   == '2'   ? ' selected="selected"' : '';
	$SearchMethodVal3    = $SearchMethodVar   == '3'   ? ' selected="selected"' : '';
	$SearchUserValue     = Defence_HTMLSpecials ($SearchUserVar);
	$SearchFullUserValue = $SearchFullUserVar == 'yes' ? ' checked="checked"'  : '';
	$SearchMethodInVal1  = $SearchMethodInVar == '1'   ? ' selected="selected"' : '';
	$SearchMethodInVal2  = $SearchMethodInVar == '2'   ? ' selected="selected"' : '';    
	$SearchSortByVal1    = $SearchSortByVar   == '1'   ? ' selected="selected"' : '';
	$SearchSortByVal2    = $SearchSortByVar   == '2'   ? ' selected="selected"' : '';
	$SearchSortHowVal1   = $SearchSortByVar   == '1'   ? ' selected="selected"' : '';
	$SearchSortHowVal2   = $SearchSortByVar   == '2'   ? ' selected="selected"' : '';
	$SearchHighlightVal  = $SearchHighlight   == 'yes' ? ' checked="checked"'  : '';

	//список форумов для поиска
	$ForumsSelectBlock = '<select style="height:150px;width:95%;" class="InpEl InpSelect MultipleSelect" multiple="multiple" name="SearchInForums[]">
										<option' . (in_array ('-1', $SearchInForumsVar) ? ' selected="selected"' : '') . ' value="-1">' . $ForumLang['SearchInAll'] . '</option>';
	foreach ($ForumsArray as $ForumsKey=>$ForumsValue) {
		$ForumsKeyArray = explode ('_', $ForumsKey);
		$CharterIdent = $ForumsKeyArray[0];
		$CharterName  = $ForumsKeyArray[1];
		$OneChartArray = $ForumsValue;

		$ForumsSelectBlock .= '<optgroup label="' . $CharterName . '">';
		foreach ($OneChartArray as $OneChartKey=>$OneChatValue) {
			$ForumsSelectBlock .= '<option' . (in_array ($OneChartKey, $SearchInForumsVar) ? ' selected="selected"' : '') . ' value="' . $OneChartKey . '">' . $OneChatValue . '</option>';
		}
		$ForumsSelectBlock .= '</optgroup>';
	}
	$ForumsSelectBlock .= '</select>';

	//галка "Подсветить поиск" - если разрешена
	if (OBB_SEARCH_HIGHLIGHT) {
		$HighlightCheckbox = '<tr class="SearchOptionsClassHide">
								<td style="border-bottom:1px solid #FFF; vertical-align:top; width:233px; padding-bottom:28px;" class="FormTitleTD">
									<div class="InputTitle">
										' . $ForumLang['SearchAdvancedOptions'] . '
									</div>
								</td>
								<td style="border-bottom:1px solid #FFF; vertical-align:top; padding-bottom:28px;" class="FormInputTD">
									<input id="Label_SearchHighlight"' . $SearchHighlightVal . ' type="checkbox" name="SearchHighlight" />
									<span class="Usual"><label for="Label_SearchHighlight">' . $ForumLang['SearchHighlight'] . '</label></span>
								</td>
							</tr>';
		$SortHowPaddingTD  = '';
		$BorderBottomWhite = '';
	}
	else {
		$HighlightCheckbox = '';
		$SortHowPaddingTD  = ' padding-bottom:28px;';
		$BorderBottomWhite = ' border-bottom:1px solid #FFF;';
	}

	//Search FAQ - описание и рекомендации по поиску
	$SearchFAQArray = array ();

	//  --Главное ограничение
	$SearchFAQ_PhraseOrAndUser = $ForumLang['SearchFAQList']['PhraseOrAndUser'];
	$SearchFAQArray[] = $SearchFAQ_PhraseOrAndUser;

	//  --Выбор форумов для поиска
	$SearchFAQ_MultipleForums = $ForumLang['SearchFAQList']['MultipleForums'];
	$SearchFAQArray[] = $SearchFAQ_MultipleForums;

	//  --Описание способов поиска
	$SearchFAQ_PhraseDescription = $ForumLang['SearchFAQList']['PhraseDescription'];
	$SearchFAQArray[] = $SearchFAQ_PhraseDescription;

	//  --Длина фразы/слов поиска
	$SearchFAQ_PhraseLength = OBB_Main_ReplaceSymbols ($ForumLang['SearchFAQList']['PhraseLength'], array('min'=>OBB_MIN_FULL_SEARCH_WORD,'max'=>OBB_MAX_SEARCH_WORD,'one_min'=>OBB_MIN_ONE_SEARCH_WORD));
	$SearchFAQArray[] = $SearchFAQ_PhraseLength;

	//  --Недопустимые символы во фразе поиска
	$SearchFAQ_PhraseBadSymbols = $ForumLang['SearchFAQList']['PhraseBadSymbols'];
	$SearchFAQArray[] = $SearchFAQ_PhraseBadSymbols;

	//  --Максимальная длина имени пользователя
	$SearchFAQ_LoginLength = OBB_Main_ReplaceSymbols ($ForumLang['SearchFAQList']['LoginLength'], array('max'=>OBB_MAX_LOGIN_LENGTH));
	$SearchFAQArray[] = $SearchFAQ_LoginLength;

	//  --Постоение списка
	$SearchFAQUL = '<ul class="FAQSearchUl">';
	foreach ($SearchFAQArray as $SearchFAQKey=>$SearchFAQValue) {
		$SearchFAQUL .= '<li>' . $SearchFAQValue . '</li>';
	}
	$SearchFAQUL .= '</ul>';
	//Search FAQ - описание и рекомендации по поиску - КОНЕЦ

	///*JS-массив*///
	$JSParametersArray = array ('Action'=>$Action, 'SubAction'=>'search');
	///*JS-массив*///

	//JS-массив
	$JavaScriptArray = array (
						array ('jquery.js', ''),
						array ('functions.js', ''),
						array ('gui.js', ''),
						array ('parameters', $JSParametersArray),
						array ('Search.js', '')
					);

	//заголовок страницы
	$Title = OBB_Main_ReplaceSymbols ($ForumLang['Title']['Search'], array('forumname'=>$Config_ForumName));

	//верх
	$MainOutput .= Echo_PrintHead ($NavigArray, $JavaScriptArray, $Title, 'AddDelimiterDiv');

	//массив ошибок
	if (sizeof ($SearchErrorArray) > 0) {
		$ErrorListBlock = '<div class="ErrorBlockDiv">
								<div class="ErrorBlockTitle">' . $ForumLang['SearchErrors']['ErrorBlockTitle'] . ':</div>';
		foreach ($SearchErrorArray as $ErrorKey=>$ErrorValue) {
			$ErrorListBlock .= '<div class="ErrorItemDiv">' . $ErrorValue . '</div>';
		}
		$ErrorListBlock .= ' </div>
							<div style="height:15px;"><!-- --></div>';
	}
	else {
		$ErrorListBlock = '';
	}
	$MainOutput .= $ErrorListBlock;

	//форма поиска
	$ActionURL = Defence_HTMLSpecials ($SelfName . '?action=search&search_build=1');
	$MainOutput .= '<table style="width:100%;" class="MainForumsTable" cellpadding="0" cellspacing="0" border="0">
						<tr class="MainColumnRow">
							<td colspan="1" class="MainColumnName"><span>' . $ForumLang['Search'] . '</span></td>
						</tr>
						<tr class="ForumMainTR">
							<td style="padding:0;">
								<form style="padding:0; margin:0;" id="SearchForm" action="' . $ActionURL . '" method="post">
									<table style="width:100%;" class="FormsTable" cellspacing="0" cellpadding="0" border="0">
										<tr>
											<td style="border-top:1px solid #FFF; width:233px;" class="ForumCharter">
												<span style="margin-left:10px;" class="ThemesTitle">' . $ForumLang['SearchWhat'] . '</span>
											</td>
											<td style="border-top:1px solid #FFF; text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'SearchWhatClassHide\',\'' . OBB_IMAGE_DIR . '\',\'SearchWhatImgID\');"><img id="SearchWhatImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
											</td>
										</tr>
										<tr class="SearchWhatClassHide">
											<td style="padding-top:6px; padding-bottom:6px; border-top:1px solid #FFFFFF; vertical-align:top; width:233px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchEnterWord'] . '
												</div>
												<div class="InputDescr">
													(' . OBB_Main_ReplaceSymbols ($ForumLang['SearchPhraseLimit'], array('min'=>OBB_MIN_FULL_SEARCH_WORD,'max'=>OBB_MAX_SEARCH_WORD)) . ')
												</div>
											</td>
											<td style="padding-bottom:7px; vertical-align:top; border-top:1px solid #FFFFFF;" class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="SearchWord" type="text" name="SearchWord" maxlength="' . OBB_MAX_SEARCH_WORD . '" value="' . $SearchWordValue . '" />
											</td>
										</tr>
										<tr class="SearchWhatClassHide">
											<td style="padding-top:6px; padding-bottom:12px; vertical-align:top; width:233px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchChooseMethod'] . '
												</div>
											</td>
											<td style="padding-bottom:13px; vertical-align:top;" class="FormInputTD">
												<select style="font-size:13px; width:150px;" class="InpEl" id="SearchMethod" name="SearchMethod">
													<option' . $SearchMethodVal1 . ' value="1">' . $ForumLang['SearchMethodAll'] . '</option>
													<option' . $SearchMethodVal2 . ' value="2">' . $ForumLang['SearchMethodOr']  . '</option>
													<option' . $SearchMethodVal3 . ' value="3">' . $ForumLang['SearchMethodAnd'] . '</option>
												</select>
											</td>
										</tr>
										<tr class="SearchWhatClassHide">
											<td style="padding-top:6px; vertical-align:top; width:233px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchUserFilter'] . '
												</div>
											</td>
											<td style="vertical-align:top;" class="FormInputTD">
												<input style="font-size:13px; width:280px;" class="InpEl InpText" id="SearchLogin" type="text" name="SearchUser" maxlength="' . OBB_MAX_LOGIN_LENGTH . '" value="' . $SearchUserValue . '" />
											</td>
										</tr>
										<tr class="SearchWhatClassHide">
											<td style="vertical-align:middle; width:233px;" class="FormTitleTD">
												&nbsp;
											</td>
											<td style="padding-top:0 !important;vertical-align:top;" class="FormInputTD">
												<div style="margin-top:0px; margin-bottom:20px; " class="AuthRemember">
													<input id="Label_SearchFullUser"' . $SearchFullUserValue . ' type="checkbox" name="SearchFullUser" />&nbsp;&nbsp;<label for="Label_SearchFullUser">' . $ForumLang['SearchFullUserLogin'] . '</label>
												</div>
											</td>
										</tr>
										<tr>
											<td style="border-top:1px solid #FFF; width:233px;" class="ForumCharter">
												<span style="margin-left:10px;" class="ThemesTitle">' . $ForumLang['SearchWhere'] . '</span>
											</td>
											<td style="border-top:1px solid #FFF; text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'SearchWhereClassHide\',\'' . OBB_IMAGE_DIR . '\',\'SearchWhereImgID\');"><img id="SearchWhereImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
											</td>
										</tr>
										<tr class="SearchWhereClassHide">
											<td style="border-top:1px solid #FFFFFF; vertical-align:top; width:233px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchInForums'] . '
												</div>
												<div class="InputDescr">
													(&nbsp;' . $ForumLang['SearchMultipleForums'] . '&nbsp;)
												</div>
											</td>
											<td style="vertical-align:top; border-top:1px solid #FFFFFF;" class="FormInputTD">
													' . $ForumsSelectBlock . '
											</td>
										</tr>
										<tr class="SearchWhereClassHide">
											<td style="padding-top:15px; vertical-align:top; width:233px; padding-bottom:28px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchMethodIn'] . '
												</div>
											</td>
											<td style="padding-top:15px; vertical-align:top; padding-bottom:28px;" class="FormInputTD">
												<select style="font-size:13px; width:170px;" class="InpEl" name="SearchMethodIn">
													<option' . $SearchMethodInVal1 . ' value="1">' . $ForumLang['SearchThemesMethod'] . '</option>
													<option' . $SearchMethodInVal2 . ' value="2">' . $ForumLang['SearchPostsMethod']  . '</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style="border-top:1px solid #FFF; width:233px;" class="ForumCharter">
												<span style="margin-left:10px;" class="ThemesTitle">' . $ForumLang['SearchOther'] . '</span>
											</td>
											<td style="border-top:1px solid #FFF; text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'SearchOptionsClassHide\',\'' . OBB_IMAGE_DIR . '\',\'SearchOptionsImgID\');"><img id="SearchOptionsImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/collapse.gif" /></a>
											</td>
										</tr>
										<tr class="SearchOptionsClassHide">
											<td style="border-top:1px solid #FFF; vertical-align:top; width:233px;" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchSortBy'] . '
												</div>
											</td>
											<td style="border-top:1px solid #FFF; vertical-align:top;" class="FormInputTD">
												<select style="font-size:13px; width:170px;" class="InpEl" name="SearchSortBy">
													<option' . $SearchSortByVal1 . ' value="1">' . $ForumLang['SearchSortByDate']   . '</option>
													<option' . $SearchSortByVal2 . ' value="2">' . $ForumLang['SearchSortByAuthor'] . '</option>
												</select>
											</td>
										</tr>
										<tr class="SearchOptionsClassHide">
											<td style="vertical-align:top; width:233px;' . $SortHowPaddingTD . '" class="FormTitleTD">
												<div class="InputTitle">
													' . $ForumLang['SearchSortHow'] . '
												</div>
											</td>
											<td style="vertical-align:top;' . $SortHowPaddingTD . $BorderBottomWhite . '" class="FormInputTD">
												<select style="font-size:13px; width:160px;" class="InpEl" name="SearchSortHow">
													<option' . $SearchSortHowVal1 . ' value="1">' . $ForumLang['SearchSortHowASC']  . '</option>
													<option' . $SearchSortHowVal2 . ' value="2">' . $ForumLang['SearchSortHowDESC'] . '</option>
												</select>
											</td>
										</tr>
										' . $HighlightCheckbox . '
										<tr>
											<td style="padding:7px; background:#D1DCEB !important; border-bottom:1px solid #FFF; border-top:1px solid #5176B5; width:233px;" class="ForumCharter">
												<span style="margin-left:10px;" class="ThemesTitle">' . $ForumLang['SearchFAQ'] . '</span>
											</td>
											<td style="adding:7px; background:#D1DCEB !important; border-bottom:1px solid #FFF; border-top:1px solid #5176B5; text-align:right; vertical-align:middle;" class="ForumCharter">
												<a href="javascript:void(0);" onclick="javascript:hideCharterForums(\'SearchFAQClassHide\',\'' . OBB_IMAGE_DIR . '\',\'SearchFAQImgID\');"><img id="SearchFAQImgID" alt="" title="" src="' . OBB_IMAGE_DIR . '/expand.gif" /></a>
											</td>
										</tr>
										<tr style="display:none;" class="SearchFAQClassHide">
											<td colspan="2" style="vertical-align:top; padding-bottom:28px;" class="FormInputTD">
												<div style="font-size:13px;">' . $ForumLang['SearchFAQTitle'] . '</div>
												' . $SearchFAQUL . '
											</td>
										</tr>
										<tr>
											<td colspan="2" class="FormInputTD CenterTD">
												<input type="hidden" name="search" value="1" />
												<div id="SubmitButtonDiv">
													<input class="InpButton" id="SearchSubmit" type="submit" name="SearchSubmit" value="' . $ForumLang['SearchSubmit'] . '" />
													<input class="InpButton" type="reset" value="' . $ForumLang['SearchReset'] . '" />
												</div>
											</td>
										</tr>
									</table>
								</form>
							</td>
						</tr>
						<tr>
							<td class="ForumsTableBottom" colspan="1">
								<div><!-- --></div>
							</td>
						</tr>
					</table>';

	//Футер форума
	$MainOutput .= Echo_PrintFoot ();
}

//вывод результатов в браузер
Main_ShowStandartHeader ();
echo $MainOutput;
//echo microtime() - $a;

?>