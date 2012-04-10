<?php

$ForumLang = array ();

//общие
$ForumLang['Pages'] = 'Страницы';
$ForumLang['Submit'] = 'Вперед';
$ForumLang['Reset'] = 'Очистить';
$ForumLang['MainPage'] = 'Главная страница';
$ForumLang['AllowCreateThemes'] = 'Вам разрешено создавать темы';
$ForumLang['DisAllowCreateThemes'] = 'Вам запрещено создавать темы';
$ForumLang['AllowCreatePosts'] = 'Вам разрешено оставлять сообщения';
$ForumLang['DisAllowCreatePosts'] = 'Вам запрещено оставлять сообщения';
$ForumLang['NoAccess'] = 'У вас нет доступа к этому ресурсу. Войдите или заргистрируйтесь!';
$ForumLang['EnterCaptcha'] = 'Введите число, изображенное на картинке.';
$ForumLang['CaptchaTitle'] = 'Защита от спама';
$ForumLang['Errors'] = array (
                              'ErrorTitle'  => 'Ошибка',
							  'ErrorsArray' => array (
							                          'common_ip_banned'                 => 'Ваш IP-адрес заблокирован администратором',
							                          'common_no_group_access'           => 'Ошибка доступа - недостаточчно прав',
													  'common_no_access'                 => 'Ошибка доступа - недостаточчно прав',													  
													  'themes_wrong_forum'               => 'Идентификатор форума не является числовым',
													  'themes_no_forum'                  => 'Форума не существует. Возможно, его удалил администратор',
							                          'posts_wrong_forum_or_theme'       => 'Идентификатор темы или форума не является числовым',
													  'add_wrong_forum_or_theme_or_post' => 'Идентификатор сообщения или темы или форума не является числовым',
													  'add_no_forum_exist'               => 'Форума не существует. Возможно, его удалил администратор',
													  'add_no_theme_exist'               => 'Темы не существует. Возможно, её удалил администратор',
													  'add_no_post_exist'                => 'Сообщения не существует. Возможно, его удалил администратор',
													  'add_forum_block'                  => 'Форум заблокирован администраторам',
													  'add_theme_block'                  => 'Тема заблокирована администраторам',
													  'add_bad_editer'                   => 'Вы не можете редактировать данное сообщение - недостаточно прав',
													  'download_no_attach'               => 'В данном сообщении нет прикрепления',
													  'download_no_attach_file_exist'    => 'Ошибка - запрашиваемый файл не найден',
													  'reg_no_access'                    => 'Администтратор запретил регистрацию на форуме',
													  'profile_wrong_user_id'            => 'Идентификатор пользователя не является числовым',
													  'profile_no_user'                  => 'Пользователь не найден в базе - возможно, его удалил администратор'
							                         )
                             );


//загрузка файлов на сервер
$ForumLang['Attach'] = array ();
$ForumLang['Attach']['not_upload'] = 'Ошибка загрузки файла на сервер! Повторите попытку.';
$ForumLang['Attach']['too_many_dots'] = 'В имени файла больше одной точки!';
$ForumLang['Attach']['bad_extension'] = 'Расширение файла не является допустимым!';
$ForumLang['Attach']['bad_mime'] = 'Тип файла не является допустимым!';
$ForumLang['Attach']['bad_file_type'] = 'Тип файла не является допустимым!';
$ForumLang['Attach']['bad_size'] = 'Размер файла не является допустимым!';
$ForumLang['Attach']['image_not'] = 'Файл, заявленный изображением, изображением не является!';
$ForumLang['Attach']['image_bad_size'] = 'Габариты изображения не являются допустимыми!';


//массив даты
$ForumLand['DateArray'] = array ('Monday'   =>'Понедельник',
                                 'Tuesday'  =>'Вторник',
								 'Wednesday'=>'Среда',
								 'Thursday' =>'Четверг',
								 'Friday'   =>'Пятница',
								 'Saturday' =>'Суббота',
								 'Sunday'   =>'Воскресение',);
