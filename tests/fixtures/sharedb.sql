-- phpMyAdmin SQL Dump
-- version 4.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 16, 2015 at 01:02 PM
-- Server version: 5.6.22-72.0-log
-- PHP Version: 5.5.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `connect_development`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT '',
  `content` text,
  `time` datetime DEFAULT NULL,
  `seen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rollovers`
--

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
-- Table structure for table `shared_config`
--

CREATE TABLE IF NOT EXISTS `shared_config` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shared_courses`
--

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

CREATE TABLE IF NOT EXISTS `shared_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`), ADD KEY `index_notifications_on_seen` (`seen`), ADD KEY `index_notifications_on_uid` (`username`);

--
-- Indexes for table `rollovers`
--
ALTER TABLE `rollovers`
  ADD PRIMARY KEY (`id`), ADD KEY `index_rollovers_on_status` (`status`), ADD KEY `index_rollovers_on_from_env` (`from_env`), ADD KEY `index_rollovers_on_from_dist` (`from_dist`), ADD KEY `index_rollovers_on_to_env` (`to_env`), ADD KEY `index_rollovers_on_to_dist` (`to_dist`), ADD KEY `index_rollovers_on_requester` (`requester`), ADD KEY `index_rollovers_to_course` (`to_course`), ADD KEY `index_rollovers_from_course` (`from_course`), ADD KEY `index_rollovers_status` (`status`);

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
-- AUTO_INCREMENT for dumped tables
--

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