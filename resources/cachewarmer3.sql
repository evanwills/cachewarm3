-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 03, 2014 at 08:06 AM
-- Server version: 5.5.35
-- PHP Version: 5.4.4-14+deb7u7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cachewarmer3`
--

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `urls`;
CREATE TABLE IF NOT EXISTS `urls` (
  `url_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url_url` text NOT NULL COMMENT 'String reversed URL with http(s):// removed',
  `url_url_sub` CHAR(2) NOT NULL COMMENT 'Last two chars of the URL',
  `url_depth` tinyint(3) unsigned NOT NULL COMMENT 'How deep within the site the URL is',
  `url__url_status_id` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT 'Foreign key to the url_status table. The status of the URL',
  PRIMARY KEY			(`url_id`),
  KEY `IND_url_sub`		(`url_url_sub`),
  KEY `IND_url_depth`		(`url_depth`),
  KEY `IND_url__url_status_id`		(`url__url_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores unique URLs';

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `_url_status`;
CREATE TABLE IF NOT EXISTS `_url_status` (
  `url_status_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `url_status_name` varchar(8) NOT NULL COMMENT 'The status of the URL (e.g. "new", "good", "delete")',
  PRIMARY KEY				(`url_status_id`),
  UNIQUE KEY `IND_url_status_name`	(`url_status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Normalised table for URL statuses';

INSERT INTO `cachewarmer3`.`_url_status` ( `url_status_id` , `url_status_name` )
VALUES ( 1 , 'new' ) , ( 2 , 'good' ) , ( 3 , 'stale') , ( 4 , 'bad' ) , ( 5 , 'delete');
-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `url_by_protocol`;
CREATE TABLE IF NOT EXISTS `url_by_protocol` (
  `url_by_protocol__url_id` int(10) unsigned NOT NULL COMMENT 'Foreign key to the urls table. The unique ID of the URL',
  `url_by_protocol_https` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Whether or not this relates to a HTTPS version of a URL',
  `url_by_protocol_ok` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Whether or not the URL works for this url_by_protocol',
  `url_by_protocol_is_cached` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Whether or not the URL for this url_by_protocol is cached',
  `url_by_protocol_cache_expires` datetime DEFAULT NULL COMMENT 'Cache expiry time for the URL for this url_by_protocol',
  UNIQUE KEY `UNI_url_by_protocol_url_id__url_by_protocol_https` (`url_by_protocol__url_id`,`url_by_protocol_https`),
  KEY `IND_protocol_url_id`			(`url_by_protocol__url_id`),
  KEY `IND_protocol_https`			(`url_by_protocol_https`),
  KEY `IND_protocol_ok`				(`url_by_protocol_ok`),
  KEY `IND_protocol_is_cached`			(`url_by_protocol_is_cached`),
  KEY `IND__protocol_https__protocol_ok`	(`url_by_protocol_https`,`url_by_protocol_ok`),
  KEY `IND__protocol_https__protocol_is_cached`	(`url_by_protocol_https`,`url_by_protocol_is_cached`),
  KEY `IND__protocol_ok__protocol_is_cached`	(`url_by_protocol_ok`,`url_by_protocol_is_cached`),
  KEY `IND__protocol_https__protocol_ok__protocol_is_cached`	(`url_by_protocol_https`,`url_by_protocol_ok`,`url_by_protocol_is_cached`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Contains all the data relating to the URL via a given protocl';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
