-- phpMyAdmin SQL Dump
-- version 4.1.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 11, 2014 at 11:34 AM
-- Server version: 5.1.71-log
-- PHP Version: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `connect_shared`
--

-- --------------------------------------------------------

--
-- Table structure for table `shared_courses`
--

CREATE TABLE IF NOT EXISTS `shared_courses` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `moodle_env` varchar(24) NOT NULL,
  `moodle_dist` varchar(24) NOT NULL,
  `moodle_id` bigint(11) NOT NULL,
  `shortname` varchar(255) NOT NULL,
  `fullname` varchar(254) NOT NULL,
  `summary` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_moodle_env_moodle_dist_moodle_id` (`moodle_env`,`moodle_dist`,`moodle_id`),
  KEY `unique_moodle_env_moodle_dist` (`moodle_env`,`moodle_dist`),
  KEY `unique_moodle_id` (`moodle_id`),
  KEY `unique_shortname` (`shortname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='1';
