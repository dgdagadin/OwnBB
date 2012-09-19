<?php

//Переменные
$VALIDATION = '1';//Переменная определения
$Config_DBType = 'mysql';//Тип СУБД
$Config_Lang   = 'Rus';//Язык
$Config_ForumName = 'Own Bulletin Board';//Название форума
$Config_AuthSalt = 'FHHFf23243gxgdDGG';//регистрационная соль
$Config_AttachSalt = 'vyyvftfttyftyffty';//соль для аттачей
$Config_SecureKey = 'SOMETHING';//ключ сессионной переменной строки безопасности
$Config_SecureCookieName = 'DimaCookieForum';//ключ сессионной переменной строки безопасности
$Config_AutologinCookieName = 'DimaCookieAutologin';//ключ массива $COOKIE для автологина
$Config_NavigDeleter = '>>';//разделитель верхней навигации форума
$Config_JSTabs = '    '; //Табуляция в JSBuilder
$Config_ShortDescription = 'Новый форумный движок!';
$Config_FooterText = 'Powered by <a href="' . $_SERVER['PHP_SELF'] . '">OwnBB</a>&nbsp;|&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '">Own Bulletin Board</a>&nbsp;|&nbsp;2011-2012&nbsp;&copy;&nbsp;&nbsp;';
$Config_GuestGroupID = 3; //Идентификатор гостевой группы
$Config_HostName = 'ownbb.local'; //
//Переменные - конец

//Данные соединения с БД
define ('OBB_CONNECTION_HOST', 'localhost'); //Сервер БД
define ('OBB_CONNECTION_USER', 'root'); //Пользователь БД
define ('OBB_CONNECTION_PASSWORD', 'root'); //Пароль БД
define ('OBB_CONNECTION_DB', 'ownbb'); //Название БД
//Конец данных о соединении

//Пути
define ('OBB_ROOT_DIR', ''); //Корневая директория форума
define ('OBB_FORUM_KERNEL_DIR', 'Kernel');//Папка с исполняемыми файлами
define ('OBB_KERNEL_DIR', OBB_FORUM_KERNEL_DIR . '/Includes'); //Путь к функциям
define ('OBB_SHOW_DIR', OBB_FORUM_KERNEL_DIR); //Путь к показывающим скриптам
define ('OBB_LANGUAGE_DIR', 'Language/' . $Config_Lang . '/LangIncludes'); //Путь к файлам языков
define ('OBB_TEMPLATE_LANGUAGE_DIR', 'Language/' . $Config_Lang . '/LangTemplates'); //Путь к файлам шаблонов
define ('OBB_HTML_LANGUAGE_DIR', 'Language/' . $Config_Lang . '/HTMLTemplates'); //Путь к файлам html
define ('OBB_IMAGE_DIR', 'images'); //Путь к картинкам
define ('OBB_SMILES_DIR', OBB_IMAGE_DIR . '/Smiles'); //Путь к смайлам
define ('OBB_CSS_DIR', 'css'); //Путь к CSS
define ('OBB_JS_DIR', 'js'); //Путь к Javascript
define ('OBB_MVC_DIR', 'MVC'); //Путь к шаблонам
define ('OBB_ADMIN_DIR', OBB_FORUM_KERNEL_DIR . '/Admin'); //Путь к админским скриптам
define ('OBB_SESSION_DIR', 'Sessions'); //Путь к папке с файлами сессий
define ('OBB_FILE_DIR', 'Files'); //Путь к папке с файлами
define ('OBB_CACHE_DIR', 'Cache'); //Путь к кэш-папке
define ('OBB_LOG_DIR', 'Logs'); //Путь к лог-папке
define ('OBB_ERROR_LOG_DIR', OBB_LOG_DIR . '/ErrorLogs'); //Путь к папке с логами ошибкок
define ('OBB_ERROR_MAIL_DIR', OBB_LOG_DIR . '/MailLogs'); //Путь к папке с логами почты
define ('OBB_ERROR_LOG_FILE', OBB_ERROR_LOG_DIR . '/ErrorLog.txt'); //Файл ошибкок
define ('OBB_TRANSACTION_LOG_DIR', OBB_ERROR_LOG_DIR . '/Transactions.ErrorLog.txt'); //Файл ошибкок транзакций
define ('OBB_ACTIVATION_DIR', OBB_FILE_DIR . '/Activation'); //Путь к папке с активационными файлами
define ('OBB_ATTACH_DIR', OBB_FILE_DIR . '/Download'); //Путь к папке с файлами сообщений
define ('OBB_AVATAR_DIR', OBB_FILE_DIR . '/Avatars'); //Путь к папке с файлами аватарами пользователей
define ('OBB_CRON_DIR', OBB_FORUM_KERNEL_DIR . '/Cron'); //Путь к папке со скриптами, предназначенными для запуска из планировщика
define ('OBB_BB_LIB_DIR', OBB_KERNEL_DIR . '/nbbc');//путь к папке с библиотекой парсинга BB-кодов
define ('OBB_PHPMAILER_LIB_DIR', OBB_KERNEL_DIR . '/phpmailer');//путь к папке с библиотекой отправки сообщений по электронной почте
define ('OBB_HTACCESS_DIR', OBB_FILE_DIR . '/HTAccessCommon'); //Путь к папк с общим .htaccess
define ('OBB_SCHEMA_DIR', 'InfoModel'); //Путь к папкe cо структурой БД
//Конец путей

