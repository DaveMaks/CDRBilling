﻿--
-- Script was generated by Devart dbForge Studio 2019 for MySQL, Version 8.2.23.0
-- Product home page: http://www.devart.com/dbforge/mysql/studio
-- Script date 27.03.2021 16:38:44
-- Server version: 5.5.5-10.3.23-MariaDB-0+deb10u1
-- Client version: 4.1
--

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

--
-- Set default database
--
USE cucmdb;

--
-- Drop table `tb_BillingFromPBXTemp`
--
DROP TABLE IF EXISTS tb_BillingFromPBXTemp;

--
-- Drop table `tb_cdrImportStatistics`
--
DROP TABLE IF EXISTS tb_cdrImportStatistics;

--
-- Drop table `tb_CDRTemp`
--
DROP TABLE IF EXISTS tb_CDRTemp;

--
-- Drop table `tb_menu`
--
DROP TABLE IF EXISTS tb_menu;

--
-- Drop table `tb_systemUser`
--
DROP TABLE IF EXISTS tb_systemUser;

--
-- Drop table `tb_units`
--
DROP TABLE IF EXISTS tb_units;

--
-- Drop table `tb_users`
--
DROP TABLE IF EXISTS tb_users;

--
-- Drop view `vv_pbx2cdr`
--
DROP VIEW IF EXISTS vv_pbx2cdr CASCADE;

--
-- Drop table `tb_CDR`
--
DROP TABLE IF EXISTS tb_CDR;

--
-- Drop table `tb_BillingFromPBX`
--
DROP TABLE IF EXISTS tb_BillingFromPBX;

--
-- Drop table `tb_phoneCode`
--
DROP TABLE IF EXISTS tb_phoneCode;

--
-- Set default database
--
USE cucmdb;

--
-- Create table `tb_phoneCode`
--
CREATE TABLE tb_phoneCode (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(250) NOT NULL,
  code int(5) UNSIGNED NOT NULL,
  symb varchar(2) DEFAULT NULL,
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 587,
AVG_ROW_LENGTH = 101,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_BillingFromPBX`
--
CREATE TABLE tb_BillingFromPBX (
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
AUTO_INCREMENT = 50158,
AVG_ROW_LENGTH = 162,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_BillingFromPBX_CalledNu` on table `tb_BillingFromPBX`
--
ALTER TABLE tb_BillingFromPBX
ADD INDEX IDX_tb_BillingFromPBX_CalledNu (CalledNumber);

--
-- Create index `IDX_tb_BillingFromPBX_datetime` on table `tb_BillingFromPBX`
--
ALTER TABLE tb_BillingFromPBX
ADD INDEX IDX_tb_BillingFromPBX_datetime (datetime);

--
-- Create index `IDX_tb_BillingFromPBX_duration` on table `tb_BillingFromPBX`
--
ALTER TABLE tb_BillingFromPBX
ADD INDEX IDX_tb_BillingFromPBX_duration (duration);

--
-- Create index `UK_tb_BillingFromPBX` on table `tb_BillingFromPBX`
--
ALTER TABLE tb_BillingFromPBX
ADD UNIQUE INDEX UK_tb_BillingFromPBX (datetime, CalledNumber, duration);

--
-- Create table `tb_CDR`
--
CREATE TABLE tb_CDR (
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
AUTO_INCREMENT = 1097664,
AVG_ROW_LENGTH = 614,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_CDR_callingpartynumber` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD INDEX IDX_tb_CDR_callingpartynumber (callingpartynumber);

--
-- Create index `IDX_tb_CDR_duration` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD INDEX IDX_tb_CDR_duration (duration);

--
-- Create index `IDX_tb_CDR_finalcalledpartynumber` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD INDEX IDX_tb_CDR_finalcalledpartynumber (finalcalledpartynumber);

--
-- Create index `IDX_tb_CDR_originalcalledpartynumber` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD INDEX IDX_tb_CDR_originalcalledpartynumber (originalcalledpartynumber);

--
-- Create index `IDX_tb_CDR_pkid` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD INDEX IDX_tb_CDR_pkid (pkid);

--
-- Create index `UK_tb_CDR` on table `tb_CDR`
--
ALTER TABLE tb_CDR
ADD UNIQUE INDEX UK_tb_CDR (pkid, finalcalledpartynumber, duration, datetimeconnect);

--
-- Create view `vv_pbx2cdr`
--
CREATE
DEFINER = 'cucm'@'%'
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
FROM ((((SELECT
      `tablePBX`.`id` AS `id`,
      `tablePBX`.`datetime` AS `datetime`,
      `tablePBX`.`CalledNumber` AS `CalledNumber`,
      `tablePBX`.`direction` AS `direction`,
      `tablePBX`.`duration` AS `duration`,
      `tablePBX`.`tarif` AS `tarif`,
      `tablePBX`.`cost` AS `cost`,
      `tablePBX`.`overinfo` AS `overinfo`,
      (SELECT
          `cdr`.`id`
        FROM `cucmdb`.`tb_CDR` `cdr`
        WHERE OCTET_LENGTH(`cdr`.`finalcalledpartynumber`) > 6
        AND `cdr`.`datetimeconnect` BETWEEN `tablePBX`.`datetime` - 120 AND `tablePBX`.`datetime` + 120
        AND `cdr`.`finalcalledpartynumber` = `tablePBX`.`CalledNumber` LIMIT 1) AS `cdrID`
    FROM `cucmdb`.`tb_BillingFromPBX` `tablePBX`)) `pbx`
  LEFT JOIN `cucmdb`.`tb_CDR` `cdr`
    ON (`pbx`.`cdrID` = `cdr`.`id`))
  LEFT JOIN `cucmdb`.`tb_phoneCode` `code`
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
AUTO_INCREMENT = 147,
AVG_ROW_LENGTH = 615,
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
AUTO_INCREMENT = 48,
AVG_ROW_LENGTH = 348,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_systemUser`
--
CREATE TABLE tb_systemUser (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  login varchar(50) NOT NULL,
  password varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  role longtext binary CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}',
  PRIMARY KEY (id)
)
ENGINE = INNODB,
AUTO_INCREMENT = 10,
AVG_ROW_LENGTH = 5461,
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
-- Create table `tb_CDRTemp`
--
CREATE TABLE tb_CDRTemp (
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
AVG_ROW_LENGTH = 16384,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create index `IDX_tb_CDRTemp_filename` on table `tb_CDRTemp`
--
ALTER TABLE tb_CDRTemp
ADD INDEX IDX_tb_CDRTemp_filename (filename);

--
-- Create table `tb_cdrImportStatistics`
--
CREATE TABLE tb_cdrImportStatistics (
  datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  filename varchar(200) NOT NULL,
  countRows int(11) UNSIGNED NOT NULL DEFAULT 0,
  skipRows int(11) UNSIGNED DEFAULT 0,
  status tinyint(1) DEFAULT NULL
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 90,
CHARACTER SET utf8,
COLLATE utf8_general_ci;

--
-- Create table `tb_BillingFromPBXTemp`
--
CREATE TABLE tb_BillingFromPBXTemp (
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
-- Restore previous SQL mode
-- 
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

-- 
-- Enable foreign keys
-- 
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;