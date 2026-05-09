-- Upgrade existing databases:
-- - Creates `wallet_types` + seeds when missing (safe to re-run).
-- - Adds `wallets.wallet_type_id` when missing, migrates from legacy `wallet_type` if present,
--   then drops legacy column and adds FK.
-- Fresh installs from the current `001_initial_schema.sql` already include this; you can skip
-- this file in that case, or run it anyway (noop / idempotent-ish).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `wallet_types` (
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

INSERT IGNORE INTO `wallet_types` (`slug`, `label`, `icon`, `sort_order`, `is_system`) VALUES
('cash', 'Cash', 'banknote', 10, 1),
('bank', 'Bank', 'landmark', 20, 1),
('ewallet', 'E-wallet', 'smartphone', 30, 1),
('credit_card', 'Credit card', 'credit-card', 40, 1),
('other', 'Other', 'wallet', 90, 1);

SET @db := DATABASE();

-- Legacy column was enum or varchar storing slug-like values ('cash','bank',…)
SET @had_legacy_wallet_type := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wallets' AND COLUMN_NAME = 'wallet_type'
);

SET @has_wallet_type_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'wallets' AND COLUMN_NAME = 'wallet_type_id'
);

-- Add wallet_type_id whenever it is missing (do not require legacy column to exist)
SET @sql := IF(@has_wallet_type_id = 0,
  'ALTER TABLE `wallets` ADD COLUMN `wallet_type_id` int unsigned NULL AFTER `name`',
  'SELECT 1'
);
PREPARE _m1 FROM @sql;
EXECUTE _m1;
DEALLOCATE PREPARE _m1;

-- Map legacy values to rows (slug match; enum values matched initial app slugs)
UPDATE `wallets` w
INNER JOIN `wallet_types` wt ON wt.slug = CONVERT(w.`wallet_type` USING utf8mb4) COLLATE utf8mb4_unicode_ci
SET w.`wallet_type_id` = wt.`id`
WHERE @had_legacy_wallet_type > 0 AND w.`wallet_type_id` IS NULL;

-- Anything still null → "other"
UPDATE `wallets` w
INNER JOIN `wallet_types` wt ON wt.slug = 'other'
SET w.`wallet_type_id` = wt.`id`
WHERE w.`wallet_type_id` IS NULL;

SET @sql2 := IF(@had_legacy_wallet_type > 0,
  'ALTER TABLE `wallets` DROP COLUMN `wallet_type`',
  'SELECT 1'
);
PREPARE _m2 FROM @sql2;
EXECUTE _m2;
DEALLOCATE PREPARE _m2;

-- Not null once every row has a type
SET @sql3 := IF(@has_wallet_type_id = 0 OR @had_legacy_wallet_type > 0,
  'ALTER TABLE `wallets` MODIFY `wallet_type_id` int unsigned NOT NULL',
  'SELECT 1'
);
PREPARE _m3 FROM @sql3;
EXECUTE _m3;
DEALLOCATE PREPARE _m3;

SET @sql4 := IF(
  (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'wallets' AND CONSTRAINT_NAME = 'wallets_wallet_type_fk') = 0,
  'ALTER TABLE `wallets` ADD CONSTRAINT `wallets_wallet_type_fk` FOREIGN KEY (`wallet_type_id`) REFERENCES `wallet_types` (`id`)',
  'SELECT 1'
);
PREPARE _m4 FROM @sql4;
EXECUTE _m4;
DEALLOCATE PREPARE _m4;
SET FOREIGN_KEY_CHECKS = 1;