//общие - конец

//навигация
$ForumLang['Navig'] = array ();
$ForumLang['Navig']['Reg'] = 'Регистрация';
$ForumLang['Navig']['Login'] = 'Вход';
$ForumLang['Navig']['Rules'] = 'Правила';
$ForumLang['Navig']['Search'] = 'Поиск';
$ForumLang['Navig']['Profile'] = 'Профиль';
$ForumLang['Navig']['Members'] = 'Участники';
$ForumLang['Navig']['Logout'] = 'Выход';
//навигация - конец

//быстрый переход по форумам
$ForumLang['ForumFastJump'] = 'Быстрый переход по форумам';
$ForumLang['JumpToForum'] = 'Перейти на форум';
//быстрый переход по форумам - конец

//главная страница
$ForumLang['ForumsTitle'] = 'Форумы';
$ForumLang['ForumsBlockTitle'] = 'Форум заблокирован';
$ForumLang['ForumsNoBlockTitle'] = 'Форум открыт';
$ForumLang['NumThemesTitle'] = 'Тем';
$ForumLang['NumPostsTitle'] = 'Ответов';
$ForumLang['UpdateTitle'] = 'Обновления';
$ForumLang['MainStatistics'] = 'Статистика';
$ForumLang['MainOnline'] = 'Пользователи онлайн';
$ForumLang['MainStat_NumForums'] = 'Количество форумов';
$ForumLang['MainStat_NumThemes'] = 'Количество тем';
$ForumLang['MainStat_NumPosts'] = 'Количество постов';
$ForumLang['MainStat_NumRegUsers'] = 'Количество зарегистрированных пользователей';
$ForumLang['MainStat_LastUser'] = 'Последним зарегистрировался';
$ForumLang['MainAllOnline'] = 'Всего пользователей онлайн';
$ForumLang['MainRegOnline'] = 'Зарегистрированных пользователей';
$ForumLang['MainGuestOnline'] = 'Гостей';
$ForumLang['MainOnlineDetails'] = 'Посмотреть пользователей онлайн...';
$ForumLang['MainOpportunities'] = 'Ваши возможности';
$ForumLang['NoForums'] = 'Форумов нет';
$ForumLang['NoUpdates'] = 'Обновлений нет';
$ForumLang['GotoLastPost'] = 'Последнее сообщение';
$ForumLang['GotoLastTheme'] = 'Последняя тема';
//главная страница - конец

//Регистрация
$ForumLang['Registration'] = 'Регистрация';
$ForumLang['RegStepOne'] = 'шаг первый';
$ForumLang['RegStepTwo'] = 'шаг второй';
$ForumLang['RegStepThree'] = 'активация';
$ForumLang['RegRules'] = 'Правила регистрации';
$ForumLang['AgreeWithRules'] = 'Я ознакомился с правилами и принимаю их';
$ForumLang['RegContinue'] = 'Продолжить регистрацию';
$ForumLang['RegPersonMain'] = 'Персональные данные';
$ForumLang['RegSymbolWord'] = 'символов';
$ForumLang['RegLoginTitle'] = 'Логин';
$ForumLang['RegMailTitle'] = 'Электронная почта';
$ForumLang['RegRepeatMailTitle'] = 'Повторите электронную почту';
$ForumLang['RegSexTitle'] = 'Пол';
$ForumLang['RegSexMTitle'] = 'Мужской';
$ForumLang['RegSexFTitle'] = 'Женский';
$ForumLang['RegBirthDateTitle'] = 'Дата рождения';
$ForumLang['RegAvatar'] = 'Аватар';
$ForumLang['RegTitleAvatar'] = 'Загрузите картинку';
$ForumLang['RegMax'] = 'максимум';
$ForumLang['RegBytes'] = 'байт';
$ForumLang['RegOther'] = 'Дополнительные опции';
$ForumLang['RegHideMail'] = 'Скрывать электронную почту';
$ForumLang['RegGetAdminMail'] = 'Получать письма от администратора';
$ForumLang['RegGetUserMail'] = 'Получать письма от пользователей';
$ForumLang['RegFootnote'] = 'Поля, обязательные для заполнения';
$ForumLang['RegMailThemeUser'] = 'Регистрация на форуме';
$ForumLang['RegMailThemeAdmin'] = 'Регистрация нового пользователя на форуме';
	//ошибки регистрации//
