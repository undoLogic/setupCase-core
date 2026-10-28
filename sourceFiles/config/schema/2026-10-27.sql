# Files (Local-First S3 Offload)
# See docs/features/MVP-Files-S3-Offload.md for the full spec.

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Also the folder name on disk and in the bucket',
  `user_id` int(11) NOT NULL COMMENT 'Uploader; every lookup filters on it',
  `filename` varchar(255) NOT NULL COMMENT 'Original filename, shown to users and used on download',
  `mime_type` varchar(127) NOT NULL COMMENT 'Detected server-side with finfo',
  `size_bytes` bigint(20) NOT NULL,
  `checksum` char(64) DEFAULT NULL COMMENT 'SHA-256',
  `local_path` varchar(500) DEFAULT NULL COMMENT 'Relative to FILES_LOCAL_PATH; null once deleted locally',
  `storage_key` varchar(500) DEFAULT NULL COMMENT 'Key in the bucket; set when pushed',
  `thumb_path` varchar(500) DEFAULT NULL COMMENT 'Generated thumbnail, relative to FILES_LOCAL_PATH; never deleted locally',
  `thumb_key` varchar(500) DEFAULT NULL COMMENT 'Thumbnail key in the bucket; set when pushed',
  `status` varchar(20) NOT NULL DEFAULT 'local' COMMENT 'local, uploading, remote, failed',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL COMMENT 'When the S3 copy was confirmed',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
