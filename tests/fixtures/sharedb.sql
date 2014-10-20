-- phpMyAdmin SQL Dump
-- version 4.2.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 20, 2014 at 04:32 PM
-- Server version: 5.6.21-69.0-log
-- PHP Version: 5.5.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `connect_development`
--

-- --------------------------------------------------------

--
-- Table structure for table `aspire_sink`
--

DROP TABLE IF EXISTS `aspire_sink`;
CREATE TABLE IF NOT EXISTS `aspire_sink` (
  `login` varchar(255) DEFAULT NULL,
  `shortnames` text,
  `chksum` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
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
`id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
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
`id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
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
`id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_enrollments`
--

DROP TABLE IF EXISTS `group_enrollments`;
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
`id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
`id` int(11) NOT NULL,
  `uid` varchar(255) DEFAULT NULL,
  `content` text,
  `time` datetime DEFAULT NULL,
  `seen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rollovers`
--

DROP TABLE IF EXISTS `rollovers`;
CREATE TABLE IF NOT EXISTS `rollovers` (
`id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `from_env` varchar(255) DEFAULT NULL,
  `from_dist` varchar(255) DEFAULT NULL,
  `from_course` int(11) DEFAULT NULL,
  `to_env` varchar(255) DEFAULT NULL,
  `to_dist` varchar(255) DEFAULT NULL,
  `to_course` int(11) DEFAULT NULL,
  `path` text,
  `options` text,
  `requester` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rules`
--

DROP TABLE IF EXISTS `rules`;
CREATE TABLE IF NOT EXISTS `rules` (
`id` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sds_category` int(11) DEFAULT NULL,
  `rule` varchar(255) DEFAULT NULL,
  `mdl_category` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_config`
--

DROP TABLE IF EXISTS `shared_config`;
CREATE TABLE IF NOT EXISTS `shared_config` (
`id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_courses`
--

DROP TABLE IF EXISTS `shared_courses`;
CREATE TABLE IF NOT EXISTS `shared_courses` (
`id` int(11) NOT NULL,
  `moodle_env` varchar(255) DEFAULT NULL,
  `moodle_dist` varchar(255) DEFAULT NULL,
  `moodle_id` int(11) DEFAULT NULL,
  `shortname` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `summary` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_course_admins`
--

DROP TABLE IF EXISTS `shared_course_admins`;
CREATE TABLE IF NOT EXISTS `shared_course_admins` (
`id` int(11) NOT NULL,
  `moodle_env` varchar(255) DEFAULT NULL,
  `moodle_dist` varchar(255) DEFAULT NULL,
  `courseid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_roles`
--

DROP TABLE IF EXISTS `shared_roles`;
CREATE TABLE IF NOT EXISTS `shared_roles` (
`id` int(11) NOT NULL,
  `moodle_env` varchar(255) DEFAULT NULL,
  `moodle_dist` varchar(255) DEFAULT NULL,
  `roleid` int(11) DEFAULT NULL,
  `shortname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_role_assignments`
--

DROP TABLE IF EXISTS `shared_role_assignments`;
CREATE TABLE IF NOT EXISTS `shared_role_assignments` (
`id` int(11) NOT NULL,
  `moodle_env` varchar(255) DEFAULT NULL,
  `moodle_dist` varchar(255) DEFAULT NULL,
  `roleid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `migration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_users`
--

DROP TABLE IF EXISTS `shared_users`;
CREATE TABLE IF NOT EXISTS `shared_users` (
`id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `student_talis_sink`
--

DROP TABLE IF EXISTS `student_talis_sink`;
CREATE TABLE IF NOT EXISTS `student_talis_sink` (
  `login` varchar(255) DEFAULT NULL,
  `shortnames` text,
  `chksum` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `timetabling`
--

DROP TABLE IF EXISTS `timetabling`;
CREATE TABLE IF NOT EXISTS `timetabling` (
  `event_number` int(11) DEFAULT NULL,
  `session_code` varchar(255) DEFAULT NULL,
  `module_delivery_key` varchar(255) DEFAULT NULL,
  `module_code` varchar(255) DEFAULT NULL,
  `module_version` varchar(255) DEFAULT NULL,
  `campus` varchar(255) DEFAULT NULL,
  `campus_desc` varchar(255) DEFAULT NULL,
  `module_week_beginning` varchar(255) DEFAULT NULL,
  `week_beginning_date` varchar(255) DEFAULT NULL,
  `module_title` varchar(255) DEFAULT NULL,
  `activity_start` varchar(255) DEFAULT NULL,
  `activity_end` varchar(255) DEFAULT NULL,
  `activity_day` varchar(255) DEFAULT NULL,
  `activity_type` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `weeks` varchar(255) DEFAULT NULL,
`id` int(11) NOT NULL,
  `chksum` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `week_beginning`
--

DROP TABLE IF EXISTS `week_beginning`;
CREATE TABLE IF NOT EXISTS `week_beginning` (
  `session_code` varchar(255) DEFAULT NULL,
  `week_beginning` varchar(255) DEFAULT NULL,
  `week_beginning_date` varchar(255) DEFAULT NULL,
  `week_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aspire_sink`
--
ALTER TABLE `aspire_sink`
 ADD UNIQUE KEY `index_aspire_sink_on_chksum` (`chksum`), ADD UNIQUE KEY `index_aspire_sink_on_login` (`login`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `index_courses_on_chksum` (`chksum`), ADD KEY `index_courses_on_module_delivery_key` (`module_delivery_key`), ADD KEY `index_courses_on_session_code` (`session_code`), ADD KEY `index_courses_on_state` (`state`), ADD KEY `index_courses_on_parent_id` (`parent_id`), ADD KEY `index_courses_on_session_delivery` (`session_code`,`module_delivery_key`), ADD KEY `index_courses_on_id_chksum` (`id_chksum`), ADD KEY `index_courses_on_primary_child` (`primary_child`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `index_enrollments_on_chksum` (`chksum`), ADD KEY `index_enrollments_on_login` (`login`), ADD KEY `index_enrollments_on_module_delivery_key` (`module_delivery_key`), ADD KEY `index_enrollments_on_session_code` (`session_code`), ADD KEY `index_enrollments_on_state` (`state`), ADD KEY `index_enrollments_on_session_delivery_login` (`session_code`,`module_delivery_key`,`login`), ADD KEY `index_enrollments_on_session_delivery_role_login` (`session_code`,`module_delivery_key`,`role`,`login`), ADD KEY `index_enrollments_on_id_chksum` (`id_chksum`), ADD KEY `index_enrollments_sink_deleted` (`sink_deleted`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `index_groups_on_chksum` (`chksum`), ADD KEY `index_groups_on_group_id` (`group_id`), ADD KEY `index_groups_on_state` (`state`), ADD KEY `index_groups_on_session_delivery` (`session_code`,`module_delivery_key`), ADD KEY `index_groups_on_id_chksum` (`id_chksum`);

--
-- Indexes for table `group_enrollments`
--
ALTER TABLE `group_enrollments`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `index_group_enrollments_on_chksum` (`chksum`), ADD KEY `index_group_enrollments_on_login` (`login`), ADD KEY `index_group_enrollments_on_module_delivery_key` (`module_delivery_key`), ADD KEY `index_group_enrollments_on_session_code` (`session_code`), ADD KEY `index_group_enrollments_on_state` (`state`), ADD KEY `index_group_enrollments_on_session_delivery_login` (`session_code`,`module_delivery_key`,`login`), ADD KEY `index_group_enrollments_on_id_chksum` (`id_chksum`), ADD KEY `index_group_enrollments_sink_deleted` (`sink_deleted`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
 ADD PRIMARY KEY (`id`), ADD KEY `index_notifications_on_seen` (`seen`), ADD KEY `index_notifications_on_uid` (`uid`);

--
-- Indexes for table `rollovers`
--
ALTER TABLE `rollovers`
 ADD PRIMARY KEY (`id`), ADD KEY `index_rollovers_on_status` (`status`), ADD KEY `index_rollovers_on_from_env` (`from_env`), ADD KEY `index_rollovers_on_from_dist` (`from_dist`), ADD KEY `index_rollovers_on_to_env` (`to_env`), ADD KEY `index_rollovers_on_to_dist` (`to_dist`), ADD KEY `index_rollovers_on_requester` (`requester`), ADD KEY `index_rollovers_to_course` (`to_course`), ADD KEY `index_rollovers_from_course` (`from_course`), ADD KEY `index_rollovers_status` (`status`);

--
-- Indexes for table `rules`
--
ALTER TABLE `rules`
 ADD PRIMARY KEY (`id`), ADD KEY `index_rules_on_rule` (`rule`), ADD KEY `index_rules_on_sds_category` (`sds_category`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
 ADD UNIQUE KEY `unique_schema_migrations` (`version`);

--
-- Indexes for table `shared_config`
--
ALTER TABLE `shared_config`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_config_on_name` (`name`);

--
-- Indexes for table `shared_courses`
--
ALTER TABLE `shared_courses`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_courses_on_moodle_env` (`moodle_env`), ADD KEY `index_shared_courses_on_moodle_dist` (`moodle_dist`), ADD KEY `index_shared_courses_on_moodle_id` (`moodle_id`);

--
-- Indexes for table `shared_course_admins`
--
ALTER TABLE `shared_course_admins`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_course_admins_on_moodle_env` (`moodle_env`), ADD KEY `index_shared_course_admins_on_moodle_dist` (`moodle_dist`), ADD KEY `index_shared_course_admins_on_courseid` (`courseid`), ADD KEY `index_shared_course_admins_on_username` (`username`);

--
-- Indexes for table `shared_roles`
--
ALTER TABLE `shared_roles`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_course_list_on_moodle_env` (`moodle_env`), ADD KEY `index_shared_course_list_on_moodle_dist` (`moodle_dist`), ADD KEY `index_shared_course_list_on_shortname` (`shortname`);

--
-- Indexes for table `shared_role_assignments`
--
ALTER TABLE `shared_role_assignments`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_role_assignments_on_moodle_env` (`moodle_env`), ADD KEY `index_shared_role_assignments_on_moodle_dist` (`moodle_dist`), ADD KEY `index_shared_role_assignments_on_roleid` (`roleid`), ADD KEY `index_shared_role_assignments_on_migration` (`migration`);

--
-- Indexes for table `shared_users`
--
ALTER TABLE `shared_users`
 ADD PRIMARY KEY (`id`), ADD KEY `index_shared_users_on_username` (`username`);

--
-- Indexes for table `student_talis_sink`
--
ALTER TABLE `student_talis_sink`
 ADD UNIQUE KEY `index_talis_student_sink_on_chksum` (`chksum`), ADD UNIQUE KEY `index_talis_student_sink_on_login` (`login`);

--
-- Indexes for table `timetabling`
--
ALTER TABLE `timetabling`
 ADD PRIMARY KEY (`id`), ADD KEY `index_timetabling_on_module_delivery_key` (`module_delivery_key`), ADD KEY `index_timetabling_on_session_code` (`session_code`), ADD KEY `index_timetabling_on_login` (`login`), ADD KEY `index_timetabling_on_venue` (`venue`), ADD KEY `index_timetabling_on_event_number` (`event_number`), ADD KEY `index_timetabling_on_chksum` (`chksum`);

--
-- Indexes for table `week_beginning`
--
ALTER TABLE `week_beginning`
 ADD KEY `index_week_beginning_on_session_code` (`session_code`), ADD KEY `index_week_beginning_on_week_beginning` (`week_beginning`), ADD KEY `index_week_beginning_on_week_number` (`week_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `group_enrollments`
--
ALTER TABLE `group_enrollments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `rollovers`
--
ALTER TABLE `rollovers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `rules`
--
ALTER TABLE `rules`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_config`
--
ALTER TABLE `shared_config`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_courses`
--
ALTER TABLE `shared_courses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_course_admins`
--
ALTER TABLE `shared_course_admins`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_roles`
--
ALTER TABLE `shared_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_role_assignments`
--
ALTER TABLE `shared_role_assignments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shared_users`
--
ALTER TABLE `shared_users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `timetabling`
--
ALTER TABLE `timetabling`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;