$ForumLang['RegErrors'] = array ('ErrorBlockTitle'    => 'Ошибки при регистрации',
                                 'RegEmptyMail'       => 'Не заполнено поле почты',
								 'RegEmptyLogin'      => 'Не заполнено поле логина',
								 'RegEmptyRepeatMail' => 'Не заполнено поле повтора почты',
								 'RegEmptyDate'       => 'Не заполнено поле даты',
								 'RegBadLoginLength'  => 'Не та длина логина!',
								 'RegBadLoginSymbols' => 'Некорректные логинские символы!',
								 'RegLoginExists'     => 'Пользователь с таким логином уже зарегистрирован!',
								 'RegBadMailLength'   => 'Не та длина меила!',
								 'RegBadMailSymbols'  => 'Некорректный формат электронной почты!',
								 'RegMailsNoEq'       => 'Почты не совпадают!',
								 'RegMailExists'      => 'Пользователь с такой электронной почтой уже зарегистрирован!',
								 'RegWrongDate'       => 'Неправильный формат даты! Дата должна быть в виде дд.мм.гггг!',
								 'RegWrongAvatar'     => 'Загруженный вами в ачестве аватара не является изображением!',
								 'BadCaptcha'         => 'Вы ввели неправильно защитный код!',
								 'EmptyCaptcha'       => 'Вы не ввели защитный код!',
								 'CaptchaError'       => 'Ошибка защитного кода!');
    //ошибки регистрации//
	
	//ошибки активации
	$ForumLang['ActErrors'] = array ();
	$ForumLang['ActErrors']['ErrorBlockTitle'] = 'Ошибки при активации';
	$ForumLang['ActErrors']['NoUser'] = 'Ошибка активации - пользователь не зарегистрирован!';
	$ForumLang['ActErrors']['WrongKey'] = 'Ошибка активации - неверный ключ активации!';
	$ForumLang['ActErrors']['AlreadyActivated'] = 'Ошибка активации - пользователь уже активирован!';
	$ForumLang['ActErrors']['WrongActivationDate'] = 'Неверная дата активации';
	//ошибки активации
//Регистрация - конец

//Авторизация
$ForumLang['Authorization'] = 'Вход на форум';
$ForumLang['AuthTitle'] = 'Войти';
$ForumLang['AuthLoginTitle'] = 'Логин';
$ForumLang['AuthPassTitle'] = 'Пароль';
$ForumLang['AuthRegistration'] = 'Регистрация';
$ForumLang['AuthForgotten'] = 'Забыли пароль?';
$ForumLang['AuthRemember'] = 'запомнить меня';
$ForumLang['AuthErrors'] = array ();
$ForumLang['AuthErrors']['ErrorBlockTitle'] = 'При авторизации возникли ошибки';
$ForumLang['AuthErrors']['ErrorNoAccessTitle'] = 'Ошибка доступа';
$ForumLang['AuthErrors']['WrongLoginPass'] = 'Неправильный логин и/или пароль!';
$ForumLang['AuthErrors']['NoAcess'] = 'Для совершения данной операции необходимо войти в систему';
$ForumLang['AuthErrors']['NotActivate'] = 'Вы не активированы!';
//Конец авторизации

