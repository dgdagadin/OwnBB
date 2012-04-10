<?php

$ForumLang = array ();

//общие
$ForumLang['Pages'] = 'Страницы';
$ForumLang['Submit'] = 'Вперед';
$ForumLang['Reset'] = 'Очистить';
$ForumLang['MainPage'] = 'Главная страница';
$ForumLang['AllowCreateThemes'] = 'Вам <span>разрешено</span> создавать темы';
$ForumLang['DisAllowCreateThemes'] = 'Вам <span>запрещено</span> создавать темы';
$ForumLang['AllowCreatePosts'] = 'Вам <span>разрешено</span> оставлять сообщения';
$ForumLang['DisAllowCreatePosts'] = 'Вам <span>запрещено</span> оставлять сообщения';
$ForumLang['AllowEditPosts'] = 'Вам <span>разрешено</span> редактировать сообщения';
$ForumLang['DisAllowEditPosts'] = 'Вам <span>запрещено</span> редактировать сообщения';
$ForumLang['AllowSearch'] = 'Вам <span>разрешено</span> проводить поиск по форуму';
$ForumLang['DisAllowSearch'] = 'Вам <span>запрещено</span> проводить поиск по форуму';
$ForumLang['AllowDeletePosts'] = 'Вам <span>разрешено</span> удалять сообщения';
$ForumLang['DisAllowDeletePosts'] = 'Вам <span>запрещено</span> удалять сообщения';
$ForumLang['YouHaveAllAbilities'] = '<div>Вы имеете <span>неограниченные</span> возможности</div>';
$ForumLang['NoAccess'] = 'У вас нет доступа к этому ресурсу. Войдите или заргистрируйтесь';
$ForumLang['EnterCaptcha'] = 'Введите защитный код';
$ForumLang['CaptchaNoRegister'] = 'Регистр не важен';
$ForumLang['CaptchaTitle'] = 'Защита от спама';
$ForumLang['CloseSmilesWindow'] = 'Закрыть';
$ForumLang['CommonSearch'] = 'Поиск';
$ForumLang['CommonSearchButton'] = 'Искать';
$ForumLang['Errors'] = array (
							  'ErrorTitle'  => 'Ошибка',
							  'ErrorsArray' => array (
													  'common_ip_banned'                 => 'Ваш IP-адрес заблокирован администратором',
													  'common_id_banned_full'            => 'Ваш аккаунт заблокирован администратором',
													  'common_id_banned_add'             => 'Вы забанены на добавление',
													  'common_no_group_access'           => 'Ошибка доступа - недостаточно прав',
													  'common_no_access'                 => 'Ошибка доступа - недостаточчно прав',
													  'common_no_search'                 => 'Администратор отключил поиск по форуму',
													  'common_no_report_group_access'    => 'У вас недостаточно прав для доступа',
													  'themes_wrong_forum'               => 'Идентификатор форума не является числовым',
													  'themes_no_forum'                  => 'Форума не существует. Возможно, его удалил администратор',
													  'posts_wrong_forum_or_theme'       => 'Идентификатор темы или форума не является числовым',
													  'add_wrong_forum_or_theme_or_post' => 'Идентификатор сообщения или темы или форума не является числовым',
													  'add_wrong_theme_or_post'          => 'Идентификатор сообщения или темы не является числовым',
													  'add_no_forum_exist'               => 'Форума не существует. Возможно, его удалил администратор',
													  'add_no_theme_exist'               => 'Темы не существует. Возможно, её удалил администратор',
													  'add_no_post_exist'                => 'Сообщения не существует. Возможно, его удалил администратор',
													  'add_forum_block'                  => 'Форум заблокирован администраторам',
													  'add_theme_block'                  => 'Тема заблокирована администраторам',
													  'add_bad_editer'                   => 'Вы не можете редактировать данное сообщение - недостаточно прав',
													  'add_delete_theme_post'            => 'Вы не можете удалить первое сообщение темы',
													  'download_no_attach'               => 'В данном сообщении нет прикрепления',
													  'download_no_attach_file_exist'    => 'Ошибка - запрашиваемый файл не найден',
													  'reg_no_access'                    => 'Администтратор запретил регистрацию на форуме',
													  'profile_wrong_user_id'            => 'Идентификатор пользователя не является числовым',
													  'profile_no_user'                  => 'Пользователь не найден в базе - возможно, его удалил администратор',
													  'search_no_search_data_exists'     => 'Данная поисковая информация не найдена и/или устарела',
													  'report_report_exist'              => 'Вы уже подавали жалобу на данное сообщение',
													  'report_post_not_exist'            => 'Поста, на который вы хотите пожаловаться, не существует. Возможно, его удалил администратор',
													  'report_wrong_post'                => 'Идентификатор сообщения не является числовым',
													  'mail_no_send_allowed'             => 'Вам не разрешено посылать сообщения этому пользователю'
													  
													  )
							 );


