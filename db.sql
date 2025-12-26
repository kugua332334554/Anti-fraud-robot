SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `fanzhasbzhapianfan` (
  `id` int(11) NOT NULL,
  `audit_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint(20) NOT NULL,
  `msg_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fanzhaunshenhe` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitter_id` bigint(20) NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `media_group_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `msg_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fanzhauser` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0正常, 1被封禁'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fanzha_temp_media` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `media_group_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `fanzhasbzhapianfan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `target_id` (`target_id`),
  ADD KEY `idx_target_id` (`target_id`),
  ADD KEY `idx_audit_id` (`audit_id`);

ALTER TABLE `fanzhaunshenhe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target_id` (`target_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submitter_id` (`submitter_id`);

ALTER TABLE `fanzhauser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

ALTER TABLE `fanzha_temp_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_target_group` (`user_id`,`target_id`,`media_group_id`),
  ADD KEY `idx_media_group_id` (`media_group_id`);

ALTER TABLE `fanzhasbzhapianfan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `fanzhauser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `fanzha_temp_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
