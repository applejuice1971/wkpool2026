/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: 127.0.0.1    Database: wkpool2026
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(64) DEFAULT NULL,
  `stage` varchar(64) NOT NULL,
  `match_date` datetime NOT NULL,
  `home_team` varchar(120) NOT NULL,
  `away_team` varchar(120) NOT NULL,
  `home_score` tinyint(3) unsigned DEFAULT NULL,
  `away_score` tinyint(3) unsigned DEFAULT NULL,
  `status` enum('scheduled','finished') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_matches_external_id` (`external_id`),
  KEY `idx_matches_match_date` (`match_date`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES
(1,'Group A-001','Group A','2026-06-11 21:00:00','Mexico','South Africa',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(2,'Group A-002','Group A','2026-06-12 04:00:00','South Korea','Sweden',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(3,'Group A-003','Group A','2026-06-18 18:00:00','Sweden','South Africa',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(4,'Group A-004','Group A','2026-06-19 03:00:00','Mexico','South Korea',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(5,'Group A-005','Group A','2026-06-25 03:00:00','Sweden','Mexico',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(6,'Group A-006','Group A','2026-06-25 03:00:00','South Africa','South Korea',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(7,'Group B-001','Group B','2026-06-12 21:00:00','Canada','United Arab Emirates',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(8,'Group B-002','Group B','2026-06-13 21:00:00','Qatar','Switzerland',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(9,'Group B-003','Group B','2026-06-18 21:00:00','Switzerland','United Arab Emirates',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(10,'Group B-004','Group B','2026-06-19 00:00:00','Canada','Qatar',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(11,'Group B-005','Group B','2026-06-24 21:00:00','Switzerland','Canada',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(12,'Group B-006','Group B','2026-06-24 21:00:00','United Arab Emirates','Qatar',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(13,'Group C-001','Group C','2026-06-14 00:00:00','Brazil','Morocco',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(14,'Group C-002','Group C','2026-06-14 03:00:00','Haiti','Scotland',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(15,'Group C-003','Group C','2026-06-20 00:00:00','Scotland','Morocco',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(16,'Group C-004','Group C','2026-06-20 03:00:00','Brazil','Haiti',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(17,'Group C-005','Group C','2026-06-25 00:00:00','Scotland','Brazil',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(18,'Group C-006','Group C','2026-06-25 00:00:00','Morocco','Haiti',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(19,'Group D-001','Group D','2026-06-13 03:00:00','USA','Paraguay',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(20,'Group D-002','Group D','2026-06-13 06:00:00','Australia','Bosnia and Herzegovina',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(21,'Group D-003','Group D','2026-06-19 21:00:00','USA','Australia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(22,'Group D-004','Group D','2026-06-19 06:00:00','Bosnia and Herzegovina','Paraguay',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(23,'Group D-005','Group D','2026-06-26 04:00:00','Bosnia and Herzegovina','USA',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(24,'Group D-006','Group D','2026-06-26 04:00:00','Paraguay','Australia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(25,'Group E-001','Group E','2026-06-14 19:00:00','Germany','Curacao',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(26,'Group E-002','Group E','2026-06-15 01:00:00','Ivory Coast','Ecuador',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(27,'Group E-003','Group E','2026-06-20 22:00:00','Germany','Ivory Coast',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(28,'Group E-004','Group E','2026-06-21 02:00:00','Ecuador','Curacao',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(29,'Group E-005','Group E','2026-06-25 22:00:00','Ecuador','Germany',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(30,'Group E-006','Group E','2026-06-25 22:00:00','Curacao','Ivory Coast',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(31,'Group F-001','Group F','2026-06-14 22:00:00','Netherlands','Japan',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(32,'Group F-002','Group F','2026-06-15 04:00:00','Iraq','Tunisia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(33,'Group F-003','Group F','2026-06-20 19:00:00','Netherlands','Iraq',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(34,'Group F-004','Group F','2026-06-20 06:00:00','Tunisia','Japan',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(35,'Group F-005','Group F','2026-06-26 01:00:00','Japan','Iraq',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(36,'Group F-006','Group F','2026-06-26 01:00:00','Tunisia','Netherlands',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(37,'Group G-001','Group G','2026-06-16 03:00:00','Iran','New Zealand',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(38,'Group G-002','Group G','2026-06-15 21:00:00','Belgium','Egypt',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(39,'Group G-003','Group G','2026-06-21 21:00:00','Belgium','Iran',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(40,'Group G-004','Group G','2026-06-22 03:00:00','New Zealand','Egypt',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(41,'Group G-005','Group G','2026-06-27 05:00:00','Egypt','Iran',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(42,'Group G-006','Group G','2026-06-27 05:00:00','New Zealand','Belgium',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(43,'Group H-001','Group H','2026-06-15 18:00:00','Spain','Cape Verde',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(44,'Group H-002','Group H','2026-06-16 00:00:00','Saudi Arabia','Uruguay',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(45,'Group H-003','Group H','2026-06-21 18:00:00','Spain','Saudi Arabia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(46,'Group H-004','Group H','2026-06-22 00:00:00','Uruguay','Cape Verde',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(47,'Group H-005','Group H','2026-06-27 02:00:00','Cape Verde','Saudi Arabia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(48,'Group H-006','Group H','2026-06-27 02:00:00','Uruguay','Spain',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(49,'Group I-001','Group I','2026-06-16 21:00:00','France','Senegal',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(50,'Group I-002','Group I','2026-06-17 00:00:00','Jamaica','Norway',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(51,'Group I-003','Group I','2026-06-22 23:00:00','France','Jamaica',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(52,'Group I-004','Group I','2026-06-23 02:00:00','Norway','Senegal',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(53,'Group I-005','Group I','2026-06-26 21:00:00','Norway','France',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(54,'Group I-006','Group I','2026-06-26 21:00:00','Senegal','Jamaica',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(55,'Group J-001','Group J','2026-06-17 03:00:00','Argentina','Algeria',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(56,'Group J-002','Group J','2026-06-16 06:00:00','Austria','Jordan',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(57,'Group J-003','Group J','2026-06-22 19:00:00','Argentina','Austria',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(58,'Group J-004','Group J','2026-06-23 05:00:00','Jordan','Algeria',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(59,'Group J-005','Group J','2026-06-28 04:00:00','Algeria','Austria',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(60,'Group J-006','Group J','2026-06-28 04:00:00','Jordan','Argentina',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(61,'Group K-001','Group K','2026-06-17 19:00:00','Portugal','DR Congo',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(62,'Group K-002','Group K','2026-06-18 04:00:00','Uzbekistan','Colombia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(63,'Group K-003','Group K','2026-06-23 19:00:00','Portugal','Uzbekistan',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(64,'Group K-004','Group K','2026-06-24 04:00:00','Colombia','DR Congo',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(65,'Group K-005','Group K','2026-06-28 01:30:00','Colombia','Portugal',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(66,'Group K-006','Group K','2026-06-28 01:30:00','DR Congo','Uzbekistan',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-04-15 20:59:50'),
(67,'Group L-001','Group L','2026-06-17 22:00:00','England','Croatia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(68,'Group L-002','Group L','2026-06-18 01:00:00','Ghana','Panama',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(69,'Group L-003','Group L','2026-06-23 22:00:00','England','Ghana',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(70,'Group L-004','Group L','2026-06-24 01:00:00','Panama','Croatia',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(71,'Group L-005','Group L','2026-06-27 23:00:00','Panama','England',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(72,'Group L-006','Group L','2026-06-27 23:00:00','Croatia','Ghana',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(73,'Round of 32-001','Round of 32','2026-06-28 21:00:00','Runner up Group A','Runner up Group B',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(74,'Round of 32-002','Round of 32','2026-06-29 19:00:00','Winner Group C','Runner up Group F',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(75,'Round of 32-003','Round of 32','2026-06-29 22:30:00','Winner Group E','3rd Group A/B/C/D/F',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(76,'Round of 32-004','Round of 32','2026-06-30 03:00:00','Winner Group F','Runner up Group C',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(77,'Round of 32-005','Round of 32','2026-06-30 19:00:00','Runner up Group E','Runner up Group I',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(78,'Round of 32-006','Round of 32','2026-06-30 23:00:00','Winner Group I','3rd Group C/D/F/G/H',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(79,'Round of 32-007','Round of 32','2026-07-01 03:00:00','Winner Group A','3rd Group C/E/F/H/I',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(80,'Round of 32-008','Round of 32','2026-07-01 18:00:00','Winner Group L','3rd Group E/H/I/J/K',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(81,'Round of 32-009','Round of 32','2026-07-01 22:00:00','Winner Group G','3rd Group A/E/H/I/J',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(82,'Round of 32-010','Round of 32','2026-07-02 02:00:00','Winner Group D','3rd Group B/E/F/I/J',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(83,'Round of 32-011','Round of 32','2026-07-02 21:00:00','Winner Group H','Runner up Group J',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(84,'Round of 32-012','Round of 32','2026-07-03 01:00:00','Runner up Group K','Runner up Group L',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(85,'Round of 32-013','Round of 32','2026-07-03 05:00:00','Winner Group B','3rd Group E/F/G/I/J',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(86,'Round of 32-014','Round of 32','2026-07-03 20:00:00','Runner up Group D','Runner up Group G',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(87,'Round of 32-015','Round of 32','2026-07-04 00:00:00','Winner Group J','Runner up Group H',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(88,'Round of 32-016','Round of 32','2026-07-04 03:30:00','Winner Group K','3rd Group D/E/I/J/L',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(89,'Round of 16-001','Round of 16','2026-07-04 19:00:00','Winner Match 73','Winner Match 75',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(90,'Round of 16-002','Round of 16','2026-07-04 23:00:00','Winner Match 74','Winner Match 77',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(91,'Round of 16-003','Round of 16','2026-07-05 22:00:00','Winner Match 76','Winner Match 78',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(92,'Round of 16-004','Round of 16','2026-07-06 02:00:00','Winner Match 79','Winner Match 80',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(93,'Round of 16-005','Round of 16','2026-07-06 21:00:00','Winner Match 83','Winner Match 84',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(94,'Round of 16-006','Round of 16','2026-07-07 02:00:00','Winner Match 81','Winner Match 82',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(95,'Round of 16-007','Round of 16','2026-07-07 18:00:00','Winner Match 86','Winner Match 88',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(96,'Round of 16-008','Round of 16','2026-07-07 22:00:00','Winner Match 85','Winner Match 87',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(97,'Quarterfinal-001','Quarterfinal','2026-07-09 22:00:00','Winner Match 89','Winner Match 90',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(98,'Quarterfinal-002','Quarterfinal','2026-07-10 21:00:00','Winner Match 93','Winner Match 94',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(99,'Quarterfinal-003','Quarterfinal','2026-07-11 23:00:00','Winner Match 91','Winner Match 92',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(100,'Quarterfinal-004','Quarterfinal','2026-07-12 03:00:00','Winner Match 95','Winner Match 96',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(101,'Semifinal-001','Semifinal','2026-07-14 21:00:00','Winner Match 97','Winner Match 98',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(102,'Semifinal-002','Semifinal','2026-07-15 21:00:00','Winner Match 99','Winner Match 100',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(103,'Third-place game-001','Third-place game','2026-07-18 23:00:00','Loser Match 101','Loser Match 102',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23'),
(104,'Final-001','Final','2026-07-19 21:00:00','Winner Match 101','Winner Match 102',NULL,NULL,'scheduled','2026-03-23 22:48:23','2026-03-23 22:48:23');
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `participants`
--

DROP TABLE IF EXISTS `participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_participants_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participants`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `participants` WRITE;
/*!40000 ALTER TABLE `participants` DISABLE KEYS */;
INSERT INTO `participants` VALUES
(1,'Maurits',NULL,'2026-03-23 22:42:09','2026-03-23 22:42:09');
/*!40000 ALTER TABLE `participants` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `predictions`
--

DROP TABLE IF EXISTS `predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `predictions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` int(10) unsigned NOT NULL,
  `match_id` int(10) unsigned NOT NULL,
  `predicted_home_score` tinyint(3) unsigned NOT NULL,
  `predicted_away_score` tinyint(3) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_predictions_participant_match` (`participant_id`,`match_id`),
  KEY `fk_predictions_match` (`match_id`),
  CONSTRAINT `fk_predictions_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_predictions_participant` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `predictions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `predictions` WRITE;
/*!40000 ALTER TABLE `predictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `predictions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping routines for database 'wkpool2026'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-17 22:53:48