//загрузка файлов на сервер
$ForumLang['Attach'] = array ();
$ForumLang['Attach']['not_upload'] = 'Ошибка загрузки файла на сервер! Повторите попытку.';
$ForumLang['Attach']['too_many_dots'] = 'В имени файла больше одной точки';
$ForumLang['Attach']['bad_extension'] = 'Расширение файла не является допустимым';
$ForumLang['Attach']['bad_mime'] = 'Тип файла не является допустимым';
$ForumLang['Attach']['bad_file_type'] = 'Тип файла не является допустимым';
$ForumLang['Attach']['bad_size'] = 'Размер файла не является допустимым';
$ForumLang['Attach']['image_not'] = 'Файл, заявленный изображением, изображением не является';
$ForumLang['Attach']['image_bad_size'] = 'Габариты изображения не являются допустимыми';

//Lightbox
$ForumLang['Lightbox'] = array ();
$ForumLang['Lightbox']['SearchStatisticsTitle'] = 'Статистика';
$ForumLang['Lightbox']['SearchStatisticsClose'] = 'закрыть';
$ForumLang['Lightbox']['SearchStatisticsTitle2'] = 'Краткая статистика по поиску';
$ForumLang['Lightbox']['SearchPhrase'] = 'Что искали';
$ForumLang['Lightbox']['SearchNum'] = 'Найдено записей';
$ForumLang['Lightbox']['SearchUser'] = 'Фильтр по логину';
$ForumLang['Lightbox']['SearchFullUser'] = 'полное имя';
$ForumLang['Lightbox']['SearchMethod'] = 'Тип поиска';
$ForumLang['Lightbox']['SearchMethod1'] = 'фраза целиком';
$ForumLang['Lightbox']['SearchMethod2'] = 'ИЛИ';
$ForumLang['Lightbox']['SearchMethod3'] = 'И';
$ForumLang['Lightbox']['SearchMethodIn'] = 'Искали в';
$ForumLang['Lightbox']['SearchMethodInPosts'] = 'в сообщениях';
$ForumLang['Lightbox']['SearchMethodInThemes'] = 'в заголовках тем';
$ForumLang['Lightbox']['SearchSortField'] = 'Поле сортировки';
$ForumLang['Lightbox']['SearchSortField2'] = 'логин автора';
$ForumLang['Lightbox']['SearchSortField1'] = 'дата создания';
$ForumLang['Lightbox']['SearchSortHow'] = 'Способ сортировки';
$ForumLang['Lightbox']['SearchSortHow1'] = 'по возрастанию';
$ForumLang['Lightbox']['SearchSortHow2'] = 'по убыванию';
$ForumLang['Lightbox']['SearchHighlight'] = 'Подсветка найденного';
$ForumLang['Lightbox']['SearchHighlightYes'] = 'Да';
$ForumLang['Lightbox']['SearchHighlightNo'] = 'Нет';

//действия пользователей
$ForumLang['UserActions'] = array ();
$ForumLang['UserActions']['add_theme'] = 'добавляет тему';
$ForumLang['UserActions']['add_post'] = 'добавляет сообщение';
$ForumLang['UserActions']['edit_post'] = 'редактирует сообщение';
$ForumLang['UserActions']['delete_post'] = 'удаляет сообщение';
$ForumLang['UserActions']['download_file'] = 'скачивает сообщение';
$ForumLang['UserActions']['main_page'] = 'просматривает главную страницу';
$ForumLang['UserActions']['theme_page'] = 'просматривает сообщения';
$ForumLang['UserActions']['rss_page'] = 'просматирвает RSS-ленту';
$ForumLang['UserActions']['search'] = 'производит поиск по форуму';
$ForumLang['UserActions']['forum_page'] = 'просматривает темы';
$ForumLang['UserActions']['report_post'] = 'производит жалобу на сообщение';
$ForumLang['UserActions']['send_mail'] = 'отправляет письмо по электронной почте';
$ForumLang['UserActions']['read_rules'] = 'просматривает правила форума';
$ForumLang['UserActions']['user_list'] = 'просматривает список пользователей';
$ForumLang['UserActions']['edit_own_profile'] = 'редактирует свой профиль';
$ForumLang['UserActions']['edit_profile'] = 'редактирует профиль пользователя';
$ForumLang['UserActions']['view_profile'] = 'просматирвает профиль пользователя';


//массив даты
$ForumLang['DateArray'] = array ('Monday'   =>'Понедельник',
								'Tuesday'  =>'Вторник',
								'Wednesday'=>'Среда',
								'Thursday' =>'Четверг',
								'Friday'   =>'Пятница',
								'Saturday' =>'Суббота',
								'Sunday'   =>'Воскресение',);