//Список тем форума
$ForumLang['ThemesNo'] = 'В этом форуме пока нет ни одной темы';
$ForumLang['ThemesTitle'] = 'Название темы';
$ForumLang['ThemesBlockTitle'] = 'Тема заблокирована';
$ForumLang['ThemesNewPostsTitle'] = 'В теме имеются новые сообщения';
$ForumLang['ThemesUsualTitle'] = 'Обычная тема';
$ForumLang['ThemesNumAttaches'] = 'Количество вложений';
$ForumLang['ThemesNumWatches'] = 'Просмотров';
$ForumLang['ThemesAuthor'] = 'Автор';
$ForumLang['ThemesReports'] = 'Ответов';
$ForumLang['ThemesUpdate'] = 'Обновление';
$ForumLang['ThemesImportant'] = 'Важно';
//Список тем - конец

//Список сообщений темы
$ForumLang['PostsAuthorTitle'] = 'Автор';
$ForumLang['PostsPostTitle'] = 'Сообщение';
$ForumLang['PostsGroup'] = 'Группа';
$ForumLang['PostsGuest'] = 'Гость';
$ForumLang['PostsStatus'] = 'Статус';
$ForumLang['PostsNumPosts'] = 'Сообщений';
$ForumLang['PostsRegDate'] = 'Зарегистрирован';
$ForumLang['PostsSex'] = 'Пол';
$ForumLang['PostsMale'] = 'Мужской';
$ForumLang['PostsFemale'] = 'Женский';
$ForumLang['PostsUserNumber'] = 'Пользователь №';
$ForumLang['PostsUserOnline'] = 'Сейчас на форуме';
$ForumLang['PostsUserOffline'] = 'Сейчас вне форума';
$ForumLang['PostsPost'] = 'Сообщение';
$ForumLang['PostsEdited'] = 'Отредактировано';
$ForumLang['Postsb'] = 'Байт';
$ForumLang['Postskb'] = 'КБайт';
$ForumLang['Postsmb'] = 'МБайт';
$ForumLang['PostsAttach'] = 'Прикрепление';
$ForumLang['PostsFileViews'] = 'просмотров';
$ForumLang['PostsNo'] = 'В этой теме нет сообщений!';
$ForumLang['PostsFastPost'] = 'Быстрое добавление сообщения';
$ForumLang['PostsPostTitle'] = 'Сообщение';
$ForumLang['PostsAddOptions'] = 'Дополнительные опции';
$ForumLang['PostsAllowSmiles'] = 'Разрешить смайлы';
$ForumLang['PostsSubmit'] = 'Добавить сообщение';
//Список сообщений темы - конец

