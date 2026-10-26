# Login Throttling
# See docs/features/MVP-login_throttling.md for the full spec.

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6',
  `username_hash` char(64) NOT NULL COMMENT 'SHA-256 of trimmed, lowercased username; raw username is never stored',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_username_created` (`ip`, `username_hash`, `created`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
