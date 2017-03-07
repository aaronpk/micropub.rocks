CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `auth_code` varchar(64) DEFAULT NULL,
  `auth_code_exp` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group` enum('server','client') DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `group_number` (`group`,`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `test_results` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `endpoint_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `passed` tinyint(4) DEFAULT NULL,
  `response` blob,
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_result_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `endpoint_id` (`endpoint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `micropub_endpoints` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `me` varchar(255) DEFAULT NULL,
  `micropub_endpoint` varchar(255) DEFAULT NULL,
  `access_token` text,
  `scope` varchar(255) DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `last_test_at` datetime DEFAULT NULL,
  `share_token` varchar(255) DEFAULT NULL,
  `implementation_name` varchar(255) DEFAULT NULL,
  `implementation_url` varchar(255) DEFAULT NULL,
  `programming_language` varchar(255) DEFAULT NULL,
  `developer_name` varchar(255) DEFAULT NULL,
  `developer_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `micropub_clients` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `redirect_uri` varchar(255) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `last_viewed_test` int(11) NOT NULL DEFAULT '100',
  `created_at` datetime DEFAULT NULL,
  `share_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `features` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group` enum('server','client') DEFAULT NULL,
  `number` int(11) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `tests` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `feature_results` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feature_num` int(11) NOT NULL,
  `endpoint_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `source_test_id` int(11) DEFAULT NULL,
  `implements` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