//Добавление темы/сообщение
$ForumLang['Add'] = 'Добавление';
$ForumLang['AddEdit'] = 'Редактирование';
$ForumLang['AddTheme'] = 'темы';
$ForumLang['AddPost'] = 'сообщения';
$ForumLang['AddMainData'] = 'Основное';
$ForumLang['AddGuestName'] = 'Имя';
$ForumLang['AddGuestMail'] = 'Почта';
$ForumLang['AddThemeName'] = 'Заголовок темы';
$ForumLang['AddAttach'] = 'Прикрепление';
$ForumLang['AddAttachAdd'] = 'Выберите файл для прикрепления';
$ForumLang['AddAttachAllowed'] = 'Допустимые типы файлов';
$ForumLang['AddAttachEdit'] = 'Редактирование файла прикрепления';
$ForumLang['AddAttachSave'] = 'Сохранить текущий файл';
$ForumLang['AddAttachDelete'] = 'Удалить текущий файл';
$ForumLang['AddAttachReplace'] = 'Заменить текущий файл';
$ForumLang['AddAttachCur'] = 'Текущее прикрепление';
$ForumLang['AddAttachb'] = 'Байт';
$ForumLang['AddAttachkb'] = 'КБайт';
$ForumLang['AddAttachmb'] = 'МБайт';
$ForumLang['AddAOptions'] = 'Дополнительные опции';
$ForumLang['AddAllowSmiles'] = 'Разрешить смайлы';
$ForumLang['AddThemeIcon'] = 'Выберите иконку для темы';
$ForumLang['AddNoIcon'] = 'Нет';
$ForumLang['AddMessage'] = 'Сообщение';
$ForumLang['AddSubmit'] = 'Добавить';
$ForumLang['AddEdit'] = 'Редактировать';
$ForumLang['AddReAdd'] = 'Предварительный просмотр';
$ForumLang['AddErrors'] = array ();
$ForumLang['AddErrors']['EmptyLogin'] = 'Не заполнено поле логина';
$ForumLang['AddErrors']['BadLoginLength'] = 'Не та длина логина!';
$ForumLang['AddErrors']['BadLoginSymbols'] = 'Некорректные логинские символы!';
$ForumLang['AddErrors']['LoginExists'] = 'Логин уже существует! Введите другой.';
$ForumLang['AddErrors']['ErrorBlockTitle'] = 'При добавлении возникли ошибки';
$ForumLang['AddErrors']['EmptyMail'] = 'Не заполнено поле почты!';
$ForumLang['AddErrors']['BadMailLength'] = 'Не та длина меила!';
$ForumLang['AddErrors']['BadMailSymbols'] = 'Некорректный формат электронной почты!';
$ForumLang['AddErrors']['MailExists'] = 'Пользователь с такой электронной почтой уже зарегистрирован!';
$ForumLang['AddErrors']['EmptyThemeNameLength'] = 'Не заполнено поле названия темы!';
$ForumLang['AddErrors']['BadThemeNameLength'] = 'Не та длина названия темы!';
$ForumLang['AddErrors']['ThemeExists'] = 'Такая тема уже существует в данном форуме!';
$ForumLang['AddErrors']['EmptyPostField'] = 'Не заполнено поле сообщения!';
$ForumLang['AddErrors']['BadPostLength'] = 'Длина сообщения выше максимальной!';
$ForumLang['AddErrors']['BadCaptcha'] = 'Вы ввели неправильно защитный код!';
$ForumLang['AddErrors']['EmptyCaptcha'] = 'Вы не ввели защитный код!';
$ForumLang['AddErrors']['CaptchaError'] = 'Ошибка защитного кода!';
//Добавление темы/сообщение - конец

//Список пользователей
$ForumLang['UserlistTitle'] = 'Список пользователей';
$ForumLang['UserlistShowNumUsers'] = 'Пользователей на страницу';
$ForumLang['UserlistSubmit'] = 'ОК';
$ForumLang['UserlistMainLogin'] = 'Логин';
$ForumLang['UserlistMainGroup'] = 'Группа';
$ForumLang['UserlistMainRDate'] = 'Дата регистрации';
$ForumLang['UserlistMainNumPosts'] = 'Число постов';
$ForumLang['UserlistMainMail'] = 'Е-Маил';
$ForumLang['UserlistMainStatus'] = 'Активность';
$ForumLang['UserlistFemale'] = 'женский';
$ForumLang['UserlistMale'] = 'мужской';
$ForumLang['UserlistOnline'] = 'сейчас на форуме';
$ForumLang['UserlistOffline'] = 'сейчас вне форума';
$ForumLang['UserlistLastAction'] = 'последний визит';
$ForumLang['UserlistIn'] = 'в';
$ForumLang['UserlistSortUp'] = 'по возрастанию';
$ForumLang['UserlistSortDown'] = 'по убыванию';
$ForumLang['UserlistActions'] = array ();
$ForumLang['UserlistActions']['main_page'] = 'просматривает главную страницу';
$ForumLang['UserlistActions']['forum_page'] = 'просматривает темы форумов';
$ForumLang['UserlistActions']['theme_page'] = 'просматривает сообщения темы';
$ForumLang['UserlistActions']['add_theme'] = 'добавляет тему в форум';
$ForumLang['UserlistActions']['add_post'] = 'добавляет пост в тему';
$ForumLang['UserlistActions']['edit_post'] = 'редактирует пост';
$ForumLang['UserlistActions']['user_list'] = 'просматривает список пользователей';
$ForumLang['UserlistActions']['own_profile'] = 'редактирует профиль';
//Список пользователей - конец

