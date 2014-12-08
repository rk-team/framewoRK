-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 24, 2014 at 02:00 PM
-- Server version: 5.5.38
-- PHP Version: 5.5.17-1~dotdeb.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `trackeet`
--
CREATE DATABASE `trackeet` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `trackeet`;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`, `description`, `date_added`, `date_updated`) VALUES
(1, 'PHP', 'Toute remarque relative au PHP', NULL, NULL),
(2, 'Javascript', 'Toute remarque relative au Javascript', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE IF NOT EXISTS `report` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL,
  `author` varchar(250) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin,
  `name` varchar(250) COLLATE utf8_bin NOT NULL,
  `type` enum('EVOL','BUG','DOC') COLLATE utf8_bin NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2 ;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`id`, `category_id`, `author`, `description`, `name`, `type`, `date_added`, `date_updated`) VALUES
(1, 1, 'rophle', 'My first report !', 'First report', 'EVOL', '2014-11-24 13:45:36', '2014-11-24 13:45:36');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`);

