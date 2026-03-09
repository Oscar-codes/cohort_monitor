-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cohort_monitor
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:27:35'),(2,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:28:41'),(3,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:28:50'),(4,4,'login','user',4,NULL,NULL,'::1','2026-02-23 19:28:59'),(5,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:29:22'),(6,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:29:31'),(7,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 19:29:32'),(8,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:31:13'),(9,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 19:58:06'),(10,1,'login','user',1,NULL,NULL,'::1','2026-02-23 19:58:13'),(11,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:01:12'),(12,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:01:20'),(13,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:02:13'),(14,2,'login','user',2,NULL,NULL,'::1','2026-02-23 20:02:18'),(15,2,'logout','user',2,NULL,NULL,'::1','2026-02-23 20:02:50'),(16,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:02:59'),(17,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:03:07'),(18,4,'login','user',4,NULL,NULL,'::1','2026-02-23 20:03:15'),(19,4,'logout','user',4,NULL,NULL,'::1','2026-02-23 20:25:03'),(20,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:25:12'),(21,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:50:33'),(22,2,'login','user',2,NULL,NULL,'::1','2026-02-23 20:50:43'),(23,2,'logout','user',2,NULL,NULL,'::1','2026-02-23 20:51:11'),(24,3,'login','user',3,NULL,NULL,'::1','2026-02-23 20:51:23'),(25,3,'logout','user',3,NULL,NULL,'::1','2026-02-23 20:51:53'),(26,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:51:56'),(27,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:55:28'),(28,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:55:29'),(29,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 20:55:32'),(30,4,'login','user',4,NULL,NULL,'::1','2026-02-23 20:55:38'),(31,4,'logout','user',4,NULL,NULL,'::1','2026-02-23 20:55:51'),(32,1,'login','user',1,NULL,NULL,'::1','2026-02-23 20:55:54'),(33,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 21:22:15'),(34,1,'login','user',1,NULL,NULL,'::1','2026-02-23 21:22:17'),(35,1,'logout','user',1,NULL,NULL,'::1','2026-02-23 21:41:16'),(36,1,'login','user',1,NULL,NULL,'::1','2026-02-23 21:41:18'),(37,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 08:54:21'),(38,1,'login','user',1,NULL,NULL,'::1','2026-02-24 08:54:25'),(39,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 08:54:34'),(40,1,'login','user',1,NULL,NULL,'::1','2026-02-24 08:54:35'),(41,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 08:57:04'),(42,1,'login','user',1,NULL,NULL,'::1','2026-02-24 08:57:06'),(43,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 09:35:50'),(44,1,'login','user',1,NULL,NULL,'::1','2026-02-24 10:29:26'),(45,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 10:32:19'),(46,1,'login','user',1,NULL,NULL,'::1','2026-02-24 10:32:29'),(47,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 10:57:28'),(48,3,'login','user',3,NULL,NULL,'::1','2026-02-24 11:07:15'),(49,3,'add_comment','cohort_comment',16,NULL,'{\"category\":\"admission\",\"body\":\"Notenemos suficientes Leads\"}','::1','2026-02-24 11:10:23'),(50,3,'logout','user',3,NULL,NULL,'::1','2026-02-24 11:11:03'),(51,4,'login','user',4,NULL,NULL,'::1','2026-02-24 11:11:07'),(52,4,'logout','user',4,NULL,NULL,'::1','2026-02-24 11:12:32'),(53,1,'login','user',1,NULL,NULL,'::1','2026-02-24 11:12:36'),(54,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 11:21:18'),(55,3,'login','user',3,NULL,NULL,'::1','2026-02-24 11:21:23'),(56,3,'logout','user',3,NULL,NULL,'::1','2026-02-24 13:59:37'),(57,1,'login','user',1,NULL,NULL,'::1','2026-02-24 13:59:41'),(58,1,'logout','user',1,NULL,NULL,'::1','2026-02-24 18:20:47'),(59,4,'login','user',4,NULL,NULL,'::1','2026-02-24 18:20:55'),(60,4,'logout','user',4,NULL,NULL,'::1','2026-02-24 18:21:17'),(61,4,'login','user',4,NULL,NULL,'::1','2026-02-24 18:21:19'),(62,4,'add_comment','cohort_comment',6,NULL,'{\"category\":\"risk\",\"body\":\"No tenemos aprobado presupuesto para comercializar este bootcamp\"}','::1','2026-02-24 18:27:26'),(63,4,'logout','user',4,NULL,NULL,'::1','2026-02-24 18:42:28'),(64,4,'login','user',4,NULL,NULL,'::1','2026-02-24 18:42:29'),(65,4,'logout','user',4,NULL,NULL,'::1','2026-02-24 18:49:18'),(66,3,'login','user',3,NULL,NULL,'::1','2026-02-24 18:49:20'),(67,3,'logout','user',3,NULL,NULL,'::1','2026-02-24 18:50:05'),(68,1,'login','user',1,NULL,NULL,'::1','2026-02-24 18:50:08'),(69,1,'update_marketing_stage','marketing_stage',4,'{\"status\":\"pending\"}','{\"stage\":\"campaign_start\",\"status\":\"at_risk\"}','::1','2026-02-24 18:51:08'),(70,1,'update_marketing_stage','marketing_stage',4,'{\"status\":\"pending\"}','{\"stage\":\"campaign_build\",\"status\":\"at_risk\"}','::1','2026-02-24 18:51:15'),(71,1,'logout','user',1,NULL,NULL,'::1','2026-02-25 11:16:19'),(72,3,'login','user',3,NULL,NULL,'::1','2026-02-25 11:16:22'),(73,3,'logout','user',3,NULL,NULL,'::1','2026-02-25 11:16:25'),(74,4,'login','user',4,NULL,NULL,'::1','2026-02-25 11:16:28'),(75,4,'logout','user',4,NULL,NULL,'::1','2026-02-25 11:19:11'),(76,4,'login','user',4,NULL,NULL,'::1','2026-02-25 11:19:12'),(77,4,'logout','user',4,NULL,NULL,'::1','2026-02-25 11:44:26'),(78,1,'login','user',1,NULL,NULL,'::1','2026-02-25 11:44:32'),(79,1,'login','user',1,NULL,NULL,'::1','2026-03-02 13:45:24'),(80,1,'logout','user',1,NULL,NULL,'::1','2026-03-02 13:45:52'),(81,3,'login','user',3,NULL,NULL,'::1','2026-03-02 13:45:56'),(82,3,'logout','user',3,NULL,NULL,'::1','2026-03-02 13:46:33'),(83,4,'login','user',4,NULL,NULL,'::1','2026-03-02 13:46:37'),(84,4,'logout','user',4,NULL,NULL,'::1','2026-03-02 13:47:49'),(85,1,'login','user',1,NULL,NULL,'::1','2026-03-02 13:47:52');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cohort_comments`
--

DROP TABLE IF EXISTS `cohort_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cohort_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cohort_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `category` enum('risk','general','admission','marketing') NOT NULL DEFAULT 'general',
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comments_cohort` (`cohort_id`),
  KEY `idx_comments_cat` (`category`),
  KEY `fk_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cohort_comments`
--

LOCK TABLES `cohort_comments` WRITE;
/*!40000 ALTER TABLE `cohort_comments` DISABLE KEYS */;
INSERT INTO `cohort_comments` VALUES (2,16,3,'admission','Notenemos suficientes Leads','2026-02-24 11:10:23'),(3,6,4,'risk','No tenemos aprobado presupuesto para comercializar este bootcamp','2026-02-24 18:27:26');
/*!40000 ALTER TABLE `cohort_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cohorts`
--

DROP TABLE IF EXISTS `cohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cohorts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cohort_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `correlative_number` int(10) unsigned NOT NULL DEFAULT 0,
  `total_admission_target` int(10) unsigned NOT NULL DEFAULT 0,
  `b2b_admission_target` int(10) unsigned NOT NULL DEFAULT 0,
  `b2b_admissions` int(10) unsigned NOT NULL DEFAULT 0,
  `b2c_admissions` int(10) unsigned NOT NULL DEFAULT 0,
  `admission_deadline_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `related_project` varchar(255) DEFAULT NULL,
  `assigned_coach` varchar(255) DEFAULT NULL,
  `bootcamp_type` varchar(100) DEFAULT NULL,
  `area` enum('academic','marketing','admissions') DEFAULT NULL,
  `assigned_class_schedule` varchar(255) DEFAULT NULL,
  `training_status` enum('not_started','in_progress','completed','cancelled') NOT NULL DEFAULT 'not_started',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cohort_code` (`cohort_code`),
  KEY `idx_cohorts_code` (`cohort_code`),
  KEY `idx_cohorts_training_status` (`training_status`),
  KEY `idx_cohorts_dates` (`start_date`,`end_date`),
  KEY `idx_cohorts_bootcamp_type` (`bootcamp_type`),
  KEY `idx_cohorts_area` (`area`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cohorts`
--

LOCK TABLES `cohorts` WRITE;
/*!40000 ALTER TABLE `cohorts` DISABLE KEYS */;
INSERT INTO `cohorts` VALUES (4,'AIGSK4','Gen AI Skills',0,9,0,7,3,NULL,'2026-01-12','2026-02-20','KODIGO','Astrid Navarrete','AIGSK',NULL,'Mar-Jue 18:30-20:30','completed','2026-02-24 09:31:08','2026-02-24 18:49:44'),(5,'AIESS1','AI Agents Essentials',0,4,0,3,1,NULL,'2026-01-12','2026-03-20','KODIGO','Michelle Bonilla','AIESS',NULL,'Mar-Jue 16:00-18:00','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(6,'AITCH2','AI For Teacher MINDE',0,65,0,65,0,NULL,'2026-02-06','2026-04-26','MINEDUCYT','Kenia Paiz','AITCH',NULL,'Lun-Mi??-Vie 18:00-20:00','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(7,'AITCH3','AI For Teacher MINDE',0,65,0,65,0,NULL,'2026-02-06','2026-04-26','MINEDUCYT','Fernando Aguilar','AITCH',NULL,'Lun-Mi??-Vie 18:00-20:00','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(8,'AITCH1','AI For Teacher MINDE',0,65,0,65,0,NULL,'2026-02-06','2026-04-26','MINEDUCYT','Vic Flores','AITCH',NULL,'Lun-Mi??-Vie 18:00-20:00','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(9,'DAJ21','Data Analyst Jr.',0,11,0,6,5,NULL,'2026-02-16','2026-06-16','KODIGO','Numas Salazar','DAJ',NULL,'Lun-Mar-Mi?? 18:30-20:30','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(10,'PY-K1','Python para an??lisis de datos',0,0,0,0,0,NULL,'2026-02-19','2026-05-21','KEY INSTITUTE','Joel Orellana','PY',NULL,'Jue 16:00-18:00','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(11,'AIESS2','AI Agents Essentials',0,45,0,0,0,NULL,'2026-02-23','2026-04-24','KODIGO','Vic Flores','AIESS',NULL,'Mar-Jue 18:30-20:30','in_progress','2026-02-24 09:31:08','2026-02-24 09:31:08'),(12,'TECHF3','Tech Fundamentals',0,0,0,0,0,NULL,'2026-02-25','2026-04-08','ALDEA','Eduardo Calles','TECHF',NULL,'Lun-Vie 08:00-12:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(13,'TECHF4','Tech Fundamentals',0,0,0,0,0,NULL,'2026-02-25','2026-04-08','ALDEA','Fernando Aguilar','TECHF',NULL,'Lun-Vie 13:00-17:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(14,'FSJ33','Full Stack Jr.',0,12,0,8,4,NULL,'2026-03-02','2026-09-02','KODIGO','Jairo Vega','FSJ',NULL,'Lun-Mi??-Vie 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(15,'AIDAT1','AI For Data',0,8,0,4,4,NULL,'2026-03-09','2026-05-09','KODIGO','Michelle Bonilla','AIDAT',NULL,'Mar-Jue 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(16,'PY5','Python para an??lisis de datos',0,9,0,5,4,NULL,'2026-03-16','2026-07-16','KODIGO','Andr??s Torres','PY',NULL,'Lun-Mi??-Vie 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 11:09:59'),(17,'PY4','Python para an??lisis de datos',0,45,0,0,45,NULL,'2026-04-06','2026-08-02','INCAF 3.1','Andr??s Torres','PY',NULL,'Mar-Jue-S??b 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(18,'DAJ22','BI para An??lisis de Datos',0,46,0,23,23,NULL,'2026-04-06','2026-08-02','INCAF 3.1','Andr??s H??rcules','BIANL',NULL,'Lun-Mi??-Vie 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(19,'FSJ34','Full Stack Jr.',0,45,0,45,0,NULL,'2026-04-06','2026-10-07','INCAF 3.1','Gino Miles','FSJ',NULL,'Lun-Mi??-Vie 15:00-17:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(20,'TECHF5','Tech Fundamentals',0,10,0,10,0,NULL,'2026-04-06','2026-05-06','KODIGO','Eduardo Calles','TECHF',NULL,'Jue-Vie 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(21,'SQL6','SQL',0,37,0,37,0,NULL,'2026-04-06','2026-08-02','INCAF 3.1','Johnny de Paz','SQL',NULL,'Lun-Mar-Mi?? 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(22,'AIGSK5','Gen AI Agents',0,45,0,27,18,NULL,'2026-04-07','2026-05-07','INCAF 3.1','Vic Flores','AIGSK',NULL,'Mar-Jue 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(23,'DATTR1','Data Trainee',0,45,0,18,27,NULL,'2026-04-07','2026-07-10','INCAF 3.1','Michelle Bonilla','DATTR',NULL,'Lun-Mi??-Vie 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(24,'AIDJR1','AI For Devs Jr.',0,45,0,0,45,NULL,'2026-04-07','2026-05-19','INCAF 3.1','Kenia Paiz','AIDJR',NULL,'Mar-Jue 18:30-20:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(25,'AITCH4','AI For Teacher',0,45,0,36,9,NULL,'2026-04-07','2026-06-02','INCAF 3.1','(nuevo coach 5)','AITCH',NULL,'Mar-Jue 18:30-04:30','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(26,'AIGSK6','Gen AI Skills',0,18,0,14,4,NULL,'2026-04-09','2026-05-09','KODIGO','Sergio Hern??ndez','AIGSK',NULL,'Mar-Jue 15:00-17:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(27,'WIB2','Web Infrastructure Basic',0,65,0,65,0,NULL,'2026-04-27','2026-07-12','MINEDUCYT','Kenia Paiz','WIB',NULL,'Lun-Mi??-Vie 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(28,'WIB3','Web Infrastructure Basic',0,65,0,65,0,NULL,'2026-04-27','2026-07-12','MINEDUCYT','Fernando Aguilar','WIB',NULL,'Lun-Mi??-Vie 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(29,'WIB1','Web Infrastructure Basic',0,65,0,65,0,NULL,'2026-04-27','2026-07-12','MINEDUCYT','Vic Flores','WIB',NULL,'Lun-Mi??-Vie 18:00-20:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08'),(30,'DAJ-L1','Data Analyst Jr.',0,12,0,12,0,NULL,'2026-04-27','2026-10-27','LAMAR','Michelle Bonilla','DAJ',NULL,'Lun-Mi??-Vie 10:00-12:00','not_started','2026-02-24 09:31:08','2026-02-24 09:31:08');
/*!40000 ALTER TABLE `cohorts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketing_stages`
--

DROP TABLE IF EXISTS `marketing_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketing_stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cohort_id` int(10) unsigned NOT NULL,
  `stage_name` enum('workflow_campaign','campaign_build','campaign_start','lead_funnel') NOT NULL,
  `status` enum('completed','pending','at_risk') NOT NULL DEFAULT 'pending',
  `risk_notes` text DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cohort_stage` (`cohort_id`,`stage_name`),
  KEY `idx_mkt_status` (`status`),
  KEY `fk_mkt_user` (`updated_by`),
  CONSTRAINT `fk_mkt_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mkt_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketing_stages`
--

LOCK TABLES `marketing_stages` WRITE;
/*!40000 ALTER TABLE `marketing_stages` DISABLE KEYS */;
INSERT INTO `marketing_stages` VALUES (29,4,'workflow_campaign','pending',NULL,NULL,'2026-02-24 10:31:53','2026-02-24 10:31:53'),(30,4,'campaign_build','at_risk','dfxgdfgsdfgsd',1,'2026-02-24 10:31:53','2026-02-24 18:51:15'),(31,4,'campaign_start','at_risk','sdgvsdxfgs',1,'2026-02-24 10:31:53','2026-02-24 18:51:08'),(32,4,'lead_funnel','pending',NULL,NULL,'2026-02-24 10:31:53','2026-02-24 10:31:53'),(55,21,'workflow_campaign','pending',NULL,NULL,'2026-02-25 11:19:20','2026-02-25 11:19:20'),(56,21,'campaign_build','pending',NULL,NULL,'2026-02-25 11:19:20','2026-02-25 11:19:20'),(57,21,'campaign_start','pending',NULL,NULL,'2026-02-25 11:19:20','2026-02-25 11:19:20'),(58,21,'lead_funnel','pending',NULL,NULL,'2026-02-25 11:19:20','2026-02-25 11:19:20');
/*!40000 ALTER TABLE `marketing_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_activity` (`last_activity`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `cohort_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','inactive','graduated','dropped') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_students_cohort` (`cohort_id`),
  KEY `idx_students_status` (`status`),
  CONSTRAINT `fk_students_cohort` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','admissions_b2b','admissions_b2c','marketing') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@cohortmonitor.com','$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS','Super Administrador','admin',1,'2026-03-02 13:47:52','2026-02-23 19:25:36','2026-03-02 13:47:52'),(2,'admissions_b2b','b2b@cohortmonitor.com','$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS','Analista Admisiones B2B','admissions_b2b',1,'2026-02-23 20:50:43','2026-02-23 19:25:36','2026-02-23 20:50:43'),(3,'admissions_b2c','b2c@cohortmonitor.com','$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS','Analista Admisiones B2C','admissions_b2c',1,'2026-03-02 13:45:56','2026-02-23 19:25:36','2026-03-02 13:45:56'),(4,'marketing','marketing@cohortmonitor.com','$2y$10$tQaeHyCrKEADqdY7hhH0huYTnhZLD1egi8vFK6zLEv693tkpgQGWS','Coordinador Marketing','marketing',1,'2026-03-02 13:46:37','2026-02-23 19:25:36','2026-03-02 13:46:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-03 12:45:15
