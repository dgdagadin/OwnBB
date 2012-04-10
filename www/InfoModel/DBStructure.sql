--Таблица категорий форумов
--DROP TABLE charters;
CREATE TABLE charters 
( CharterID INT AUTO_INCREMENT NOT NULL , 
  CharterName VARCHAR(200) NOT NULL , 
  CharterPosition INT NOT NULL , 
  
  PRIMARY KEY ( CharterID ) , 
  UNIQUE ( CharterName )  
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
ENGINE = MYISAM;

INSERT INTO charters (CharterID, CharterName, CharterPosition) VALUES ('1', 'Программирование', '1');
INSERT INTO charters (CharterID, CharterName, CharterPosition) VALUES ('2', 'Операционные системы', '2');
INSERT INTO charters (CharterID, CharterName, CharterPosition) VALUES ('3', 'Обо всем', '3');
-------------------------------



--таблица форумов
--DROP TABLE forums_list;
CREATE TABLE forums_list
( ForumID INT AUTO_INCREMENT NOT NULL , 
  ForumName VARCHAR(200) NOT NULL , 
  ForumDescription TEXT NOT NULL , 
  ForumPosition INT NOT NULL , 
  ForumNumThemes BIGINT DEFAULT '0' NOT NULL , 
  ForumNumPosts BIGINT DEFAULT '0' NOT NULL ,
  ForumGuestView ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  ForumMinStatus VARCHAR(100) , 
  ForumLastThemeID BIGINT , 
  ForumLastPostID BIGINT ,
  ForumLastUserID BIGINT , 
  ForumLastUserName VARCHAR(200) ,
  ForumLastUpDate varchar(100) ,
  ForumBlock ENUM('yes','no') DEFAULT 'no' NOT NULL , 
  CharterID INT NOT NULL , 

  PRIMARY KEY ( ForumID ) ,   
  UNIQUE ( ForumName, CharterID ),
  UNIQUE ( ForumPosition, CharterID ) ,
  INDEX ( CharterID )
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('Программирование на Java',   'Здесь расматриваются вопросы о программировании на Java',   '1', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '1');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('Программирование на PHP',   'Здесь расматриваются вопросы о программировании на PHP',   '2', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '1');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('Программирование на C++',   'Здесь расматриваются вопросы о программировании на C++',   '3', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '1');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('Программирование на C#',   'Здесь расматриваются вопросы о программировании на C#',   '5', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '1');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('OS Windows',   'Здесь расматриваются вопросы о Windows',   '5', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '2');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('OS Linux',   'Здесь расматриваются вопросы о Linux',   '6', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '2');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('OS Mascintosh',   'Здесь расматриваются вопросы о Mascintosh',   '7', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '2');
INSERT INTO forums_list (ForumName, ForumDescription, ForumPosition, ForumNumThemes, ForumNumPosts, ForumGuestView, ForumMinStatus, ForumLastThemeID, ForumLastPostID, ForumLastUserID, ForumLastUserName, ForumLastUpDate, ForumBlock, CharterID) VALUES ('Болтовня',   'Здесь расматриваются вопросы о болтовне',   '8', '0', '0', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'no', '3');
-------------------------------



--Таблица тем
--DROP TABLE themes;
CREATE TABLE themes
( ThemeID BIGINT AUTO_INCREMENT NOT NULL , 
  ThemeName VARCHAR(200) NOT NULL ,
  ThemeDate VARCHAR(100) NOT NULL , 
  ThemeSmile INT , 
  ThemeBlock ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  ThemeNumPosts BIGINT DEFAULT '0' NOT NULL , 
  ThemeNumViews BIGINT DEFAULT '0' NOT NULL , 
  ThemeNumAttaches INT DEFAULT '0' NOT NULL , 
  ThemeImportant ENUM('yes','no') DEFAULT 'no' NOT NULL , 
  ThemeQuiz ENUM('yes','no') DEFAULT 'no' NOT NULL , 
  ThemeAllowSmiles ENUM('yes','no') DEFAULT 'yes' NOT NULL ,
  ThemeUpDate VARCHAR(100) NOT NULL , 
  UpdatePostID BIGINT , 
  UpdateUserID BIGINT NOT NULL , 
  UserID BIGINT NOT NULL , 
  UserName VARCHAR(100) , 
  ThemeUpdateUserName VARCHAR(200) NOT NULL , 
  ForumID INT NOT NULL , 
  ThemeMovedTo INT , 
  
  PRIMARY KEY ( ThemeID ) ,
  UNIQUE (ThemeID, ForumID) , 
  INDEX (ForumID)
 ) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------


--Таблица постов
--DROP TABLE posts;
CREATE TABLE posts
( PostID BIGINT AUTO_INCREMENT NOT NULL , 
  PostText LONGTEXT NOT NULL , 
  PostDate VARCHAR(100) NOT NULL , 
  PostEditDate VARCHAR(100) , 
  PostSmilesAllow ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  UserID BIGINT NOT NULL , 
  UserName VARCHAR(200) NOT NULL , 
  ForumID INT NOT NULL , 
  ThemeID BIGINT NOT NULL , 
  
  PRIMARY KEY ( PostID ) ,
  --FULLTEXT (PostText) ,
  UNIQUE (PostID, ForumID, ThemeID) , 
  INDEX (UserID) ,
  INDEX (ForumID) ,
  INDEX (ThemeID)
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------
 
 
 --Таблица прикреплений
 --DROP TABLE post_files;
 CREATE TABLE post_files 
( PostID BIGINT NOT NULL ,
  PostFileName VARCHAR(100) NOT NULL , 
  PostFileSize BIGINT NOT NULL , 
  PostFileType VARCHAR(100) NOT NULL , 
  PostFileExt VARCHAR(10) NOT NULL ,
  PostFileHeight INT , 
  PostFileWidth INT , 
  PostFileNumViews INT , 

  PRIMARY KEY ( PostID ) 
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------



--Таблица пользователей
--DROP TABLE users;
CREATE TABLE users
( UserID BIGINT AUTO_INCREMENT NOT NULL , 
  UserLogin VARCHAR(100) NOT NULL , 
  UserPassword VARCHAR(100) NOT NULL , 
  UserSlogan TEXT , 
  UserMail VARCHAR(100) NOT NULL , 
  UserMailHid ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  UserAdminMail ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  UserOtherMail ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  UserRegDate BIGINT NOT NULL , 
  UserSex ENUM('male','female') NOT NULL , 
  UserBirthDate VARCHAR(20) , 
  UserSite VARCHAR(200) , 
  UserCountry VARCHAR(70) , 
  UserWWW VARCHAR(100) , 
  UserCity VARCHAR(70) , 
  UserICQ VARCHAR(70) , 
  UserAvatar VARCHAR(10) , 
  UserPhone VARCHAR(50) , 
  UserMobile VARCHAR(50) , 
  UserNumThemes BIGINT DEFAULT '0' NOT NULL , 
  UserNumPosts BIGINT DEFAULT '0' NOT NULL ,
  UserIsActivate ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  UserAutoLogin VARCHAR(30) NOT NULL, 
  GroupID INT NOT NULL ,
  
  PRIMARY KEY ( UserID ) , 
  UNIQUE ( UserLogin ) ,
  UNIQUE ( UserMail ) ,
  UNIQUE ( UserAutoLogin ) ,
  INDEX ( GroupID )
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO users (UserID, UserLogin, UserPassword, UserSlogan, UserMail, UserMailHid, UserAdminMail, UserOtherMail, UserRegDate, UserSex, UserBirthDate, UserSite, UserCountry, UserWWW, UserCity, UserICQ, UserAvatar, UserPhone, UserMobile, UserNumThemes, UserNumPosts, UserIsActivate, UserAutoLogin, GroupID) VALUES (1, 'qwerty', '{pass}', NULL, 'qwerty@mail.ru', 'yes', 'yes', 'no', '{time}', 'male', '15.03.1987', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'yes', 'asdfghjklqwertyuiopz12345', 1);
-------------------------------



--Таблица групп пользователей
--DROP TABLE user_groups;
CREATE TABLE user_groups 
( GroupID INT AUTO_INCREMENT NOT NULL , 
  GroupName VARCHAR(100) NOT NULL , 
  GroupDescr VARCHAR(100) NOT NULL ,
  GroupColor VARCHAR(6) NOT NULL ,
  
  PRIMARY KEY ( GroupID ) , 
  UNIQUE ( GroupName ) 
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO user_groups (GroupID, GroupName, GroupDescr, GroupColor) VALUES (1, 'Administrators', 'Администраторы', 'e71f1f');
INSERT INTO user_groups (GroupID, GroupName, GroupDescr, GroupColor) VALUES (2, 'Users', 'Пользователи', '65d04d');
INSERT INTO user_groups (GroupID, GroupName, GroupDescr, GroupColor) VALUES (3, 'Guests', 'Гости', '2f2fec');
-------------------------------



--Таблица разрешений групп пользователей
--DROP TABLE user_group_permissions;
CREATE TABLE user_group_permissions 
( GroupID INT NOT NULL , 
  AclStatus            VARCHAR(10) NOT NULL , 
  AclVisitCommon         ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclVisitIndex          ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclVisitThemes         ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclVisitPosts          ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclVisitUserlist       ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclVisitUserProfile    ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclEditUserProfile     ENUM('yes','no') DEFAULT 'yes' NOT NULL ,
  AclShowMainStatistics  ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclShowMainOnline      ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclShowMainFastGoto    ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclShowThemesFastGoto  ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclShowPostsFastGoto   ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclShowShortAnswer     ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclAddThemes           ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclAddPosts            ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclEditPosts           ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclDeletePosts         ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclReportPosts         ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclAttachesAdd         ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclAttachesDownload    ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclLinksAllowed        ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclAvatarsAllowed      ENUM('yes','no') DEFAULT 'yes' NOT NULL ,
  AclSearchAllowed       ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  AclMailSendAllowed     ENUM('yes','no') DEFAULT 'yes' NOT NULL ,
  AclCaptchaAddTheme     ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  AclCaptchaAddPost      ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  AclCaptchaEditPost     ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  AclCaptchaReportPost   ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  AclCaptchaEditProfile  ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  AclCaptchaSendMail     ENUM('yes','no') DEFAULT 'no'  NOT NULL , 
  
  PRIMARY KEY ( GroupID )
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO user_group_permissions 
(GroupID, 
 AclStatus, 
 AclShowShortAnswer, 
 AclVisitCommon, 
 AclVisitIndex, 
 AclVisitThemes, 
 AclVisitPosts, 
 AclVisitUserlist, 
 AclVisitUserProfile, 
 AclEditUserProfile,
 AclShowMainStatistics, 
 AclShowMainOnline, 
 AclShowMainFastGoto, 
 AclShowThemesFastGoto, 
 AclShowPostsFastGoto, 
 AclAddThemes, 
 AclAddPosts, 
 AclEditPosts, 
 AclDeletePosts, 
 AclReportPosts, 
 AclAttachesAdd, 
 AclAttachesDownload, 
 AclLinksAllowed, 
 AclAvatarsAllowed, 
 AclSearchAllowed, 
 AclMailSendAllowed, 
 AclCaptchaAddTheme, 
 AclCaptchaAddPost, 
 AclCaptchaEditPost, 
 AclCaptchaReportPost, 
 AclCaptchaEditProfile, 
 AclCaptchaSendMail)
VALUES
--ADMIN--
(
--GroupID
1,

--AclStatus
'admin',

--AclShowShortAnswer
'yes',

--AclVisitCommon
'yes',

--AclVisitIndex
'yes',

--AclVisitThemes
'yes',

--AclVisitPosts
'yes',

--AclVisitUserlist
'yes',

--AclVisitUserProfile
'yes',

--AclEditUserProfile
'yes',

--AclShowMainStatistics
'yes',

--AclShowMainOnline
'yes',

--AclShowMainFastGoto
'yes',

--AclShowThemesFastGoto
'yes',

--AclShowPostsFastGoto
'yes',

--AclAddThemes
'yes',

--AclAddPosts
'yes',

--AclEditPosts
'yes',

--AclDeletePosts
'yes',

--AclReportPosts
'yes',

--AclAttachesAdd
'yes',

--AclAttachesDownload
'yes',

--AclLinksAllowed
'yes',

--AclAvatarsAllowed
'yes',

--AclSearchAllowed
'yes',

--AclMailSendAllowed
'yes',

--AclCaptchaAddTheme
'no',

--AclCaptchaAddPost
'no',

--AclCaptchaEditPost
'no',

--AclCaptchaReportPost
'no',

--AclCaptchaEditProfile
'no',

--AclCaptchaSendMail
'no'
),
--ADMIN--

--MEMBER--
(
--GroupID
2,

--AclStatus
'member',

--AclShowShortAnswer
'yes',

--AclVisitCommon
'yes',

--AclVisitIndex
'yes',

--AclVisitThemes
'yes',

--AclVisitPosts
'yes',

--AclVisitUserlist
'yes',

--AclVisitUserProfile
'yes',

--AclEditUserProfile
'yes',

--AclShowMainStatistics
'yes',

--AclShowMainOnline
'yes',

--AclShowMainFastGoto
'yes',

--AclShowThemesFastGoto
'yes',

--AclShowPostsFastGoto
'yes',

--AclAddThemes
'yes',

--AclAddPosts
'yes',

--AclEditPosts
'yes',

--AclDeletePosts
'yes',

--AclReportPosts
'yes',

--AclAttachesAdd
'yes',

--AclAttachesDownload
'yes',

--AclLinksAllowed
'yes',

--AclAvatarsAllowed
'yes',

--AclSearchAllowed
'yes',

--AclMailSendAllowed
'yes',

--AclCaptchaAddTheme
'no',

--AclCaptchaAddPost
'no',

--AclCaptchaEditPost
'no',

--AclCaptchaReportPost
'no',

--AclCaptchaEditProfile
'no',

--AclCaptchaSendMail
'no'
),
--MEMBER--

--GUEST--
(
--GroupID
3,

--AclStatus
'guest',

--AclShowShortAnswer
'yes',

--AclVisitCommon
'yes',

--AclVisitIndex
'yes',

--AclVisitThemes
'yes',

--AclVisitPosts
'yes',

--AclVisitUserlist
'yes',

--AclVisitUserProfile
'yes',

--AclEditUserProfile
'no',

--AclShowMainStatistics
'yes',

--AclShowMainOnline
'yes',

--AclShowMainFastGoto
'yes',

--AclShowThemesFastGoto
'yes',

--AclShowPostsFastGoto
'yes',

--AclAddThemes
'no',

--AclAddPosts
'yes',

--AclEditPosts
'no',

--AclDeletePosts
'no',

--AclReportPosts
'no',

--AclAttachesAdd
'yes',

--AclAttachesDownload
'yes',

--AclLinksAllowed
'no',

--AclAvatarsAllowed
'no',

--AclSearchAllowed
'yes',

--AclMailSendAllowed
'no',

--AclCaptchaAddTheme
'yes',

--AclCaptchaAddPost
'yes',

--AclCaptchaEditPost
'yes',

--AclCaptchaReportPost
'yes',

--AclCaptchaEditProfile
'yes',

--AclCaptchaSendMail
'yes'
);
--GUEST--
-------------------------------



--Таблица активности пользователей
--DROP TABLE user_activity;
CREATE TABLE user_activity 
( UserID BIGINT NOT NULL , 
  UserLastLogin VARCHAR(100) NOT NULL , 
  UserLastAction VARCHAR(100) NOT NULL , 
  UserIPAddress VARCHAR(20) NOT NULL , 
  UserIsOnline ENUM('yes','no') ,
  UserLastSearch VARCHAR(100) ,
  
  PRIMARY KEY ( UserID ) 
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO user_activity (UserID, UserLastLogin, UserLastAction, UserIPAddress, UserIsOnline, UserLastSearch) VALUES (1, '{time}', 'main_page', '{ip}', 'no', NULL);
-------------------------------



--Таблица активности гостей
--DROP TABLE guest_activity;
CREATE TABLE guest_activity 
( GuestIPAddress VARCHAR(20) NOT NULL, 
  GuestLastUpdate VARCHAR(100) NOT NULL, 
  GuestLastSearch VARCHAR(100) ,
  
  UNIQUE ( GuestIPAddress ) 
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------



--Таблица просмотров тем пользователями
--DROP TABLE users_to_themes;
CREATE TABLE users_to_themes 
( UserID BIGINT NOT NULL , 
  ThemeID BIGINT NOT NULL , 
  ViewDate VARCHAR(100) NOT NULL , 
  
  UNIQUE ( UserID, ThemeID ) ,
  INDEX ( ThemeID )
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------



--Статистика пользователей
--DROP TABLE statistics;
CREATE TABLE statistics 
( StatisticsKey BIGINT NOT NULL ,
  StatisticsValue BIGINT NOT NULL) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;

INSERT INTO statistics VALUES ('1', '1');
-------------------------------



--Таблица поиска
--DROP TABLE search_data;
CREATE TABLE search_data 
( SearchID BIGINT AUTO_INCREMENT NOT NULL ,
  SearchDate VARCHAR(100) NOT NULL ,
  SearchWord VARCHAR(100) ,
  SearchTrueWord VARCHAR(100) ,
  SearchUser VARCHAR(100) ,
  SearchFullUser ENUM('yes','no') DEFAULT 'no' NOT NULL , 
  SearchMethod ENUM('1','2','3') DEFAULT '1' NOT NULL , 
  SearchMethodIn ENUM('1','2') DEFAULT '1' NOT NULL , 
  SearchInForums TEXT,
  SearchSortBy ENUM('1','2') DEFAULT '1' NOT NULL , 
  SearchSortHow ENUM('1','2') DEFAULT '1' NOT NULL , 
  SearchHighlight ENUM('yes','no') DEFAULT 'yes' NOT NULL , 
  SearchGroupID INT NOT NULL ,
    
  PRIMARY KEY ( SearchID ),
  INDEX (SearchGroupID)
) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------


--Таблица подготовленных для поиска постов
--DROP TABLE search_content;
CREATE TABLE search_content 
( PostID BIGINT NOT NULL,
  SearchPostContent LONGTEXT NOT NULL,
  ForumID INT NOT NULL,
  
  PRIMARY KEY ( PostID )
)
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------


--Таблица бана
--DROP TABLE ip_bans;
CREATE TABLE ip_bans 
( BanID BIGINT AUTO_INCREMENT NOT NULL ,
  BanString VARCHAR(100) NOT NULL,
  
  PRIMARY KEY (BanID)  )
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;



--Таблица забаненных пользователей
--DROP TABLE user_bans;
CREATE TABLE user_bans 
( UserID BIGINT NOT NULL UNIQUE,
  UserBanTime VARCHAR(100) NOT NULL,
  UserBanPeriod BIGINT NOT NULL,
  UserBanMethod ENUM ('full', 'add') NOT NULL,
  
  PRIMARY KEY (UserID) )
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------



--Таблица забаненных пользователей
--DROP TABLE user_autologins;
CREATE TABLE user_autologins 
( AutologinID BIGINT AUTO_INCREMENT NOT NULL,
  UserID BIGINT NOT NULL,
  AutoLoginString VARCHAR(50) NOT NULL,
  AutoLoginIP VARCHAR(20) NOT NULL ,
  AutoLoginDate VARCHAR(100) NOT NULL,
  
  PRIMARY KEY (AutologinID) )
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------



--Таблица жалоб на посты
--DROP TABLE reports;
CREATE TABLE reports 
( ReportID BIGINT AUTO_INCREMENT NOT NULL,
  PostID BIGINT NOT NULL,
  ReportDate VARCHAR(100) NOT NULL,
  ReportPostUserID BIGINT NOT NULL,
  ReportPostForumID BIGINT NOT NULL,
  ReportPostThemeID BIGINT NOT NULL,
  UserID BIGINT NOT NULL,
  ReportReason TEXT NOT NULL,
  
  PRIMARY KEY (ReportID),
  UNIQUE (PostID, UserID) ,
  INDEX (UserID))
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
-------------------------------


--Таблица смайлов
--DROP TABLE smiles;
CREATE TABLE smiles 
( SmileID BIGINT AUTO_INCREMENT NOT NULL,
  SmileChars VARCHAR(10) NOT NULL,
  SmileImage VARCHAR(30) NOT NULL,
  SmileName VARCHAR(100) NOT NULL,
  
  PRIMARY KEY (SmileID) )
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 
ENGINE = MYISAM;
INSERT INTO smiles (SmileChars, SmileImage, SmileName) VALUES (':)', 'smile.gif', 'улыбка');
INSERT INTO smiles (SmileChars, SmileImage, SmileName) VALUES (':(', 'frown.gif', 'грусть');
INSERT INTO smiles (SmileChars, SmileImage, SmileName) VALUES (':D', 'bigsmile.gif', 'восторг');
INSERT INTO smiles (SmileChars, SmileImage, SmileName) VALUES ('>:(', 'angry.gif', 'злость');
INSERT INTO smiles (SmileChars, SmileImage, SmileName) VALUES ('>;)', 'sneaky.gif', 'сник');
-------------------------------

--Триггер обновления статистики сообщений в темах и форумах - при добавлении поста
DELIMITER //
CREATE TRIGGER  update_post_statistics_on_add
AFTER INSERT ON posts
FOR EACH ROW BEGIN
	DECLARE NumPostsInForum BIGINT;
	DECLARE NumPostsInTheme BIGINT;
	
	SELECT COUNT(PostID) INTO NumPostsInTheme FROM posts WHERE ThemeID = NEW.ThemeID;
	SELECT COUNT(PostID) INTO NumPostsInForum FROM posts WHERE ForumID = NEW.ForumID;
    
	UPDATE themes 
	SET    ThemeNumPosts = NumPostsInTheme, 
	       UpdatePostID        = NEW.PostID, 
		   UpdateUserID        = NEW.UserID, 
		   ThemeUpdateUserName = NEW.UserName,
		   ThemeUpDate         = NEW.PostDate
    WHERE ThemeID = NEW.ThemeID;
	
	UPDATE forums_list 
	SET    ForumNumPosts = NumPostsInForum, 
		   ForumLastThemeID  = NEW.ThemeID, 
	       ForumLastPostID   = NEW.PostID, 
		   ForumLastUserID   = NEW.UserID, 
		   ForumLastUserName = NEW.UserName,
		   ForumLastUpDate   = NEW.PostDate
    WHERE ForumID = NEW.ForumID;
END//
DELIMITER ;

--Триггер обновления статистики тем в форумах
DELIMITER //
CREATE TRIGGER  update_theme_statistics 
AFTER INSERT ON themes
FOR EACH ROW BEGIN
	DECLARE NumThemesInForum BIGINT;
		
	SELECT COUNT(ThemeID) INTO NumThemesInForum FROM themes WHERE ForumID = NEW.ForumID;
		
	UPDATE forums_list 
	SET    ForumNumThemes = NumThemesInForum, 
	       ForumLastThemeID = NEW.ThemeID		   
    WHERE ForumID = NEW.ForumID;
END//
DELIMITER ;
-------------------------------