//Профиль пользователя
$ForumLang['UserProfileTitle'] = 'Профиль';
$ForumLang['UserProfileOnline'] = 'в данный момент находится на форуме';
$ForumLang['UserProfileOffline'] = 'В данный момент нет на форуме';
$ForumLang['UserProfileLastVisit'] = 'Последний раз был на форуме';
$ForumLang['UserProfileEmpty'] = 'Не заполнено';
$ForumLang['UserProfileIn'] = 'в';
$ForumLang['UserProfilePersonals'] = 'Личные данные';
$ForumLang['UserProfileStatistics'] = 'Данные форума';
$ForumLang['UserProfileContacts'] = 'Контактные данные';
$ForumLang['UserProfileMailOpts'] = 'Дополнительные опции';
$ForumLang['UserProfileAvatar'] = 'Аватар';
$ForumLang['UserProfileMale'] = 'Мужской';
$ForumLang['UserProfileFemale'] = 'Женский';
$ForumLang['UserProfileLogin'] = 'Логин на форуме';
$ForumLang['UserProfileSlogan'] = 'Девиз';
$ForumLang['UserProfileBirth'] = 'Дата рождения';
$ForumLang['UserProfileSex'] = 'Пол';
$ForumLang['UserProfileCountry'] = 'Страна';
$ForumLang['UserProfileWWW'] = 'Домашняя страница';
$ForumLang['UserProfileCity'] = 'Город';
$ForumLang['UserProfileGroup'] = 'Группа';
$ForumLang['UserProfileStatus'] = 'Статус';
$ForumLang['UserProfileRegDate'] = 'Дата регистрации';
$ForumLang['UserProfileNumber'] = 'Номер пользователя';
$ForumLang['UserProfileThemes'] = 'Тем';
$ForumLang['UserProfilePosts'] = 'Сообщений';
$ForumLang['UserProfileMail'] = 'Электронная почта';
$ForumLang['UserProfileHidden'] = 'Скрывать';
$ForumLang['UserProfileAvatarDel'] = 'Удалить аватар';
$ForumLang['UserProfileAvatarNew'] = 'Загрузить новый аватар';
$ForumLang['UserProfileICQ'] = 'ICQ';
$ForumLang['UserProfilePhone'] = 'Домашний телефон';
$ForumLang['UserProfileMobile'] = 'Мобильный телефон';
$ForumLang['UserProfileNotAvail'] = 'Недоступно';
$ForumLang['UserProfileNoAvatar'] = 'Пользователь еще не загрузил аватар';
$ForumLang['UserProfileAllThemes'] = 'Все темы пользователя';
$ForumLang['UserProfileSubmit'] = 'Редактировать профиль';
$ForumLang['UserProfileAllPosts'] = 'Все сообщения пользователя';
$ForumLang['UserProfileAdmMail'] = 'Разрешать администратору слать сообщения на почту';
$ForumLang['UserProfileOthMail'] = 'Разрешать пользователям слать сообщения на почту';
$ForumLang['UserProfileAdminMailProfile'] = 'Редактирование профиля на';
$ForumLang['UserProfileActions'] = array ();
$ForumLang['UserProfileActions']['main_page'] = 'просматривает главную страницу';
$ForumLang['UserProfileActions']['forum_page'] = 'просматривает темы форумов';
$ForumLang['UserProfileActions']['theme_page'] = 'просматривает сообщения темы';
$ForumLang['UserProfileActions']['add_theme'] = 'добавляет тему в форум';
$ForumLang['UserProfileActions']['add_post'] = 'добавляет пост в тему';
$ForumLang['UserProfileActions']['edit_post'] = 'редактирует пост';
$ForumLang['UserProfileActions']['user_list'] = 'просматривает список пользователей';
$ForumLang['UserProfileActions']['own_profile'] = 'редактирует профиль';
$ForumLang['UserProfileErrors'] = array ();
$ForumLang['UserProfileErrors']['ErrorBlockTitle'] = 'Ошибки при редактировании профиля';
$ForumLang['UserProfileErrors']['EmptyLogin'] = 'Не заполнено поле логина';
$ForumLang['UserProfileErrors']['EmptyMail'] = 'Не заполнено поле почты';
$ForumLang['UserProfileErrors']['EmptyRepeatMail'] = 'Не заполнено поле повтора почты';
$ForumLang['UserProfileErrors']['EmptyDate'] = 'Не заполнено поле даты';
$ForumLang['UserProfileErrors']['BadLoginLength'] = 'Не та длина логина!';
$ForumLang['UserProfileErrors']['BadLoginSymbols'] = 'Некорректные логинские символы!';
$ForumLang['UserProfileErrors']['LoginExists'] = 'Пользователь с таким логином уже зарегистрирован!';
$ForumLang['UserProfileErrors']['BadMailLength'] = 'Не та длина меила!';
$ForumLang['UserProfileErrors']['BadMailSymbols'] = 'Некорректный формат электронной почты!';
$ForumLang['UserProfileErrors']['MailsNoEq'] = 'Почты не совпадают!';
$ForumLang['UserProfileErrors']['MailExists'] = 'Пользователь с такой электронной почтой уже зарегистрирован!';
$ForumLang['UserProfileErrors']['WrongDateFormat'] = 'Неправильный формат даты! Дата должна быть в виде дд.мм.гггг!';
$ForumLang['UserProfileErrors']['WrongAvatar'] = 'Загруженный вами в ачестве аватара не является изображением!';
$ForumLang['UserProfileErrors']['WrongICQFormat'] = 'Формат ICQ не является правильным!';
$ForumLang['UserProfileErrors']['WrongPhoneFormat'] = 'Формат домашнего телефона не является правильным!';
$ForumLang['UserProfileErrors']['WrongMobileFormat'] = 'Формат мобильного телефона не является правильным!';
$ForumLang['UserProfileErrors']['WrongSiteLength'] = 'Не та длина домашней страницы!';
$ForumLang['UserProfileErrors']['WrongSiteFormat'] = 'Не тот формат домашней страницы!';
$ForumLang['UserProfileErrors']['WrongCityLength'] = 'Не та длина города!';
$ForumLang['UserProfileErrors']['WrongCityFormat'] = 'Не тот формат названия города!';
$ForumLang['UserProfileErrors']['WrongCountryLength'] = 'Не та длина страны!';
$ForumLang['UserProfileErrors']['WrongCountryFormat'] = 'Не тот формат названия страны!';
$ForumLang['UserProfileErrors']['WrongSloganLength'] = 'Не та длина девиза, батенька!';
$ForumLang['UserProfileErrors']['WrongSloganRows'] = 'Количество строк в девизе не является допустимой!';
$ForumLang['UserProfileErrors']['WrongAvatar'] = 'Файл, загруженный вами в качестве аватара, не является картинкой!';
//Профиль пользователя - конец

