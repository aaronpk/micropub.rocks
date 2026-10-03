ALTER TABLE `users`
  ADD COLUMN `webauthn_id` varchar(64) DEFAULT NULL,
  ADD COLUMN `date_created` datetime DEFAULT NULL,
  ADD UNIQUE KEY `webauthn_id` (`webauthn_id`);

CREATE TABLE `passkeys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` text NOT NULL,
  `label` varchar(64) NOT NULL DEFAULT 'Passkey',
  `sign_count` int(11) unsigned NOT NULL DEFAULT '0',
  `aaguid` varchar(32) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `date_last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
