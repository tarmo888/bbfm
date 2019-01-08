-- MySQL dump 10.13  Distrib 5.7.20, for Linux (x86_64)
--
-- Host: localhost    Database: byteball
-- ------------------------------------------------------
-- Server version	5.7.20-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bbfm`
--

DROP TABLE IF EXISTS `bbfm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_creation` datetime NOT NULL,
  `ref_merchant` int(10) unsigned NOT NULL DEFAULT '0',
  `WC_BBFM_VERSION` varchar(10) DEFAULT NULL,
  `mode` enum('live','test') NOT NULL,
  `remote_addr` varchar(15) DEFAULT NULL,
  `merchant_order_UID` varchar(20) NOT NULL,
  `url_notif` varchar(255) DEFAULT NULL,
  `get_or_post_notif` enum('get','post') DEFAULT NULL,
  `email_notif` varchar(255) DEFAULT NULL,
  `currency` varchar(4) DEFAULT NULL,
  `amount_asked_in_currency` bigint(20) unsigned NOT NULL,
  `address_merchant` char(32) DEFAULT NULL,
  `currency_BB_rate` bigint(20) unsigned DEFAULT NULL,
  `amount_BB_asked` bigint(20) unsigned NOT NULL,
  `fee_bbfm` bigint(10) unsigned NOT NULL,
  `address_bbfm_index` int(10) unsigned DEFAULT NULL,
  `address_bbfm` char(32) DEFAULT NULL,
  `receive_unit` char(44) DEFAULT NULL,
  `receive_unit_date` datetime DEFAULT NULL,
  `receive_unit_confirmed` enum('0','1') DEFAULT NULL,
  `received_amount` bigint(10) unsigned DEFAULT NULL,
  `sent_amount` bigint(10) unsigned DEFAULT NULL,
  `sent_unit_date` datetime DEFAULT NULL,
  `sent_unit` char(44) DEFAULT NULL,
  `sent_unit_confirmed` enum('0','1') DEFAULT NULL,
  `global_status` enum('pending','error','sent','completed','receiving') NOT NULL DEFAULT 'pending',
  `error_msg` varchar(255) DEFAULT NULL,
  `receiving_url_notified` enum('ok','nok') DEFAULT NULL,
  `sent_url_notified` enum('ok','nok') DEFAULT NULL,
  `sent_email_notified` enum('ok','nok') DEFAULT NULL,
  `url_notified` enum('ok','nok') DEFAULT NULL,
  `email_notified` enum('ok','nok') DEFAULT NULL,
  `partner_cashback_percentage` tinyint(3) unsigned DEFAULT NULL,
  `customer` varchar(30) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `cashback_address` char(32) DEFAULT NULL,
  `ref_partner` int(10) unsigned DEFAULT NULL,
  `cashback_result` enum('ok','error') DEFAULT NULL,
  `cashback_error_msg` varchar(100) DEFAULT NULL,
  `cashback_amount` bigint(20) unsigned DEFAULT NULL,
  `cashback_unit` char(32) DEFAULT NULL,
  `cashback_notified` enum('ok','nok') DEFAULT NULL,
  `display_powered_by` enum('0','1') DEFAULT NULL,
  `attach` varchar(255) DEFAULT NULL,
  `qrcode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_bbfm` (`address_bbfm`),
  KEY `sent_unit` (`sent_unit`)
) ENGINE=MyISAM AUTO_INCREMENT=29405 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_currency_rate`
--

DROP TABLE IF EXISTS `bbfm_currency_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_currency_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(4) NOT NULL,
  `BTC_rate` decimal(20,9) unsigned NOT NULL,
  `last_update` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_ignored_received_unit`
--

DROP TABLE IF EXISTS `bbfm_ignored_received_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_ignored_received_unit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creation` datetime NOT NULL,
  `unit` char(44) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `unit` (`unit`)
) ENGINE=MyISAM AUTO_INCREMENT=466 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_merchant`
--

DROP TABLE IF EXISTS `bbfm_merchant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_merchant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(20) NOT NULL,
  `creation` datetime NOT NULL,
  `secret_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `secret_key` (`secret_key`),
  KEY `merchant_id` (`merchant_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_partner`
--

DROP TABLE IF EXISTS `bbfm_partner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_partner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partner` varchar(100) NOT NULL,
  `partner_key` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner` (`partner`),
  KEY `partner_key` (`partner_key`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_payment_duplicate`
--

DROP TABLE IF EXISTS `bbfm_payment_duplicate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_payment_duplicate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creation` datetime NOT NULL,
  `unit` char(44) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `bbfm_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit` (`unit`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_unknown_receiving_address`
--

DROP TABLE IF EXISTS `bbfm_unknown_receiving_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_unknown_receiving_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `address` char(32) NOT NULL,
  `creation` datetime NOT NULL,
  `unit` char(44) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4654 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bbfm_unknown_sent_unit`
--

DROP TABLE IF EXISTS `bbfm_unknown_sent_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bbfm_unknown_sent_unit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creation` datetime NOT NULL,
  `unit` char(44) NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-01-08  6:59:24