//Забыли пароль
$ForumLang['ForgotPassTitle'] = 'Восстановление пароля';
$ForumLang['ForgotPassLogin'] = 'Логин';
$ForumLang['ForgotPassMail'] = 'Почта';
$ForumLang['ForgotPassMailNotice'] = 'Если вы не меняли почту при редактировании профиля, укажите ту, которую вы указывали при регистрации.';
$ForumLang['ForgotPassSubmit'] = 'Восстановить';
$ForumLang['ForgotUserMailTheme'] = 'Восстановление пароля на форуме';
$ForumLang['ForgotAdminMailTheme'] = 'Пользователь восстановил пароль на форуме';
$ForumLang['ForgotErrors'] = array ('UserNotExists'=>'Пользователь с таким логином и почтой не найден',
                                    'Title'=>'Ошибки, возникшие при восстановлении пароля');
//Забыли пароль - конец

//RSS
$ForumLang['RSSTitle'] = 'RSS-лента форума';
$ForumLang['RSSDescription'] = 'Свое описание ленты - введите';
$ForumLang['RSSLanguage'] = 'en-us';
$ForumLang['RSSAuthor'] = 'Автор';
$ForumLang['RSSForum'] = 'Форум';
$ForumLang['RSSTheme'] = 'Тема';
$ForumLang['RSSAttach'] = 'Прикрепление';
$ForumLang['RSSb'] = 'Байт';
$ForumLang['RSSkb'] = 'КБайт';
$ForumLang['RSSmb'] = 'МБайт';
//RSS - конец