//Числовые ограничения форума
define ('OBB_USER_CHECK_TIME', 10); //Количество минут, через которые каждый раз необходимо проверять пользователя
define ('OBB_NUM_IP_OCTETS', 2); //количество разрядов ИП, проверяемых при авторизации
define ('OBB_NUM_THEMES_PER_PAGE', 20); //количество тем на страницу
define ('OBB_NUM_POSTS_PER_PAGE', 10); //количество постов (сообщений) на страницу
define ('OBB_NUM_USERS_PER_PAGE', 30); //количество пользователей на страницу
define ('OBB_MAX_USERS_PER_PAGE', 100); //максимальное число пользователей на страницу
define ('OBB_NUM_POSTS_IN_STATUS', 1000); //к-во постов надо написать для взятия следующей ступени статуса
define ('OBB_MAX_STATUS', 5); //максимально допустимый статус пользователя
define ('OBB_MAX_LOGIN_LENGTH', 25); //Максимальная длина логина
define ('OBB_MIN_LOGIN_LENGTH', 4); //Минимальная длина логина
define ('OBB_MAX_PASSWORD_LENGTH', 30); //Максимальная длина пароля
define ('OBB_MIN_PASSWORD_LENGTH', 6); //Минимальная длина пароля
define ('OBB_MAX_MAIL_LENGTH', 70); //Максимальная длина почты
define ('OBB_MIN_MAIL_LENGTH', 5); //Минимальная длина почты
define ('OBB_MAX_COUNTRY_LENGTH', 25); //Максимальная длина названия страны
define ('OBB_MIN_COUNTRY_LENGTH', 2); //Минимальная длина названия страны
define ('OBB_MIN_CITY_LENGTH', 2); //Минимальная длина названия города
define ('OBB_MAX_CITY_LENGTH', 30); //Максимальная длина названия города
define ('OBB_MAX_THEME_NAME_LENGTH', 100); //Максимальная длина названия темы
define ('OBB_MIN_THEME_NAME_LENGTH', 5); //Максимальная длина названия темы
define ('OBB_MAX_POST_LENGTH', 10000); //максимальная длина сообщения (поста)
define ('OBB_MIN_HOME_SITE_LENGTH', 3); //минимальная длина ссылки на дом. страницу
define ('OBB_MAX_HOME_SITE_LENGTH', 50); //максимальная длина ссылки на дом. страницу
define ('OBB_MAX_SLOGAN_LENGTH', 200); //максимальная длина девиза пользователя
define ('OBB_MAX_SLOGAN_ROWS', 3); //максимальное количество строк в девизе пользователя
define ('OBB_NUM_POSTS_FOR_POP_THEME', 30); //количество постов в теме для превращения ее в популярную
define ('OBB_MAX_AVATAR_SIZE', 37888); //Максимальный размер аватара (Байт)
define ('OBB_MAX_AVATAR_WIDTH', 150); //Максимальная ширина аватара
define ('OBB_MAX_AVATAR_HEIGHT', 150); //Максимальная высота аватара
define ('OBB_MAX_ATTACH_SIZE', 1137888); //Максимальный размер загружаемого (Байт)
define ('OBB_MAX_IMAGE_WIDTH', 640); //Максимальная ширина загруж. картинки
define ('OBB_MAX_IMAGE_HEIGHT', 800); //Максимальная высота загруж. картинки
define ('OBB_USER_ONLINE_TIME', 600); //Количество секунд, в течение которых пользователь считается онлайн
define ('OBB_USER_NEW_SEARCH', 30); //Количество секунд, после которых пользователь снова может соершать поиск по форуму
define ('OBB_GUEST_NEW_SEARCH', 30); //Количество секунд, после которых гость снова может соершать поиск по форуму
define ('OBB_MAX_SEARCH_WORD', 50); //Максимальная длина слова поиска
define ('OBB_MIN_FULL_SEARCH_WORD', 4); //Минимальная длина фразы поиска
define ('OBB_MIN_ONE_SEARCH_WORD', 4); //Минимальная длина одного слова поиска
define ('OBB_SEARCH_THEMES_PER_PAGE', 30); //К-во найденных тем на страницу
define ('OBB_SEARCH_POSTS_PER_PAGE', 10); //К-во найденных сообщений на страницу
define ('OBB_MAIN_SEARCH_ACTUAL_TIME', 24); //Время актуальности поиска (часов)
define ('OBB_AUTOLOGIN_NUM_MONTHS', 4); //К-во месяцев, в течение которых произв. автологин
define ('OBB_MAX_REPORT_REASON_LENGTH', 500); //Максимальная длина причины жалобы на сообщение
define ('OBB_MAIL_MAX_LETTER_LENGTH', 1000); //Максимальная длина тела письма

