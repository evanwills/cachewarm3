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
-- Table structure for table `places`
--

DROP TABLE IF EXISTS `places`;
CREATE TABLE IF NOT EXISTS `places` (
  `place_name` varchar(32) NOT NULL,
  `place__url_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `place_name` (`place_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `urls`;
CREATE TABLE IF NOT EXISTS `urls` (
  `url_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url_url` text NOT NULL COMMENT 'string reversed URL with http(s):// removed',
  `url_http` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Whether or not the URL works for plain HTTP',
  `url_http_cached` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'whether or not the HTTP url is cached',
  `url_http_cache_expires` datetime DEFAULT NULL COMMENT 'cache expiry time for HTTP URL',
  `url_https` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Whether or not the URL works for HTTPS',
  `url_https_cached` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'whether or not the HTTPS url is cached',
  `url_https_cache_expires` datetime DEFAULT NULL COMMENT 'cache expiry time for HTTPS URL',
  PRIMARY KEY (`url_id`),
  KEY `IND_url_http` (`url_http`),
  KEY `IND_url_http_cached` (`url_http_nocache`),
  KEY `IND_url_https` (`url_https`),
  KEY `IND_url_https_cached` (`url_https_nocache`),
  KEY `IND__url_https__url_https_cached` (`url_https`,`url_https_nocache`),
  KEY `IND__url_http__url_http_cached` (`url_http`,`url_http_nocache`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
