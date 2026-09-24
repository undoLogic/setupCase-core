# Email Queuing System
# See docs/features/email-queue-feature.md for the full spec.

CREATE TABLE `email_queues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'Staff user who queued this email, not a recipient',
  `email_to` varchar(255) NOT NULL COMMENT 'Single or comma-delimited bare addresses (named email_to, not to, since TO is a MySQL reserved word)',
  `email_from` varchar(255) NOT NULL DEFAULT 'from@example.com' COMMENT 'Chosen from a fixed list on the create/edit form',
  `cc` text DEFAULT NULL COMMENT 'Comma delimited',
  `bcc` text DEFAULT NULL COMMENT 'Comma delimited',
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL COMMENT 'Arrives already translated',
  `language` char(2) NOT NULL DEFAULT 'en',
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL COMMENT 'Written on every send attempt, test or real',
  `error` text DEFAULT NULL COMMENT 'Last failure message, null on success',
  `removed` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sent_removed` (`sent`, `removed`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `email_queue_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_queue_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL COMMENT 'As uploaded, no hashing',
  `path` varchar(255) NOT NULL COMMENT 'Relative to webroot',
  `mime_type` varchar(127) NOT NULL,
  `size` int(11) NOT NULL COMMENT 'Bytes',
  `removed` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_queue_id` (`email_queue_id`),
  CONSTRAINT `email_queue_attachments_ibfk_1` FOREIGN KEY (`email_queue_id`) REFERENCES `email_queues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