//Числовые ограничения форума - конец

//Ограничения прав доступа форума
define ('OBB_SHOW_ADMIN_ELEMENTS', TRUE); //Разрешено ли показывать административные элементы во фронтенде
define ('OBB_IP_BAN', TRUE); //Разрешено ли банить по ИП
define ('OBB_ID_BAN', TRUE); //Разрешено ли банить по ИД
define ('OBB_SHOW_ONLINE_STATISTICS', TRUE); //Выводить ли статистику онлайн на главной странице
define ('OBB_SHOW_MAIN_STATISTICS', TRUE); //Выводить ли главную статистику форума
define ('OBB_SHOW_MAIN_FASTGO', TRUE); //Выводить ли быстрый переход по форумам на главной странице
define ('OBB_SHOW_THEME_FASTGO', TRUE); //Выводить ли быстрый переход по темам на странице тем
define ('OBB_SHOW_YOUR_ABILITIES', false); //Выводить ли краткое описание возможностей пользователя на главной странице
define ('OBB_ADD_THEMES', TRUE); //Разрешать ли добавление новых тем
define ('OBB_ADD_POSTS', TRUE); //Разрешать ли добавление ответов в темы
define ('OBB_ALLOW_AVATARS', TRUE); //Разрешать ли аватары на форуме
define ('OBB_MAIL_ALLOWED', TRUE); //Разрешать ли отправку сообщений по почте
define ('OBB_EDIT_POSTS', TRUE); //Разрешать ли редактирование постов
define ('OBB_DELETE_POSTS', TRUE); //Разрешить ли удаление сообщений форума
define ('OBB_REPORT_POSTS', TRUE); //Разрешено ли жаловаться на сообщения
define ('OBB_ALLOW_ATTACHES', TRUE); //Разрешены ли аттачи на форуме
define ('OBB_CAPTCHA', TRUE); //Использовать ли капчу на форуме
define ('OBB_SHORT_ADD_FORM', TRUE); //Разрешено ли выводить форму быстрого ответа
define ('OBB_SHOW_POST_FASTGO', TRUE); //Выводить ли быстрый переход по темам на странице тем
define ('OBB_SHOW_USERLIST', TRUE); //Разрешить ли просмотр списка пользователей
define ('OBB_ALLOW_REGISTRATION', TRUE); //Можно ли гостям регистрироваться
define ('OBB_REGISTRATION_CONFIRM', false); //Необходимо ли подтверждение регистрации по электронной почте
define ('OBB_REGISTRATION_AUTOPASS', false); //Генерировать ли пароль автоматически (Константа функционирует только при OBB_REGISTRATION_CONFIRM=TRUE)
define ('OBB_REGISTRATION_CAPTCHA', false); //Выводить ли капчу при регистрации
define ('OBB_SEARCH_ALLOWED', TRUE); //Разрешен ли поиск по форуму
define ('OBB_SEARCH_WAIT', false); //Ввести ли промежутки между совершениями поиска
define ('OBB_WATCH_PROFILE', TRUE); //Позволено ли просматривать профили пользователей
define ('OBB_EDIT_PROFILE', TRUE); //Позволено ли редактировать профили пользователей
define ('OBB_ALLOW_SMILES', TRUE); //Разрешена ли замена сиволов смайлов на их картинки
define ('OBB_PUT_MESSAGE_CACHE', TRUE); //Разрешена ли запись в кэш сообщений
define ('OBB_GET_MESSAGE_CACHE', false); //Разрешено ли брать сообщения из кэша при выводе
define ('OBB_BB_PARSE_ALLOWED', TRUE); //Разрешен ли парсинг bb-кодов сообщений при выводе
define ('OBB_SEARCH_HIGHLIGHT', TRUE); //Разрешена ли подсветка искомой фразы в поиске по форуму
define ('OBB_GET_SEARCH_MESSAGE_CACHE', false); //Разрешено ли брать из кэша посты при поиске
define ('OBB_BB_SEARCH_PARSE_ALLOWED', TRUE); //Разрешено ли парсить посты при поиске
//Ограничения прав доступа форума - конец

