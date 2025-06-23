-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: nuratecstock
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES 
  (14,'fruits'),
  (11,'audio'),
  (4,'ordinateur '),
  (13,'PLOMBERIE'),
  (9,'téléphonie');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES 
  (2,'amiri1','client1@gmail.com','54520 Laxou, France','0660569756'),
  (3,'client2','anis@gmail.com','crf_ojofoif','06562564511');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fournisseurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fournisseurs`
--

LOCK TABLES `fournisseurs` WRITE;
/*!40000 ALTER TABLE `fournisseurs` DISABLE KEYS */;
INSERT INTO `fournisseurs` VALUES 
  (1,'amiri','06.56.67.55.12','12rue de lille 59410','fournisseurs1@gmail.com'),
  (3,'client2','06562564511','crf_ojofoif','anis@gmail.com');
/*!40000 ALTER TABLE `fournisseurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `code_barre` varchar(50) NOT NULL,
  `quantite` int NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `ean` varchar(50) DEFAULT NULL,
  `nu` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_categorie` int DEFAULT NULL,
  `photo1` varchar(255) DEFAULT NULL,
  `photo2` varchar(255) DEFAULT NULL,
  `photo3` varchar(255) DEFAULT NULL,
  `marque` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `imei` varchar(255) DEFAULT NULL,
  `ecid` varchar(255) DEFAULT NULL,
  `numero_de_serie` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `id_souscategorie` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_souscategorie` (`id_souscategorie`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
INSERT INTO `produits` VALUES 
  (54,'iphone xs','',2,NULL,'123/456/789','4564',NULL,14,'images/1750687914_photo1_iphone 13.webp','','','iphone','45',NULL,NULL,NULL,'qr/qr_54.png',NULL),
  (53,'iphone xs','',3,NULL,'123/456/789','4564',NULL,9,'images/1750166470_photo1_sony.jpeg','','','iphone','45','645321','ezsdlm,','745','qr/qr_53.png',NULL),
  (52,'dell e5410','',2,NULL,'123/456/789','4564',NULL,4,'images/1750166416_photo1_iphone 13.webp','','','iphone','45',NULL,NULL,NULL,'qr/qr_52.png',6),
  (50,'samsung','',2,NULL,'123/456/789','4564',NULL,9,'images/1750166347_photo1_iphone 13.webp','','','iphone','45','45123156','9654132','7*','qr/qr_50.png',4),
  (51,'assus','',2,NULL,'123/456/789','4564',NULL,4,'images/1750166386_photo1_asuspc.jpg','','','iphone','45',NULL,NULL,NULL,'qr/qr_51.png',5),
  (49,'iphone 13','',2,NULL,'123/456/789','4564',NULL,9,'images/1750164832_photo1_iphone 13.webp','images/1750164832_photo2_sony.jpeg','images/1750164832_photo3_asuspc.jpg','iphone','45','645321','ezsdlm,','745','qr/qr_49.png',NULL);
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sous_categories`
--

DROP TABLE IF EXISTS `sous_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sous_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `id_categorie` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_categorie` (`id_categorie`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sous_categories`
--

LOCK TABLES `sous_categories` WRITE;
/*!40000 ALTER TABLE `sous_categories` DISABLE KEYS */;
INSERT INTO `sous_categories` VALUES 
  (1,'robinet',13),
  (2,'wc',13),
  (3,'appel',9),
  (4,'samssung',9),
  (5,'assus',4),
  (6,'dell',4);
/*!40000 ALTER TABLE `sous_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES 
  (1,'admin','$2y$12$KfxK7KViD3WFyLzY2a9u.eGi36B.mwCB7vEfTVRFmhlQbFIplWaLa','admin'),
  (2,'client','$2y$12$gMPBs.ukfSDY5iFCV2K7letIHWW/OeLYey8BsCc/8uBvY3xYSoLea','client'),
  (3,'amiri','$2y$12$jGUieb2AxzT4izskznPR8.yKSfSCATOs6y97n7hSD2ljewrcssxW2','admin'),
  (4,'antho','$2y$12$KKmei/Sco2MKBnj0YzJVQeRShbGVdcLUjYp6xNqyKj.v6kxXlbOJm','client');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

-- Finished fix: all COLLATE clauses now use utf8mb4_unicode_ci

