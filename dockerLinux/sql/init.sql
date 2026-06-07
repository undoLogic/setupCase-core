-- phpMyAdmin SQL Dump
-- version
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 15, 2026 at 10:35 PM
-- Server version: 5.7.44-percona-sure1b-log
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `undoweb_codeblocks`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
                              `id` bigint(20) UNSIGNED NOT NULL,
                              `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
                              `action` enum('insert','update','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
                              `changed_fields` json DEFAULT NULL,
                              `original_fields` json DEFAULT NULL,
                              `user_id` bigint(20) UNSIGNED DEFAULT NULL,
                              `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                              `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `code_blocks`
--

CREATE TABLE `code_blocks` (
                               `id` int(11) NOT NULL,
                               `code_block_type_id` int(11) NOT NULL,
                               `name` varchar(99) NOT NULL,
                               `description` text NOT NULL,
                               `created` datetime NOT NULL,
                               `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `code_blocks`
--

INSERT INTO `code_blocks` (`id`, `code_block_type_id`, `name`, `description`, `created`, `modified`) VALUES
                                                                                                         (15, 1, 'ONE111111', '1111111111111', '2026-01-18 20:30:31', '2026-01-18 20:37:17'),
                                                                                                         (16, 1, 'name1234', 'description ', '2026-01-19 22:54:30', '2026-01-19 22:54:30'),
                                                                                                         (17, 2, 'name3333', 'description 3333', '2026-01-19 22:55:06', '2026-01-19 22:55:06');


--
-- Table structure for table `email_queues`
--

CREATE TABLE `email_queues` (
                                `id` int(11) NOT NULL,
                                `user_id` int(11) NOT NULL,
                                `message_subject` varchar(255) NOT NULL,
                                `message_html` text NOT NULL,
                                `message_text` text NOT NULL,
                                `email_to` varchar(50) NOT NULL,
                                `email_from` varchar(50) NOT NULL,
                                `lang` varchar(25) NOT NULL,
                                `sent` tinyint(1) NOT NULL DEFAULT '0',
                                `sent_date` datetime NOT NULL,
                                `response` text,
                                `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                `modified` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue_attachments`
--

CREATE TABLE `email_queue_attachments` (
                                           `id` int(11) NOT NULL,
                                           `email_queue_id` int(11) NOT NULL,
                                           `attachment` text NOT NULL,
                                           `created` datetime NOT NULL,
                                           `modified` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
                          `id` int(11) NOT NULL,
                          `name` varchar(100) NOT NULL,
                          `created` datetime NOT NULL,
                          `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `created`, `modified`) VALUES
                                                               (1, 'Main Group', '2022-11-08 18:48:17', '2022-11-08 18:48:17'),
                                                               (2, 'Secondary Group', '2022-11-08 18:48:17', '2022-11-08 18:48:17');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
                             `id` int(11) NOT NULL,
                             `name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`) VALUES
                                           (1, 'Location1111'),
                                           (2, 'Location2'),
                                           (3, 'Location3');

-- --------------------------------------------------------

--
-- Table structure for table `locations_users`
--

CREATE TABLE `locations_users` (
                                   `id` int(11) NOT NULL,
                                   `location_id` int(11) NOT NULL,
                                   `user_id` int(11) NOT NULL,
                                   `created` datetime NOT NULL,
                                   `modified` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `locations_users`
--

INSERT INTO `locations_users` (`id`, `location_id`, `user_id`, `created`, `modified`) VALUES
    (6, 1, 11, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--


CREATE TABLE `users` (
                         `id` int(11) NOT NULL,
                         `user_type` varchar(19) NOT NULL,
                         `email` varchar(99) DEFAULT NULL,
                         `password` varchar(1000) DEFAULT NULL,
                         `reset_token` varchar(1000) DEFAULT NULL,
                         `created` datetime NOT NULL,
                         `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
    ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
    ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_entity` (`table_name`,`entity_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created`);

--
-- Indexes for table `code_blocks`
--
ALTER TABLE `code_blocks`
    ADD PRIMARY KEY (`id`);


--
-- Indexes for table `email_queues`
--
ALTER TABLE `email_queues`
    ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_sent` (`sent`);

--
-- Indexes for table `email_queue_attachments`
--
ALTER TABLE `email_queue_attachments`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations_users`
--
ALTER TABLE `locations_users`
    ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `code_blocks`
--
ALTER TABLE `code_blocks`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;




CREATE TABLE `code_block_types` (
                                    `id` int(11) NOT NULL,
                                    `name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `code_block_types`
--

INSERT INTO `code_block_types` (`id`, `name`) VALUES
                                                  (1, 'Type1'),
                                                  (2, 'Type2');
ALTER TABLE `code_block_types`
    ADD PRIMARY KEY (`id`);
ALTER TABLE `code_block_types`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;








--
-- AUTO_INCREMENT for table `email_queues`
--
ALTER TABLE `email_queues`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `email_queue_attachments`
--
ALTER TABLE `email_queue_attachments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `locations_users`
--
ALTER TABLE `locations_users`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;







CREATE TABLE `LIVE_database`.`form_attempts` (`id` INT NOT NULL AUTO_INCREMENT , `date` DATETIME NOT NULL , `ip` VARCHAR(20) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;








CREATE DATABASE IF NOT EXISTS test_automation;