//Сообщения
$ForumLang['Messages'] = array ();
$ForumLang['Messages']['RegSuccess'] = 'Ваша регистрация прошла успешно. На почтовый ящик, который вы указали при регистрации, выслан пароль для входа на форум.<br>
                                        Через несколько секунд вы будете перемещены на страницу авторизации. Кликните сюда, если не хотите ждать или ваш браузер<br>
										не поддерживает автоматического перенаправления.';
										
$ForumLang['Messages']['AuthSuccess'] = 'Вы вошли на форум как <b>{username}</b>.<br>
                                         Через несколько секунд вы будете перемещены на главную страницу. Кликните сюда, если не хотите ждать или ваш браузер<br>
										 не поддерживает автоматического перенаправления.';
										 
$ForumLang['Messages']['LogoutSuccess'] = 'Вы вышли из своего аккаунта. Ждем вас снова!<br>
                                           Через несколько секунд вы будете перемещены на главную страницу. Кликните сюда, если не хотите ждать или ваш браузер<br>
										   не поддерживает автоматического перенаправления.';
										   
$ForumLang['Messages']['ActivateSuccess'] = 'Вы успешно активированы!<br>
                                             Через несколько секунд вы будете перемещены на главную страницу. Кликните сюда, если не хотите ждать или ваш браузер<br>
										     не поддерживает автоматического перенаправления.';
											 
$ForumLang['Messages']['AddThemeSuccess'] = 'Вы успешно добавили тему!<br>
                                             Через несколько секунд вы будете перемещены на страницу с темой. Кликните сюда, если не хотите ждать или ваш браузер<br>
										     не поддерживает автоматического перенаправления.';
											 
$ForumLang['Messages']['AddPostSuccess'] =  'Вы успешно добавили сообщение!<br>
                                             Через несколько секунд вы будете перемещены на страницу с темой. Кликните сюда, если не хотите ждать или ваш браузер<br>
										     не поддерживает автоматического перенаправления.';
											 
$ForumLang['Messages']['EditPostSuccess'] =  'Вы успешно отредактировали сообщение!<br>
                                              Через несколько секунд вы будете перемещены на страницу с темой. Кликните сюда, если не хотите ждать или ваш браузер<br>
										      не поддерживает автоматического перенаправления.';
											  
$ForumLang['Messages']['EditProfileSuccess'] =  'Вы успешно отредактировали ваш профиль!<br>
                                                 Через несколько секунд вы будете перемещены на страницу с данными вашего профиля. Кликните сюда, если не хотите ждать или ваш браузер<br>
										         не поддерживает автоматического перенаправления.';
												 
$ForumLang['Messages']['ForgotPassSuccess'] = 'На почтовый ящик, который вы указали при регистрации, выслан новый пароль для входа на форум.<br>
                                               Через несколько секунд вы будете перемещены на страницу авторизации. Кликните сюда, если не хотите ждать или ваш браузер<br>
										       не поддерживает автоматического перенаправления.';
//Конец сообщения

?>