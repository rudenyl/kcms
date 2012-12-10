DROP TABLE IF EXISTS `tbl_modules`;
CREATE TABLE `tbl_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `title` text NOT NULL,
  `data` text NOT NULL,
  `showtitle` tinyint(1) NOT NULL DEFAULT '0',
  `position` varchar(50) DEFAULT NULL,
  `access` tinyint(1) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_redirection`;
CREATE TABLE `tbl_redirection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hits` int(11) NOT NULL DEFAULT '0',
  `oldurl` varchar(255) NOT NULL DEFAULT '',
  `newurl` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `newurl` (`newurl`),
  UNIQUE KEY `oldurl` (`oldurl`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_roles`;
CREATE TABLE `tbl_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(80) NOT NULL DEFAULT '',
  `ordering` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `roleName` (`title`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_permissions`;
CREATE TABLE `tbl_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `app` varchar(40) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`key`,`app`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_role_permissions`;
CREATE TABLE `tbl_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT '0',
  `permission_id` int(11) NOT NULL DEFAULT '0',
  `value` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_id_key` (`role_id`,`permission_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_users_permissions`;
CREATE TABLE `tbl_users_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `permission_id` int(11) NOT NULL DEFAULT '0',
  `value` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`permission_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_session`;
CREATE TABLE `tbl_session` (
  `sess_id` varchar(255) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `sess_expires` int(10) NOT NULL DEFAULT '0',
  `sess_data` text
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `password` varchar(80) NOT NULL,
  `level` varchar(40) NOT NULL DEFAULT 'user',
  `email` varchar(125) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `parent` int(11) NOT NULL DEFAULT '0',
  `registerDate` datetime DEFAULT NULL,
  `lastvisit` datetime DEFAULT NULL,
  `activation` varchar(128) NOT NULL DEFAULT '',
  `sendNotice` tinyint(1) NOT NULL DEFAULT '0',
  `params` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM;
