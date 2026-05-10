-- KHFinaM initial schema — MySQL 8+ utf8mb4 InnoDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `transaction_edit_history`;
DROP TABLE IF EXISTS `transaction_attachments`;
DROP TABLE IF EXISTS `transaction_tags`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `recurring_schedules`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `backups`;
DROP TABLE IF EXISTS `wallets`;
DROP TABLE IF EXISTS `wallet_types`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `currencies`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `rate_limit_buckets`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','user') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(128) DEFAULT NULL,
  `remember_expires_at` datetime DEFAULT NULL,
  `failed_login_attempts` smallint unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `preference_theme` enum('light','dark','system') NOT NULL DEFAULT 'system',
  `preference_mute_low_balance` tinyint(1) NOT NULL DEFAULT 0,
  `include_in_analytics` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `password_resets_user_id` (`user_id`),
  CONSTRAINT `password_resets_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rate_limit_buckets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bucket_key` varchar(191) NOT NULL,
  `window_start` int unsigned NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rate_limit_buckets_unique` (`bucket_key`,`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `currencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(12) NOT NULL,
  `name` varchar(64) NOT NULL,
  `symbol` varchar(8) NOT NULL DEFAULT '',
  `decimal_places` tinyint unsigned NOT NULL DEFAULT 2,
  `is_base` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `exchange_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_currency_id` int unsigned NOT NULL,
  `to_currency_id` int unsigned NOT NULL,
  `rate` decimal(24,12) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exchange_rates_lookup` (`from_currency_id`,`to_currency_id`,`effective_date`),
  CONSTRAINT `exchange_rates_from_fk` FOREIGN KEY (`from_currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_rates_to_fk` FOREIGN KEY (`to_currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `slug` varchar(128) DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `color` varchar(16) DEFAULT '#6366f1',
  `icon` varchar(64) DEFAULT 'category',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categories_user_id` (`user_id`),
  KEY `categories_parent_id` (`parent_id`),
  CONSTRAINT `categories_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categories_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wallet_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL,
  `label` varchar(128) NOT NULL,
  `icon` varchar(64) NOT NULL DEFAULT 'wallet',
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallet_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wallet_types` (`slug`, `label`, `icon`, `sort_order`, `is_system`) VALUES
('cash', 'Cash', 'banknote', 10, 1),
('bank', 'Bank', 'landmark', 20, 1),
('ewallet', 'E-wallet', 'smartphone', 30, 1),
('credit_card', 'Credit card', 'credit-card', 40, 1),
('other', 'Other', 'wallet', 90, 1);

CREATE TABLE `wallets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `wallet_type_id` int unsigned NOT NULL,
  `currency_id` int unsigned NOT NULL,
  `opening_balance` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `min_balance_threshold` decimal(18,4) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(512) DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `wallets_user_id` (`user_id`),
  KEY `wallets_wallet_type_id` (`wallet_type_id`),
  CONSTRAINT `wallets_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallets_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  CONSTRAINT `wallets_wallet_type_fk` FOREIGN KEY (`wallet_type_id`) REFERENCES `wallet_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recurring_schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `wallet_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(18,4) NOT NULL,
  `currency_id` int unsigned NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
  `interval_value` smallint unsigned NOT NULL DEFAULT 1,
  `by_weekday` tinyint DEFAULT NULL,
  `by_monthday` tinyint unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_occurrence` date NOT NULL,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `skip_next` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text,
  `last_generated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recurring_user_next` (`user_id`,`next_occurrence`),
  CONSTRAINT `recurring_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_wallet_fk` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `recurring_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `wallet_id` int unsigned DEFAULT NULL,
  `from_wallet_id` int unsigned DEFAULT NULL,
  `to_wallet_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `parent_transaction_id` bigint unsigned DEFAULT NULL,
  `type` enum('income','expense','transfer') NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(18,4) NOT NULL,
  `amount_base` decimal(18,4) NOT NULL,
  `currency_id` int unsigned NOT NULL,
  `exchange_rate_to_base` decimal(24,12) NOT NULL DEFAULT 1.000000000000,
  `notes` text,
  `transaction_date` date NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `recurring_schedule_id` int unsigned DEFAULT NULL,
  `is_consolidated_parent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tx_user_date` (`user_id`,`transaction_date`,`deleted_at`),
  KEY `tx_wallet` (`wallet_id`,`transaction_date`),
  KEY `tx_from_wallet` (`from_wallet_id`),
  KEY `tx_to_wallet` (`to_wallet_id`),
  KEY `tx_category` (`category_id`),
  KEY `tx_parent` (`parent_transaction_id`),
  KEY `tx_recurring` (`recurring_schedule_id`),
  CONSTRAINT `tx_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tx_wallet_fk` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_from_wallet_fk` FOREIGN KEY (`from_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_to_wallet_fk` FOREIGN KEY (`to_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tx_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `tx_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  CONSTRAINT `tx_parent_fk` FOREIGN KEY (`parent_transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tx_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tx_recurring_fk` FOREIGN KEY (`recurring_schedule_id`) REFERENCES `recurring_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaction_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `tag` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_tags_tx` (`transaction_id`),
  CONSTRAINT `transaction_tags_tx_fk` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaction_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `size_bytes` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ta_transaction` (`transaction_id`),
  CONSTRAINT `ta_transaction_fk` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaction_edit_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `changes_json` json NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `th_tx` (`transaction_id`),
  CONSTRAINT `th_tx_fk` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `th_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `scope` enum('global','user') NOT NULL DEFAULT 'global',
  `user_id` int unsigned DEFAULT NULL,
  `key_name` varchar(128) NOT NULL,
  `value` mediumtext,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_unique` (`scope`,`user_id`,`key_name`),
  KEY `settings_user` (`user_id`),
  CONSTRAINT `settings_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` varchar(48) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `body` text,
  `data_json` json DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user` (`user_id`,`read_at`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `entity_type` varchar(64) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_created` (`created_at`),
  KEY `audit_user` (`user_id`),
  CONSTRAINT `audit_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `size_bytes` bigint unsigned NOT NULL DEFAULT 0,
  `created_by` int unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `backups_created` (`created_at`),
  CONSTRAINT `backups_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
