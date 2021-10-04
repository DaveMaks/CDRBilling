-- 
-- Disable foreign keys
-- 
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- 
-- Set SQL mode
-- 
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 
-- Set character set the client will use to send SQL statements to the server
--
SET NAMES 'utf8';

CREATE DATABASE cucmdb
CHARACTER SET utf8
COLLATE utf8_general_ci;
--
-- Set default database
--
USE cucmdb;

--
-- Create table `tb_phonecode`
--
CREATE TABLE tb_phonecode (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(250) NOT NULL,
  code int(5) UNSIGNED NOT NULL,
  symb varchar(2) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 587,
AVG_ROW_LENGTH = 126,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_billingfrompbx`
--
CREATE TABLE tb_billingfrompbx (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  datetime int(11) UNSIGNED NOT NULL COMMENT 'Дата вызова',
  CalledNumber varchar(255) NOT NULL COMMENT 'Номер Вызываемого Абонента ',
  direction int(2) NOT NULL COMMENT 'Направление',
  duration int(11) UNSIGNED NOT NULL COMMENT 'Длительность',
  tarif decimal(19, 2) UNSIGNED NOT NULL COMMENT 'Тариф',
  cost decimal(19, 2) UNSIGNED NOT NULL COMMENT 'Сумма',
  overinfo longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_BillingFromPBX_CalledNu` on table `tb_billingfrompbx`
--
ALTER TABLE tb_billingfrompbx
ADD INDEX IDX_tb_BillingFromPBX_CalledNu (CalledNumber);

--
-- Create index `IDX_tb_BillingFromPBX_datetime` on table `tb_billingfrompbx`
--
ALTER TABLE tb_billingfrompbx
ADD INDEX IDX_tb_BillingFromPBX_datetime (datetime);

--
-- Create index `IDX_tb_BillingFromPBX_duration` on table `tb_billingfrompbx`
--
ALTER TABLE tb_billingfrompbx
ADD INDEX IDX_tb_BillingFromPBX_duration (duration);

--
-- Create index `UK_tb_BillingFromPBX` on table `tb_billingfrompbx`
--
ALTER TABLE tb_billingfrompbx
ADD UNIQUE INDEX UK_tb_BillingFromPBX (datetime, CalledNumber, duration);

--
-- Create table `tb_cdr`
--
CREATE TABLE tb_cdr (
  pkid varchar(38) DEFAULT NULL,
  globalcallid_callmanagerid int(11) UNSIGNED DEFAULT NULL,
  globalcallid_callid int(11) UNSIGNED DEFAULT NULL,
  origlegcallidentifier int(11) UNSIGNED DEFAULT NULL,
  datetimeorigination int(11) NOT NULL,
  origipaddr int(11) NOT NULL,
  callingpartynumber varchar(50) NOT NULL,
  originalcalledpartynumber varchar(50) NOT NULL,
  finalcalledpartynumber varchar(50) NOT NULL,
  datetimeconnect int(11) UNSIGNED NOT NULL,
  datetimedisconnect int(11) DEFAULT NULL,
  lastredirectdn varchar(50) DEFAULT NULL,
  duration int(11) UNSIGNED NOT NULL,
  outpulsedcallingpartynumber varchar(50) DEFAULT NULL,
  outpulsedcalledpartynumber varchar(50) DEFAULT NULL,
  origdevicename varchar(129) DEFAULT NULL,
  destdevicename varchar(129) DEFAULT NULL,
  filename varchar(100) NOT NULL,
  coment longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  id_PhoneCode int(11) UNSIGNED DEFAULT NULL,
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 614,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_CDR_callingpartynumber` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD INDEX IDX_tb_CDR_callingpartynumber (callingpartynumber);

--
-- Create index `IDX_tb_CDR_duration` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD INDEX IDX_tb_CDR_duration (duration);

--
-- Create index `IDX_tb_CDR_finalcalledpartynumber` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD INDEX IDX_tb_CDR_finalcalledpartynumber (finalcalledpartynumber);

--
-- Create index `IDX_tb_CDR_originalcalledpartynumber` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD INDEX IDX_tb_CDR_originalcalledpartynumber (originalcalledpartynumber);

--
-- Create index `IDX_tb_CDR_pkid` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD INDEX IDX_tb_CDR_pkid (pkid);

--
-- Create index `UK_tb_CDR` on table `tb_cdr`
--
ALTER TABLE tb_cdr
ADD UNIQUE INDEX UK_tb_CDR (pkid, finalcalledpartynumber, duration, datetimeconnect);

--
-- Create view `vv_pbx2cdr`
--
CREATE
SQL SECURITY INVOKER
VIEW vv_pbx2cdr
AS
SELECT
  `pbx`.`id` AS `pbx_id`,
  `pbx`.`datetime` AS `datetime`,
  `pbx`.`CalledNumber` AS `CalledNumber`,
  `pbx`.`direction` AS `direction`,
  `pbx`.`duration` AS `pbxduration`,
  `pbx`.`tarif` AS `tarif`,
  `pbx`.`cost` AS `cost`,
  `pbx`.`overinfo` AS `overinfo`,
  `cdr`.`pkid` AS `pkid`,
  `cdr`.`callingpartynumber` AS `callingpartynumber`,
  `cdr`.`originalcalledpartynumber` AS `originalcalledpartynumber`,
  `cdr`.`finalcalledpartynumber` AS `finalcalledpartynumber`,
  `cdr`.`datetimeconnect` AS `datetimeconnect`,
  `cdr`.`datetimedisconnect` AS `datetimedisconnect`,
  `cdr`.`duration` AS `cdrduration`,
  `cdr`.`filename` AS `filename`,
  `cdr`.`coment` AS `coment`,
  `cdr`.`id_PhoneCode` AS `idPhoneCode`,
  `cdr`.`id` AS `cdrid`,
  `cdr`.`origdevicename` AS `origdevicename`,
  `code`.`id` AS `id`,
  `code`.`name` AS `name`,
  `code`.`code` AS `code`,
  `code`.`symb` AS `symb`
FROM (((SELECT
    `tablepbx`.`id` AS `id`,
    `tablepbx`.`datetime` AS `datetime`,
    `tablepbx`.`CalledNumber` AS `CalledNumber`,
    `tablepbx`.`direction` AS `direction`,
    `tablepbx`.`duration` AS `duration`,
    `tablepbx`.`tarif` AS `tarif`,
    `tablepbx`.`cost` AS `cost`,
    `tablepbx`.`overinfo` AS `overinfo`,
    (SELECT
        `cdr`.`id`
      FROM `cucmdb`.`tb_cdr` `cdr`
      WHERE OCTET_LENGTH(`cdr`.`finalcalledpartynumber`) > 6
      AND `cdr`.`datetimeconnect` BETWEEN `tablepbx`.`datetime` - 120 AND `tablepbx`.`datetime` + 120
      AND `cdr`.`finalcalledpartynumber` = `tablepbx`.`CalledNumber` LIMIT 1) AS `cdrID`
  FROM `cucmdb`.`tb_billingfrompbx` `tablepbx`) `pbx`
  LEFT JOIN `cucmdb`.`tb_cdr` `cdr`
    ON (`pbx`.`cdrID` = `cdr`.`id`))
  LEFT JOIN `cucmdb`.`tb_phonecode` `code`
    ON (`cdr`.`id_PhoneCode` = `code`.`id`));

--
-- Create table `tb_users`
--
CREATE TABLE tb_users (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_unit int(11) UNSIGNED NOT NULL DEFAULT 0,
  cn varchar(255) DEFAULT NULL,
  fullName varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  tel varchar(255) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  department varchar(255) DEFAULT NULL,
  email varchar(50) DEFAULT NULL,
  overdata varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_users_id_unit` on table `tb_users`
--
ALTER TABLE tb_users
ADD INDEX IDX_tb_users_id_unit (id_unit);

--
-- Create table `tb_units`
--
CREATE TABLE tb_units (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_parent int(11) UNSIGNED NOT NULL DEFAULT 0,
  Name varchar(255) DEFAULT NULL,
  Description varchar(255) DEFAULT NULL,
  overdata varchar(255) DEFAULT 'NULL',
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_systemuser`
--
CREATE TABLE tb_systemuser (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  login varchar(50) NOT NULL,
  password varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  role longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}',
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 3,
AVG_ROW_LENGTH = 8192,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_menu`
--
CREATE TABLE tb_menu (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_parent int(11) UNSIGNED NOT NULL DEFAULT 0,
  name varchar(50) DEFAULT NULL,
  class varchar(255) DEFAULT NULL,
  controller varchar(255) DEFAULT NULL,
  action varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 11,
AVG_ROW_LENGTH = 1820,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_cdrtemp`
--
CREATE TABLE tb_cdrtemp (
  pkid varchar(38) DEFAULT NULL,
  globalcallid_callmanagerid int(11) UNSIGNED DEFAULT NULL,
  globalcallid_callid int(11) UNSIGNED DEFAULT NULL,
  origlegcallidentifier int(11) UNSIGNED DEFAULT NULL,
  datetimeorigination int(11) NOT NULL,
  origipaddr int(11) NOT NULL,
  callingpartynumber varchar(50) NOT NULL,
  originalcalledpartynumber varchar(50) NOT NULL,
  finalcalledpartynumber varchar(50) NOT NULL,
  datetimeconnect int(11) UNSIGNED NOT NULL,
  datetimedisconnect int(11) DEFAULT NULL,
  lastredirectdn varchar(50) DEFAULT NULL,
  duration int(11) UNSIGNED NOT NULL,
  outpulsedcallingpartynumber varchar(50) DEFAULT NULL,
  outpulsedcalledpartynumber varchar(50) DEFAULT NULL,
  origdevicename varchar(129) DEFAULT NULL,
  destdevicename varchar(129) DEFAULT NULL,
  filename varchar(100) NOT NULL,
  coment longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_CDRTemp_filename` on table `tb_cdrtemp`
--
ALTER TABLE tb_cdrtemp
ADD INDEX IDX_tb_CDRTemp_filename (filename);

--
-- Create table `tb_cdrimportstatistics`
--
CREATE TABLE tb_cdrimportstatistics (
  datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  filename varchar(200) NOT NULL,
  countRows int(11) UNSIGNED NOT NULL DEFAULT 0,
  skipRows int(11) UNSIGNED DEFAULT 0,
  status tinyint(1) DEFAULT NULL
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_billingfrompbxtemp`
--
CREATE TABLE tb_billingfrompbxtemp (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  datetime int(11) UNSIGNED NOT NULL COMMENT 'Дата вызова',
  CalledNumber varchar(255) NOT NULL COMMENT 'Номер Вызываемого Абонента ',
  direction int(2) NOT NULL COMMENT 'Направление',
  duration int(11) UNSIGNED NOT NULL COMMENT 'Длительность',
  tarif decimal(19, 2) UNSIGNED NOT NULL COMMENT 'Тариф',
  cost decimal(19, 2) UNSIGNED NOT NULL COMMENT 'Сумма',
  overinfo longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

-- 
-- Dumping data for table tb_users
--
-- Table cucmdb.tb_users does not contain any data (it is empty)

-- 
-- Dumping data for table tb_units
--
-- Table cucmdb.tb_units does not contain any data (it is empty)

-- 
-- Dumping data for table tb_systemuser
--
INSERT INTO tb_systemuser VALUES
(1, 'admin', '$2y$10$UG40YU50Q09wU20rSXJYUe7PzlY4avO69FiftWSKKdRlcL9TSQyMW', 'Локальный Адинистратор', '["admin"]'),
(2, 'report', '$2y$10$UG40YU50Q09wU20rSXJYUe7PzlY4avO69FiftWSKKdRlcL9TSQyMW', 'Пользователь для Репортов', '["report"]');

-- 
-- Dumping data for table tb_phonecode
--
INSERT INTO tb_phonecode VALUES
(2, 'Канада', 1, NULL),
(3, 'Соединенные Штаты Америки', 1, NULL),
(4, 'Россия', 7, NULL),
(5, 'Египет', 20, NULL),
(6, 'Южно-Африканская Республика', 27, NULL),
(7, 'Греция', 30, NULL),
(8, 'Нидерланды', 31, NULL),
(9, 'Бельгия', 32, NULL),
(10, 'Франция', 33, NULL),
(11, 'Испания', 34, NULL),
(12, 'Венгрия', 36, NULL),
(13, 'Ватикан', 39, NULL),
(14, 'Италия', 39, NULL),
(15, 'Румыния', 40, NULL),
(16, 'Швейцария', 41, NULL),
(17, 'Австрия', 43, NULL),
(18, 'Великобритания', 44, NULL),
(19, 'Дания', 45, NULL),
(20, 'Швеция', 46, NULL),
(21, 'Норвегия', 47, NULL),
(22, 'Польша', 48, NULL),
(23, 'Германия', 49, NULL),
(24, 'Перу', 51, NULL),
(25, 'Мексика', 52, NULL),
(26, 'Куба', 53, NULL),
(27, 'Аргентина', 54, NULL),
(28, 'Бразилия', 55, NULL),
(29, 'Чили', 56, NULL),
(30, 'Колумбия', 57, NULL),
(31, 'Венесуэла', 58, NULL),
(32, 'Малайзия', 60, NULL),
(33, 'Австралия', 61, NULL),
(34, 'Индонезия', 62, NULL),
(35, 'Филиппины', 63, NULL),
(36, 'Новая Зеландия', 64, NULL),
(37, 'Сингапур', 65, NULL),
(38, 'Таиланд', 66, NULL),
(39, 'Казахстан', 77, NULL),
(40, 'Япония', 81, NULL),
(41, 'Южная Корея', 82, NULL),
(42, 'Вьетнам', 84, NULL),
(43, 'Китай', 86, NULL),
(44, 'Турция', 90, NULL),
(45, 'Индия', 91, NULL),
(46, 'Пакистан', 92, NULL),
(47, 'Афганистан', 93, NULL),
(48, 'Шри-Ланка', 94, NULL),
(49, 'Мьянма', 95, NULL),
(50, 'Иран', 98, NULL),
(51, 'Марокко', 212, NULL),
(52, 'Алжир', 213, NULL),
(53, 'Тунис', 216, NULL),
(54, 'Ливия', 218, NULL),
(55, 'Гамбия', 220, NULL),
(56, 'Сенегал', 221, NULL),
(57, 'Мавритания', 222, NULL),
(58, 'Мали', 223, NULL),
(59, 'Гвинея', 224, NULL),
(60, 'Кот-д’Ивуар', 225, NULL),
(61, 'Буркина Фасо', 226, NULL),
(62, 'Нигер', 227, NULL),
(63, 'Того', 228, NULL),
(64, 'Бенин', 229, NULL),
(65, 'Маврикий', 230, NULL),
(66, 'Либерия', 231, NULL),
(67, 'Сьерра-Леоне', 232, NULL),
(68, 'Гана', 233, NULL),
(69, 'Нигерия', 234, NULL),
(70, 'Чад', 235, NULL),
(71, 'Центрально-Африканская Республика', 236, NULL),
(72, 'Камерун', 237, NULL),
(73, 'Кабо-Верде', 238, NULL),
(74, 'Сан-Томе и Принсипи', 239, NULL),
(75, 'Экваториальная Гвинея', 240, NULL),
(76, 'Габон', 241, NULL),
(77, 'Конго, республика', 242, NULL),
(78, 'Конго, демократическая республика', 243, NULL),
(79, 'Ангола', 244, NULL),
(80, 'Гвинея-Бисау', 245, NULL),
(81, 'Сейшелы', 248, NULL),
(82, 'Судан', 249, NULL),
(83, 'Руанда', 250, NULL),
(84, 'Эфиопия', 251, NULL),
(85, 'Сомали', 252, NULL),
(86, 'Джибути', 253, NULL),
(87, 'Кения', 254, NULL),
(88, 'Танзания', 255, NULL),
(89, 'Уганда', 256, NULL),
(90, 'Бурунди', 257, NULL),
(91, 'Мозамбик', 259, NULL),
(92, 'Замбия', 260, NULL),
(93, 'Мадагаскар', 261, NULL),
(94, 'Зимбабве', 263, NULL),
(95, 'Намибия', 264, NULL),
(96, 'Малави', 265, NULL),
(97, 'Лесото', 266, NULL),
(98, 'Ботсвана', 267, NULL),
(99, 'Свазиленд', 268, NULL),
(100, 'Коморы', 269, NULL),
(101, 'Эритрея', 291, NULL),
(102, 'Португалия', 351, NULL),
(103, 'Люксембург', 352, NULL),
(104, 'Ирландия', 353, NULL),
(105, 'Исландия', 354, NULL),
(106, 'Албания', 355, NULL),
(107, 'Мальта', 356, NULL),
(108, 'Кипр', 357, NULL),
(109, 'Финляндия', 358, NULL),
(110, 'Болгария', 359, NULL),
(111, 'Литва', 370, NULL),
(112, 'Латвия', 371, NULL),
(113, 'Эстония', 372, NULL),
(114, 'Молдавия', 373, NULL),
(115, 'Армения', 374, NULL),
(116, 'Беларусь', 375, NULL),
(117, 'Андорра', 376, NULL),
(118, 'Монако', 377, NULL),
(119, 'Сан-Марино', 378, NULL),
(120, 'Украина', 380, NULL),
(121, 'Сербия', 381, NULL),
(122, 'Черногория', 381, NULL),
(123, 'Хорватия', 385, NULL),
(124, 'Босния и Герцеговина', 387, NULL),
(125, 'Македония', 389, NULL),
(126, 'Чехия', 420, NULL),
(127, 'Словакия', 421, NULL),
(128, 'Лихтенштейн', 423, NULL),
(129, 'Белиз', 501, NULL),
(130, 'Гватемала', 502, NULL),
(131, 'Сальвадор', 503, NULL),
(132, 'Гондурас', 504, NULL),
(133, 'Никарагуа', 505, NULL),
(134, 'Коста-Рика', 506, NULL),
(135, 'Панама', 507, NULL),
(136, 'Гаити', 509, NULL),
(137, 'Боливия', 591, NULL),
(138, 'Гайана', 592, NULL),
(139, 'Эквадор', 593, NULL),
(140, 'Парагвай', 595, NULL),
(141, 'Суринам', 597, NULL),
(142, 'Уругвай', 598, NULL),
(143, 'Восточный Тимор', 670, NULL),
(144, 'Бруней', 673, NULL),
(145, 'Науру', 674, NULL),
(146, 'Папуа - Новая Гвинея', 675, NULL),
(147, 'Тонга', 676, NULL),
(148, 'Соломоновы Острова', 677, NULL),
(149, 'Вануату', 678, NULL),
(150, 'Фиджи', 679, NULL),
(151, 'Палау', 680, NULL),
(152, 'Самоа', 685, NULL),
(153, 'Кирибати', 686, NULL),
(154, 'Тувалу', 688, NULL),
(155, 'Федеративные штаты Микронезии', 691, NULL),
(156, 'Маршалловы Острова', 692, NULL),
(157, 'Северная Корея', 850, NULL),
(158, 'Камбоджа', 855, NULL),
(159, 'Лаос', 856, NULL),
(160, 'Бангладеш', 880, NULL),
(161, 'Мальдивы', 960, NULL),
(162, 'Ливан', 961, NULL),
(163, 'Иордания', 962, NULL),
(164, 'Сирия', 963, NULL),
(165, 'Ирак', 964, NULL),
(166, 'Кувейт', 965, NULL),
(167, 'Саудовская Аравия', 966, NULL),
(168, 'Йемен', 967, NULL),
(169, 'Оман', 968, NULL),
(170, 'Объединенные Арабские Эмираты', 971, NULL),
(171, 'Израиль', 972, NULL),
(172, 'Бахрейн', 973, NULL),
(173, 'Катар', 974, NULL),
(174, 'Бутан', 975, NULL),
(175, 'Монголия', 976, NULL),
(176, 'Непал', 977, NULL),
(177, 'Словения', 986, NULL),
(178, 'Таджикистан', 992, NULL),
(179, 'Туркмения', 993, NULL),
(180, 'Азербайджан', 994, NULL),
(181, 'Грузия', 995, NULL),
(182, 'Киргизия', 996, NULL),
(183, 'Узбекистан', 998, NULL),
(184, 'Багамы', 1242, NULL),
(185, 'Барбадос', 1246, NULL),
(186, 'Антигуа и Барбуда', 1268, NULL),
(187, 'Гренада', 1473, NULL),
(188, 'Сент-Люсия', 1758, NULL),
(189, 'Доминика', 1767, NULL),
(190, 'Сент-Винсент и Гренадины', 1784, NULL),
(191, 'Доминиканская Республика', 1809, NULL),
(192, 'Тринидад и Тобаго', 1868, NULL),
(193, 'Сент-Китс и Невис', 1869, NULL),
(194, 'Ямайка', 1876, NULL),
(391, 'Карагандинская область', 7710, NULL),
(392, 'Жезказган', 77102, NULL),
(393, 'Жезказган ТОО Казтехносвязь', 77102918, NULL),
(394, 'Западно-Казахстанская область', 7711, NULL),
(395, 'Уральск', 77112, NULL),
(396, 'Уральск (ТОО Казтехносвязь)', 77112966, NULL),
(397, 'Атырауская область', 7712, NULL),
(398, 'Атырау', 77122, NULL),
(399, 'Уральск (ТОО Казтехносвязь)', 77122718, NULL),
(400, 'Актюбинская область', 7713, NULL),
(401, 'Актобе', 77132, NULL),
(402, 'Актобе ТОО Казтехносвязь', 77132930, NULL),
(403, 'Костанайская область', 7714, NULL),
(404, 'Костанай', 77142, NULL),
(405, 'Костанай ТОО Казтехносвязь', 77142931, NULL),
(406, 'Северо-Казахстанская область', 7715, NULL),
(407, 'Петропавловск', 77152, NULL),
(408, 'Петропавловск ТОО Казтехносвязь', 77152641, NULL),
(409, 'М.Жумабаева', 771531, NULL),
(410, 'Аккаинский', 771532, NULL),
(411, 'Айыртауский', 771533, NULL),
(412, 'Шал Акына', 771534, NULL),
(413, 'Г.Мусрепова', 771535, NULL),
(414, 'Тайыншинский', 771536, NULL),
(415, 'Тимирязевский', 771537, NULL),
(416, 'Кызылжарский', 771538, NULL),
(417, 'Мамлютский', 771541, NULL),
(418, 'Уалихановский', 771542, NULL),
(419, 'Есильский', 771543, NULL),
(420, 'Жамбыльский', 771544, NULL),
(421, 'Акжарский', 771546, NULL),
(422, 'Акмолинская область', 7716, NULL),
(423, 'Кокшетау', 77162, NULL),
(424, 'Кокшетау (ТОО Казтехносвязь)', 77162933, NULL),
(425, 'Нур-Султан', 7717, NULL),
(426, 'Нур-Султан', 77172, NULL),
(427, 'Нур-Султан (ТОО Казтехносвязь)', 77172972, NULL),
(428, 'Нур-Султан Казахтелеком', 771722, NULL),
(429, 'Нур-Султан Казахтелеком', 771723, NULL),
(430, 'Нур-Султан Казахтелеком', 771725, NULL),
(431, 'Нур-Султан Казахтелеком (iDPhone)', 7717257, NULL),
(432, 'Нур-Султан NETRING-Service', 7717266, NULL),
(433, 'Нур-Султан Казахтелеком', 77172977, NULL),
(434, 'Нур-Султан Казахтелеком', 7717279, NULL),
(435, 'Нур-Султан Транстелеком', 7717293, NULL),
(436, 'Нур-Султан Транстелеком', 7717294, NULL),
(437, 'Нур-Султан Транстелеком', 7717260, NULL),
(438, 'Нур-Султан Нурсат', 77172971, NULL),
(439, 'Нур-Султан Астел', 77172978, NULL),
(440, 'Нур-Султан Мегател', 77172978, NULL),
(441, 'Павлодарская область', 7718, NULL),
(442, 'Павлодар', 77182, NULL),
(443, 'Павлодар (ТОО Казтехносвязь)', 77182906, NULL),
(444, 'Intertel', 77182791, NULL),
(445, 'Карагандинская область', 7721, NULL),
(446, 'Караганда', 77212, NULL),
(447, 'Караганда (ТОО Казтехносвязь)', 77212902, NULL),
(448, 'Мегател', 77212996, NULL),
(449, 'Intertel', 77212920, NULL),
(450, 'Intertel', 77212921, NULL),
(451, 'Восточно-Казахстанская область', 7722, NULL),
(452, 'Семей', 77222, NULL),
(453, 'Семей (ТОО Казтехносвязь)', 77222694, NULL),
(454, 'Восточно-Казахстанская область', 7723, NULL),
(455, 'Усть-Каменогорск', 77232, NULL),
(456, 'Усть-Каменогорск (ТОО Казтехносвязь)', 77232919, NULL),
(457, 'Кызылординская область', 7724, NULL),
(458, 'Кызылорда', 77242, NULL),
(459, 'Кызылорда (ТОО Казтехносвязь)', 77242908, NULL),
(460, 'Южно-Казахстанская область', 7725, NULL),
(461, 'Шымкент', 77252, NULL),
(462, 'Шымкент (ТОО Казтехносвязь)', 77252973, NULL),
(463, 'Жамбылская область', 7726, NULL),
(464, 'Тараз', 77262, NULL),
(465, 'Тараз (ТОО Казтехносвязь)', 77262936, NULL),
(466, 'Алматинская область', 7727, NULL),
(467, 'Алма-Ата', 77272, NULL),
(468, 'Nursat «Ecord» (CDMA2000)', 77272371, NULL),
(469, 'Kazakhstan Online «J-Run»', 7727244, NULL),
(470, 'Kazakhstan Online «J-Run»', 7727258, NULL),
(471, 'Kazakhstan Online «J-Run»', 7727259, NULL),
(472, 'Алматытелеком (ISDN)', 7727278, NULL),
(473, 'Алма-Ата', 77273, NULL),
(474, 'NETRING-Service', 7727326, NULL),
(475, 'Мегател', 77273435, NULL),
(476, 'АО "Алтел" (City) (GSM)', 7727354, NULL),
(477, 'ТОО «SMARTNET»', 7727355, NULL),
(478, 'ТОО «SMARTNET»', 7727356, NULL),
(479, 'Altel «Dalacom City» (CDMA2000или GSM)', 7727317, NULL),
(480, 'Altel «Dalacom City» (CDMA2000или GSM)', 7727328, NULL),
(481, 'Altel «Dalacom City» (CDMA2000или GSM)', 7727329, NULL),
(482, 'Институт Ядерной Физики, пос. Алатау (пригород Алматы)', 7727386, NULL),
(483, 'Алма-Ата (ТОО Казтехносвязь)', 77273470, NULL),
(484, 'Алма-Ата (ТОО Казтехносвязь)', 7727310, NULL),
(485, 'Кольжат (Уйгурский р-н)', 77274010, NULL),
(486, 'Жаланаш (Райымбекский р-н)', 77274023, NULL),
(487, 'Ойкарагайский (Райымбекский р-н)', 77274033, NULL),
(488, 'Улькен (Жамбылский р-н)', 7727405, NULL),
(489, 'Satel', 772751, NULL),
(490, 'Илийский район', 772752, NULL),
(491, 'Илийский район', 772757, NULL),
(492, 'Kazakhstan Online «J-Run»', 7727581, NULL),
(493, 'Жамбылский район', 772770, NULL),
(494, 'Карасайский район', 772771, NULL),
(495, 'Капшагай', 772772, NULL),
(496, 'Арна (пригород Капшагая)', 772772, NULL),
(497, 'Заречное (пригород Капшагая)', 772772, NULL),
(498, 'Шенгельды (пригород Капшагая)', 772772, NULL),
(499, 'Балхашский район', 772773, NULL),
(500, 'Талгарский район', 772774, NULL),
(501, 'Енбекшиказахский район', 772775, NULL),
(502, 'Енбекшиказахский район', 772776, NULL),
(503, 'Райымбекский район', 772777, NULL),
(504, 'Уйгурский район', 772778, NULL),
(505, 'Райымбекский район', 772779, NULL),
(506, 'Kazakhstan Online «Ulan» (AMPS)', 772790, NULL),
(507, 'Kazakhstan Online «Ulan» (AMPS)', 772791, NULL),
(508, 'АО "Алтел" (GSM)', 7727972, NULL),
(509, 'Kazakhstan Online «Kulan» (спутниковая сеть)', 772799, NULL),
(510, 'Алматинская область', 7728, NULL),
(511, 'Талдыкорган (областной центр Алматинской области)', 77282, NULL),
(512, 'Талдыкорган (WLL Казахтелеком)', 7728239, NULL),
(513, 'Талдыкорган (ТОО Казтехносвязь)', 77282630, NULL),
(514, '.', 772830, NULL),
(515, 'Егинсу (Аксуский р-н)', 77283015, NULL),
(516, 'Кураксу (Аксуский р-н)', 77283016, NULL),
(517, 'Достык (Алакольский р-н)', 7728301, NULL),
(518, 'Токжайлау (Алакольский р-н)', 77283027, NULL),
(519, 'Акжар (Каратальский р-н)', 7728302, NULL),
(520, 'Копберлик (Каратальский р-н)', 7728303, NULL),
(521, 'Алмалы (Каратальский р-н)', 77283049, NULL),
(522, 'Камыскала (Алакольский р-н)', 7728305, NULL),
(523, 'Акши (Алакольский р-н)', 7728306, NULL),
(524, 'Каракум (Каратальский р-н)', 7728307, NULL),
(525, 'Панфиловский район', 772831, NULL),
(526, 'Аксуский район', 772832, NULL),
(527, 'Алакольский район', 772833, NULL),
(528, 'Каратальский район', 772834, NULL),
(529, 'Текели (Алматинская обл)', 772835, NULL),
(530, 'Рудничный (Текелийский гор. акимат)', 772835, NULL),
(531, 'Ескельдинский район', 772836, NULL),
(532, 'Алакольский район', 772837, NULL),
(533, 'Кабанбай (Алакольский р-н)', 772837, NULL),
(534, 'Коксуский район', 772838, NULL),
(535, 'Саркандский район', 772839, NULL),
(536, 'Сарканд (Саркандский р-н)', 772839, NULL),
(537, 'Койлык (Саркандский р-н)', 772839, NULL),
(538, 'Кербулакский район', 772840, NULL),
(539, 'Аксуский район', 772841, NULL),
(540, 'Кербулакский район', 772842, NULL),
(541, 'Саркандский район', 772843, NULL),
(542, 'Мангистауская область', 7729, NULL),
(543, 'Актау', 77292, NULL),
(544, 'Актау (ТОО Казтехносвязь)', 77292788, NULL),
(545, 'Мангистауский район', 772931, NULL),
(546, 'Бейнеуский район', 772932, NULL),
(547, 'Жанаозен', 772934, NULL),
(548, 'Каракиянский район', 772935, NULL),
(549, 'Каракиянский район', 772937, NULL),
(550, 'Тупкараганский район', 772938, NULL),
(551, 'Байконур (для звонков из Казахстана)', 773622, NULL),
(552, 'АЛТЕЛ', 7700, NULL),
(553, 'Кселл', 7701, NULL),
(554, 'Кселл', 7702, NULL),
(555, 'резерв для сотовых операторов', 7703, NULL),
(556, 'резерв для сотовых операторов', 7704, NULL),
(557, 'ТОО «КаР-Тел» (Beeline)', 7705, NULL),
(558, 'ТОО «КаР-Тел» (izi)', 7706, NULL),
(559, 'ТОО «Мобайл Телеком-Сервис» (Tele2)', 7707, NULL),
(560, 'АЛТЕЛ', 7708, NULL),
(561, 'резерв для сотовых операторов', 7709, NULL),
(562, 'ТОО «Мобайл Телеком-Сервис» (Tele2)', 7747, NULL),
(563, 'АО «Казахтелеком» (коммутируемый доступ)', 7750, NULL),
(564, 'АО «Казахтелеком» (передача данных)', 7751, NULL),
(565, 'АО «Казахтелеком» (Спутниковая сеть Кулан)', 7760, NULL),
(566, 'АО «Казахтелеком»', 7761, NULL),
(567, 'АО «NURSAT»', 7762, NULL),
(568, 'АО «Арна»', 7763, NULL),
(569, 'АО «2Day Telecom»', 7764, NULL),
(570, 'ТОО «КаР-Тел» (Beeline)', 7771, NULL),
(571, 'Кселл', 7775, NULL),
(572, 'ТОО «КаР-Тел» (Beeline)', 7776, NULL),
(573, 'ТОО «КаР-Тел» (Beeline)', 7777, NULL),
(574, 'Кселл', 7778, NULL),
(575, 'услуги интеллектуальных сетей связи', 78, NULL),
(576, 'бесплатные звонки', 7800, NULL),
(577, 'toll free номера закрепленные за ТОО Казтехносвязь', 7800004, NULL),
(578, 'звонок с автоматической альтернативной оплатой', 7801, NULL),
(579, 'звонок по кредитной карте', 7802, NULL),
(580, 'голосования', 7803, NULL),
(581, 'универсальный номер доступа', 7804, NULL),
(582, 'звонок по предоплаченной карте', 7805, NULL),
(583, 'звонок по расчётной карте', 7806, NULL),
(584, 'виртуальная частная сеть', 7807, NULL),
(585, 'универсальная персональная связь', 7808, NULL),
(586, 'звонок за дополнительную плату', 7809, NULL);

-- 
-- Dumping data for table tb_menu
--
INSERT INTO tb_menu VALUES
(1, 0, 'Основное', 'fas fa-tachometer-alt', 'Index', 'index'),
(2, 0, 'Отчеты', 'fas fa-scroll', '', ''),
(3, 0, 'Настройки', 'fas fa-tools', 'Config', NULL),
(4, 2, 'TimeLine', 'fas fa-receipt', 'Report', 'timeline'),
(5, 3, 'Общее', NULL, NULL, NULL),
(6, 3, 'Импорт PBX', NULL, 'Config', 'ImportForm'),
(8, 3, 'Пользователи', 'fas fa-users', 'Config', 'users'),
(9, 2, 'PBX Свод', 'fas fa-receipt', 'Report', 'PBX'),
(10, 2, 'Поиск по номеру', 'fas fa-search', 'Report', 'FindCallNum');

-- 
-- Dumping data for table tb_cdrtemp
--
-- Table cucmdb.tb_cdrtemp does not contain any data (it is empty)

-- 
-- Dumping data for table tb_cdrimportstatistics
--
-- Table cucmdb.tb_cdrimportstatistics does not contain any data (it is empty)

-- 
-- Dumping data for table tb_cdr
--
-- Table cucmdb.tb_cdr does not contain any data (it is empty)

-- 
-- Dumping data for table tb_billingfrompbxtemp
--
-- Table cucmdb.tb_billingfrompbxtemp does not contain any data (it is empty)

-- 
-- Dumping data for table tb_billingfrompbx
--
-- Table cucmdb.tb_billingfrompbx does not contain any data (it is empty)

-- Restore previous SQL mode
-- 
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

-- 
-- Enable foreign keys
-- 
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;