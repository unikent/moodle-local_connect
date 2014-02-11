CREATE TABLE IF NOT EXISTS `course_list` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='1'