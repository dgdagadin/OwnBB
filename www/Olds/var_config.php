<?php

//Переменная определения
$VALIDATION = '1';
//Конец переменной определения

//Пути
$RootDir     = 'large_forum';
$KernelDir   = 'Kernel';//Путь к функциям
$ShowDir     = 'ShowScripts';//Путь к показывающим скриптам
$LanguageDir = 'Language';//Путь к файлам языков
$ImageDir    = 'images';//Путь к картинкам
$SmilesDir   = $ImageDir . '/Smiles';//Путь к смайлам
$CSSDir      = 'css';//Путь к CSS
$JSDir       = 'js';//Путь к Javascript
$AdminDir    = 'Admin';//Путь к админским скриптам
$SessionDir  = 'Sessions';//Путь к папке с файлами сессий
$FileDir     = 'Files';//Путь к папке с файлами
$CacheDir    = 'Cache';//Путь к кэш-папке
$LogDir      = 'Logs';//Путь к лог-папке
$ActivatDir  = $FileDir . '/Activation';//Путь к папке с активационными файлами
$AttachDir   = $FileDir . '/Download';//Путь к папке с файлами сообщений
$AvatarDir   = $FileDir . '/Avatars';//Путь к папке с файлами аватарами пользователей
//Конец путей

//Глобальные настройки
$Lang   = 'Rus';//Язык
$DBType = 'mysql';//Тип СУБД
//Конец Глобальных настроек

//Данные соединения с БД
$ConnHost = 'localhost';//Сервер БД
$ConnUser = 'root';//Пользователь БД
$ConnPass = 'root';//Пароль БД
$ConnDB   = 'large_forum';//Название БД
//Конец данных о соединении

//Основные настройки форума
$Config_ForumName = 'Просто форум';
$ShowOnlineStatistics = '1';//Выводить ли статистику онлайн на главной странице
$RegCaptcha = '1';//выводить ли капчу при регистрации
$NumIPRanks = 3;//количество разрядов ИП, проверяемых при авторизации
$AuthSalt = 'FHHFf23243gxgdDGG';//регистрационная соль
$AttachSalt = 'vyyvftfttyftyffty';
$SecureKey = 'SOMETHING';//ключ сессионной переменной строки безопасности
$SecureCookieName = 'DimaCookieForum';//ключ сессионной переменной строки безопасности
$NumThemesPerPage = 20;
$NumPostsInTheme = 10;
$NumUsersInPage = 30;
$MaxUsersInPage = 100;
$NumPostsInStatus = 300;
$MaxStatus = 5;
$GuestAllowProfile = '1';
//Конец основных настроек форума

//Настройка прав доступа

   //Главная страница
$GuestAllowMain = '1';//Можно ли гостю на главную страницу
$GuestAllowViewMainStatistics = '1';//Можно ли гостю выводить основную статистику на главной странице
$GuestAllowOnlineMainStatistics = '1';//Можно ли гостю выводить статистику онлайн на главной странице
$GuestAddAttachAllow = '1';//Можно ли гостю присоединять файлы
$AddAttachAllow = '1';//Можно ли вообще аттачить файлы
$GuestAllowDownload = '1';//Можно ли гостю скачивать файлы
$AddUserCaptcha = '1';
$AddGuestCaptcha = '1';
   //Страница опред. форума
$GuestAllowThemes = '1';//Можно ли гостю просматривать темы всех форумов
$GuestAllowCreateThemes = '1';//Можно ли гостю создавать темы
   //Страница опред. сообщений
$GuestAllowPosts = '1';//Можно ли гостю просматривать сообщения всех тем
   //Страница опред. темы
$GuestAllowCreatePosts = '1';//Можно ли гостю оставлять посты
$UsersAllowEditPosts = '1';//Можно ли править посты
$ShortAddPostForm = '1';//Выводить мини-фориу для добавления сообщений
   //Регистрация
$AllowGuestRegistration = '1';//Можно ли гостям регистрироваться
$AllowAvatars = '1';//разрешать ли загружать аватары
  //Просмотр списка пользователей
$GuestAllowUserList = '1';
//Конец настройки прав доступа

//Ограничения форума
$MaxLoginLength = '25';//Максимальная длина логина
$MinLoginLength = '4';//Минимальная длина логина
$MaxMailLength = '70';//Максимальная длина почты
$MinMailLength = '5';//Минимальная длина почты
$MaxCountryLength = '25';//Максимальная длина названия страны
$MaxThemeLengthName = '100';//Максимальная длина названия темы
$MinThemeLengthName = '5';//Максимальная длина названия темы
$MaxPostLength = '10000';
$MinSiteLen = '3';
$MaxSiteLen = '50';
$MaxSloganLen = '200';
$MaxSloganRows = '3';
$NumPostsForPopular = 50;
$MinCityLen = '2';//Минимальная длина названия города
$MaxCityLen = '30';//Максимальная длина названия города
$MinCountryLen = '2';//Минимальная длина названия страны
$MaxCountryLen = '35';//Максимальная длина названия страны
$MaxAvatarSize = '37888';//Максимальный размер аватара (Байт)
$MaxAvatarWidth = '160';//Максимальная ширина аватара
$MaxAvatarHeight = '160';//Максимальная высота аватара
$MaxAttachSize = '37888';//Максимальный размер загружаемого (Байт)
$MaxImageWidth = '640';//Максимальная ширина загруж. картинки
$MaxImageHeight = '800';//Максимальная высота загруж. картинки
//Конец ограничения форума

//Временные настройки форума
$UserOnlineTime = 300;//Количество секунд, в течение которых пользователь считается онлайн
$UserEditPostTime = 300;//Количество секунд, в течение которых пользователю можно отредактировать пост после его оставления
//Конец временных настроек форума

//Почтовые настройки
$Config_Mail = array ('FromName'       => 'admin',//имя от кого посылается письмо
                      'FromMail'       => 'admin@local',//адрес отправителя
					  'CurrentCharset' => 'utf-8',//кодировка форума
					  'TrueCharset'    => 'koir-8',//корректная кодировка отправления
					  'MailMime'       => 'text/html',//миме-тип письма
					  'AdminMail'      => '1');//слать ли администратору письмa
//Почтовые настройки - конец

//Настройки капчи
$Config_Captcha = array ('Width'         => 175,//ширина капчи
                         'Height'        => 60,//высота капчи
					     'DotFontSize'   => 30,//размер точек на фоне
					     'MainFontSize'  => 15,//размер главных символов
					     'NumDots'       => 45,//к-во точек на фоне
					     'NumLines'      => 25,//к-во линий на фоне					   
					     'FontPath'      => $FileDir . '/Fonts',//путь к папке с шрифтами
					     'NumLetters'    => 4);//к-во основных символов
//Настройки капчи - конец

//RSS
$RSSLanguage   = 'en-us';
$RSSGenerator  = 'OwnBB_RSSGenerator';
$RSSProgrammer = 'tihondrius8777@rambler.ru';
//RSS - конец

//Прочие данные форума
$NavigDeleter = '>>';
//Конец прочих данных форума

?>