//общие - конец

//навигация
$ForumLang['Navig'] = array ();
$ForumLang['Navig']['Main'] = 'Главная';
$ForumLang['Navig']['Reg'] = 'Регистрация';
$ForumLang['Navig']['Login'] = 'Вход';
$ForumLang['Navig']['Rules'] = 'Правила';
$ForumLang['Navig']['Search'] = 'Поиск';
$ForumLang['Navig']['Profile'] = 'Профиль';
$ForumLang['Navig']['Members'] = 'Пользователи';
$ForumLang['Navig']['Logout'] = 'Выход';
//навигация - конец

//приветствие
$ForumLang['Shalom'] = array ();
$ForumLang['Shalom']['RSS'] = 'RSS Лента';
$ForumLang['Shalom']['RSSTitle'] = 'RSS Лента форума';
$ForumLang['Shalom']['Rules'] = 'Правила';
$ForumLang['Shalom']['RulesTitle'] = 'Правила форума';
$ForumLang['Shalom']['Admin'] = 'Админцентр';
$ForumLang['Shalom']['AdminTitle'] = 'Административный центр форума';
$ForumLang['Shalom']['GuestWelcome'] = 'Здравствуйте';
$ForumLang['Shalom']['Guest'] = 'гость';
$ForumLang['Shalom']['UserWelcome'] = 'Вы вошли как';
$ForumLang['Shalom']['Login'] = 'Вход';
$ForumLang['Shalom']['LoginTitle'] = 'Авторизация на форуме';
$ForumLang['Shalom']['Register'] = 'Регистрация';
$ForumLang['Shalom']['RegisterTitle'] = 'Регистрация на форуме';
$ForumLang['Shalom']['Logout'] = 'Выход';
$ForumLang['Shalom']['LogoutTitle'] = 'Выход с форума';
$ForumLang['Shalom']['Profile'] = 'Профиль';
$ForumLang['Shalom']['ProfileTitle'] = 'Ваш профиль на форуме';

//приветствие - конец

//быстрый переход по форумам
$ForumLang['ForumFastJump'] = 'Быстрый переход по форумам';
$ForumLang['JumpToForum'] = 'Перейти на форум';
$ForumLang['ThemeFastJump'] = 'Быстрый переход по темам';
$ForumLang['JumpToTheme'] = 'Перейти на тему';
$ForumLang['JumpSelectForum'] = 'Выберите форум';
$ForumLang['JumpSelectTheme'] = 'Выберите тему';
//быстрый переход по форумам - конец

//приветствие
$ForumLang['ForumGreeting'] = 'Вы вошли как';
$ForumLang['ForumUser'] = 'пользователь';
$ForumLang['ForumStatuses'] = array ();
$ForumLang['ForumStatuses']['admin'] = 'администратор';
$ForumLang['ForumStatuses']['member'] = 'пользователь';
$ForumLang['ForumStatuses']['guest'] = 'гость';
$ForumLang['CommonLogin'] = 'Войти';
$ForumLang['CommonRegister'] = 'Зарегистрироваться';
$ForumLang['CommonProfile'] = 'Профиль';
$ForumLang['CommonLogout'] = 'Выйти';
//приветствие - конец

//ЗАГОЛОВКИ
$ForumLang['Title'] = array ();
$ForumLang['Title']['Main'] = '{forumname}';
$ForumLang['Title']['Themes'] = '{forumname} - {underforumname} - список тем';
$ForumLang['Title']['Posts'] = '{forumname} - {underforumname} - {themename} - список сообщений';
$ForumLang['Title']['ProfileEdit'] = '{forumname} - профиль пользователя {user} - редактирование';
$ForumLang['Title']['ProfileWatch'] = '{forumname} - профиль пользователя {user} - просмотр';
$ForumLang['Title']['Userlist'] = '{forumname} - список пользователей';
$ForumLang['Title']['Search'] = '{forumname} - поиск по форуму';
$ForumLang['Title']['SearchResults'] = '{forumname} - результаты поиска';
$ForumLang['Title']['Registration'] = '{forumname} - регистрация на форуме';
$ForumLang['Title']['Login'] = '{forumname} - войти на форум';
$ForumLang['Title']['AddTheme'] = '{forumname} - {underforumname} - добавление темы';
$ForumLang['Title']['AddPost'] = '{forumname} - {underforumname} - {themename} - добавление ответа';
$ForumLang['Title']['EditPost'] = '{forumname} - {underforumname} - {themename} - редактирование ответа';
$ForumLang['Title']['DeletePost'] = '{forumname} - {underforumname} - {themename} - удаление ответа';
$ForumLang['Title']['SendMail'] = '{forumname} - отпрака сообщения по электронной почте';
$ForumLang['Title']['Report'] = '{forumname} - пожаловаться на сообщение';
$ForumLang['Title']['Rules'] = '{forumname} - правила форума';
$ForumLang['Title']['Smiles'] = '{forumname} - смайлы';
$ForumLang['Title']['ForgotPass'] = '{forumname} - восстановление пароля';
//ЗАГОЛОВКИ - КОНЕЦ

