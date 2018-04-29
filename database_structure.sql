/*
SQLyog Enterprise v12.4.1 (64 bit)
MySQL - 5.7.21-0ubuntu0.16.04.1 : Database - vertretungsplan
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`vertretungsplan` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `vertretungsplan`;

/*Table structure for table `infos` */

DROP TABLE IF EXISTS `infos`;

CREATE TABLE `infos` (
  `info_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` varchar(10) NOT NULL,
  `text` text NOT NULL,
  `lastupdate` bigint(13) unsigned NOT NULL,
  `created` bigint(13) unsigned NOT NULL,
  PRIMARY KEY (`info_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6292 DEFAULT CHARSET=latin1;

/*Table structure for table `lastupdates` */

DROP TABLE IF EXISTS `lastupdates`;

CREATE TABLE `lastupdates` (
  `date` varchar(10) NOT NULL,
  `last_update` bigint(13) unsigned NOT NULL,
  `urls` json DEFAULT NULL,
  `lastupdate` bigint(13) DEFAULT NULL,
  `created` bigint(13) DEFAULT NULL,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `notifications` */

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `date` varchar(10) NOT NULL,
  `last_update` bigint(13) unsigned NOT NULL,
  `recipient` varchar(100) NOT NULL,
  `message_id` int(11) NOT NULL,
  `sent` bigint(13) unsigned NOT NULL,
  `lastupdate` bigint(13) unsigned NOT NULL,
  `created` bigint(13) unsigned NOT NULL,
  PRIMARY KEY (`date`,`last_update`,`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `substitutes` */

DROP TABLE IF EXISTS `substitutes`;

CREATE TABLE `substitutes` (
  `date` varchar(10) NOT NULL,
  `class` varchar(5) NOT NULL,
  `lesson` int(10) unsigned NOT NULL,
  `teacher` varchar(5) NOT NULL,
  `teacher_substitute` varchar(5) NOT NULL,
  `subject` varchar(10) NOT NULL,
  `subject_substitute` varchar(10) NOT NULL,
  `room` varchar(10) DEFAULT NULL,
  `dropped` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text,
  `lastupdate` bigint(13) unsigned NOT NULL,
  `created` bigint(13) unsigned NOT NULL,
  PRIMARY KEY (`date`,`class`,`lesson`,`teacher`,`teacher_substitute`,`subject`,`subject_substitute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `teachers` */

DROP TABLE IF EXISTS `teachers`;

CREATE TABLE `teachers` (
  `symbol` varchar(5) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `trainee` tinyint(1) NOT NULL DEFAULT '0',
  `lastupdate` bigint(13) unsigned NOT NULL,
  `created` bigint(13) unsigned NOT NULL,
  PRIMARY KEY (`symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
