-- phpMyAdmin SQL Dump
-- version 4.1.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 10, 2014 at 10:01 AM
-- Server version: 5.1.71-log
-- PHP Version: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `connect_development`
--

-- --------------------------------------------------------

--
-- Table structure for table `aspire_sink`
--

CREATE TABLE IF NOT EXISTS `aspire_sink` (
  `login` varchar(255) DEFAULT NULL,
  `shortnames` text,
  `chksum` varchar(255) DEFAULT NULL,
  UNIQUE KEY `index_aspire_sink_on_chksum` (`chksum`),
  UNIQUE KEY `index_aspire_sink_on_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE IF NOT EXISTS `courses` (
  `module_delivery_key` varchar(36) DEFAULT NULL,
  `session_code` varchar(4) DEFAULT NULL,
  `delivery_department` varchar(4) DEFAULT NULL,
  `campus` varchar(255) DEFAULT NULL,
  `module_version` varchar(4) DEFAULT NULL,
  `campus_desc` varchar(255) DEFAULT NULL,
  `module_week_beginning` varchar(4) DEFAULT NULL,
  `module_length` varchar(4) DEFAULT NULL,
  `module_title` varchar(255) DEFAULT NULL,
  `module_code` varchar(255) DEFAULT NULL,
  `chksum` varchar(36) DEFAULT NULL,
  `moodle_id` int(11) DEFAULT NULL,
  `sink_deleted` tinyint(1) DEFAULT '0',
  `state` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `synopsis` text,
  `week_beginning_date` datetime DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `parent_id` varchar(36) DEFAULT NULL,
  `student_count` int(11) DEFAULT '0',
  `teacher_count` int(11) DEFAULT '0',
  `convenor_count` int(11) DEFAULT '0',
  `link` tinyint(1) DEFAULT '0',
  `json_cache` text,
  `primary_child` varchar(36) DEFAULT NULL,
  `id_chksum` varchar(36) DEFAULT NULL,
  `last_checked` datetime DEFAULT NULL,
  UNIQUE KEY `index_courses_on_chksum` (`chksum`),
  KEY `index_courses_on_module_delivery_key` (`module_delivery_key`),
  KEY `index_courses_on_session_code` (`session_code`),
  KEY `index_courses_on_state` (`state`),
  KEY `index_courses_on_parent_id` (`parent_id`),
  KEY `index_courses_on_session_delivery` (`session_code`,`module_delivery_key`),
  KEY `index_courses_on_id_chksum` (`id_chksum`),
  KEY `index_courses_on_primary_child` (`primary_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE IF NOT EXISTS `enrollments` (
  `ukc` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `initials` varchar(255) DEFAULT NULL,
  `family_name` varchar(255) DEFAULT NULL,
  `session_code` varchar(4) DEFAULT NULL,
  `module_delivery_key` varchar(36) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `chksum` varchar(36) DEFAULT NULL,
  `moodle_id` int(11) DEFAULT NULL,
  `sink_deleted` tinyint(1) DEFAULT '0',
  `state` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `id_chksum` varchar(36) DEFAULT NULL,
  `last_checked` datetime DEFAULT NULL,
  UNIQUE KEY `index_enrollments_on_chksum` (`chksum`),
  KEY `index_enrollments_on_login` (`login`),
  KEY `index_enrollments_on_module_delivery_key` (`module_delivery_key`),
  KEY `index_enrollments_on_session_code` (`session_code`),
  KEY `index_enrollments_on_state` (`state`),
  KEY `index_enrollments_on_session_delivery_login` (`session_code`,`module_delivery_key`,`login`),
  KEY `index_enrollments_on_session_delivery_role_login` (`session_code`,`module_delivery_key`,`role`,`login`),
  KEY `index_enrollments_on_id_chksum` (`id_chksum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `group_id` varchar(255) DEFAULT NULL,
  `group_desc` varchar(255) DEFAULT NULL,
  `module_delivery_key` varchar(36) DEFAULT NULL,
  `session_code` varchar(4) DEFAULT NULL,
  `moodle_id` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `chksum` varchar(36) DEFAULT NULL,
  `id_chksum` varchar(36) DEFAULT NULL,
  `last_checked` datetime DEFAULT NULL,
  UNIQUE KEY `index_groups_on_chksum` (`chksum`),
  KEY `index_groups_on_group_id` (`group_id`),
  KEY `index_groups_on_state` (`state`),
  KEY `index_groups_on_session_delivery` (`session_code`,`module_delivery_key`),
  KEY `index_groups_on_id_chksum` (`id_chksum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_enrollments`
--

CREATE TABLE IF NOT EXISTS `group_enrollments` (
  `group_id` varchar(255) DEFAULT NULL,
  `group_desc` varchar(255) DEFAULT NULL,
  `module_delivery_key` varchar(36) DEFAULT NULL,
  `ukc` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `session_code` varchar(4) DEFAULT NULL,
  `chksum` varchar(36) DEFAULT NULL,
  `sink_deleted` tinyint(1) DEFAULT '0',
  `moodle_id` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `id_chksum` varchar(36) DEFAULT NULL,
  `last_checked` datetime DEFAULT NULL,
  UNIQUE KEY `index_group_enrollments_on_chksum` (`chksum`),
  KEY `index_group_enrollments_on_login` (`login`),
  KEY `index_group_enrollments_on_module_delivery_key` (`module_delivery_key`),
  KEY `index_group_enrollments_on_session_code` (`session_code`),
  KEY `index_group_enrollments_on_state` (`state`),
  KEY `index_group_enrollments_on_session_delivery_login` (`session_code`,`module_delivery_key`,`login`),
  KEY `index_group_enrollments_on_id_chksum` (`id_chksum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) DEFAULT NULL,
  `content` text,
  `time` datetime DEFAULT NULL,
  `seen` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_notifications_on_seen` (`seen`),
  KEY `index_notifications_on_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rollovers`
--

CREATE TABLE IF NOT EXISTS `rollovers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) DEFAULT NULL,
  `from_mdl` int(11) DEFAULT NULL,
  `to_mdl` int(11) DEFAULT NULL,
  `courseid` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_rollovers_on_uid` (`uid`),
  KEY `index_rollovers_on_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rules`
--

CREATE TABLE IF NOT EXISTS `rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note` varchar(255) DEFAULT NULL,
  `sds_category` int(11) DEFAULT NULL,
  `rule` varchar(255) DEFAULT NULL,
  `mdl_category` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_rules_on_rule` (`rule`),
  KEY `index_rules_on_sds_category` (`sds_category`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(255) NOT NULL,
  UNIQUE KEY `unique_schema_migrations` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `student_talis_sink`
--

CREATE TABLE IF NOT EXISTS `student_talis_sink` (
  `login` varchar(255) DEFAULT NULL,
  `shortnames` text,
  `chksum` varchar(255) DEFAULT NULL,
  UNIQUE KEY `index_talis_student_sink_on_chksum` (`chksum`),
  UNIQUE KEY `index_talis_student_sink_on_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