//JAVASCRIPT
$ForumLang['Javascript']['EmptyCaptcha'] = 'Не заполнено поле "Защита от спама"';
$ForumLang['Javascript']['AddEmptyLogin'] = 'Не заполнено поле "Имя"';
$ForumLang['Javascript']['AddEmptyMail'] = 'Не заполнено поле "Почта"';
$ForumLang['Javascript']['AddEmptyThemeName'] = 'Не заполнено поле "Заголовок темы"';
$ForumLang['Javascript']['AddEmptyPostField'] = 'Не заполнено поле "Сообщение"';
$ForumLang['Javascript']['ForgotEmptyLogin'] = 'Не заполнено поле "Логин"';
$ForumLang['Javascript']['ForgotEmptyMail'] = 'Не заполнено поле "Почта"';
$ForumLang['Javascript']['LoginEmptyLogin'] = 'Не заполнено поле "Логин"';
$ForumLang['Javascript']['LoginEmptyPass'] = 'Не заполнено поле "Пароль"';
$ForumLang['Javascript']['ToolEmptyMailLetter'] = 'Не введено содержимое электронного письма';
$ForumLang['Javascript']['ToolsEnterReportReason'] = 'Не заполнено поле "Причина жалобы на сообщение"';
$ForumLang['Javascript']['PostsEmptyLogin'] = 'Не заполнено поле "Имя"';
$ForumLang['Javascript']['PostsEmptyMail'] = 'Не заполнено поле "Электронная почта"';
$ForumLang['Javascript']['PostsEmptyPostField'] = 'Не заполнено поле "Сообщение"';
$ForumLang['Javascript']['RegEmptyLogin'] = 'Не заполнено поле "Логин"';
$ForumLang['Javascript']['RegEmptyMail'] = 'Не заполнено поле "Электронная почта"';
$ForumLang['Javascript']['RegEmptyRepeatMail'] = 'Не заполнено поле "Повторите электронную почту"';
$ForumLang['Javascript']['RegPasswordIsEmpty'] = 'Не заполнено поле "Пароль"';
$ForumLang['Javascript']['RegRepeatPassIsEmpty'] = 'Не заполнено поле "Повторите пароль"';
$ForumLang['Javascript']['RegEmptyDate'] = 'Не заполнено поле "Дата рождения"';
$ForumLang['Javascript']['ProfileEmptyMail'] = 'Не заполнено поле "Электронная почта"';
$ForumLang['Javascript']['ProfileEmptyDate'] = 'Не заполнено поле "Дата рождения"';
$ForumLang['Javascript']['ProfileEmptyRepeatPass'] = 'Не заполнено поле "Повторите пароль"';
$ForumLang['Javascript']['SearchEmptyFields'] = 'Не введены критерии для поиска - введите слово поиска и/или имя пользователя';
//JAVASCRIPT - КОНЕЦ

//Paginator
$ForumLang['Paginator']['Next'] = '&gt;';
$ForumLang['Paginator']['Prev'] = '&lt;';
$ForumLang['Paginator']['End'] = '»';
$ForumLang['Paginator']['Start'] = '«';
$ForumLang['Paginator']['Pages'] = 'Страниц';
$ForumLang['Paginator']['ToPageTitle'] = 'Перейти на страницу';
$ForumLang['Paginator']['NextTitle'] = 'Перейти на следующую страницу';
$ForumLang['Paginator']['PrevTitle'] = 'Перейти на предыдущую страницу';
$ForumLang['Paginator']['StartTitle'] = 'Перейти в начало';
$ForumLang['Paginator']['EndTitle'] = 'Перейти в конец';
//Paginator

//BBEDITOR
$ForumLang['BBEditor']['Bold'] = 'Жирный';
$ForumLang['BBEditor']['Italic'] = 'Курсив';
$ForumLang['BBEditor']['Underline'] = 'Зачеркнутый';
$ForumLang['BBEditor']['Strike'] = 'Перечеркнутый';
$ForumLang['BBEditor']['Code'] = 'Исходный код';
$ForumLang['BBEditor']['Quote'] = 'Цитата';
$ForumLang['BBEditor']['List'] = 'Список';
$ForumLang['BBEditor']['Url'] = 'Вставить ссылку';
$ForumLang['BBEditor']['Img'] = 'Вставить изображение';
$ForumLang['BBEditor']['Smile'] = 'Смайлы';
$ForumLang['BBEditor']['Color'] = 'Выделение цветом';
//BBEDITOR - КОНЕЦ

?>