//Почтовые настройки
define ('OBB_MAIL_FROM_NAME', 'admin');//имя от кого посылается письмо
define ('OBB_MAIL_FROM_MAIL', 'admin@local');//адрес отправителя
define ('OBB_MAIL_CURRENT_CHARSET', 'utf-8');//текущая кодировка форума
define ('OBB_MAIL_TRUE_CHARSET', 'koir-8');//корректная кодировка отправки письма
define ('OBB_MAIL_MAIL_MIME', 'text/html');//mime-тип письма
define ('OBB_MAIL_ADMIN_MAIL', TRUE);//слать ли письма администратору
define ('OBB_MAIL_DEBUG', TRUE); //Включен ли отладочный режим работы почты
//Почтовые настройки - конец

//Настройки капчи
 define ('OBB_CAPTCHA_WIDTH', 240);//ширина капчи
 define ('OBB_CAPTCHA_HEIGHT', 60);//высота капчи
 define ('OBB_CAPTCHA_DOT_FONT_SIZE', 30);//размер точек на фоне
 define ('OBB_CAPTCHA_MAIN_FONT_SIZE', 15);//размер главных символов
 define ('OBB_CAPTCHA_NUM_DOTS', 65);//к-во точек на фоне
 define ('OBB_CAPTCHA_NUM_LINES', 45);//к-во линий на фоне
 define ('OBB_CAPTCHA_NUM_LETTERS', 6);//к-во основных символов
 define ('OBB_CPATCHA_FONT_PATH', OBB_FILE_DIR . '/Fonts');//путь к папке с шрифтами
//Настройки капчи - конец

//RSS
define ('OBB_RSS_LANGUAGE', 'en-us'); //параметр Language RSS-ленты
define ('OBB_RSS_GENERATOR', 'OwnBB_RSSGenerator'); //параметр Generator RSS-Ленты
define ('OBB_RSS_POGRAMMER_MAIL', 'tihondrius8777@rambler.ru'); //параметр Programmer RSS-ленты
//RSS - конец

?>