-- Add internal transfer + performance indexes (run after 001)

SET NAMES utf8mb4;

ALTER TABLE `transactions`
  ADD COLUMN `is_internal_transfer` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_consolidated_parent`,
  ADD COLUMN `transfer_group` CHAR(36) DEFAULT NULL AFTER `is_internal_transfer`,
  ADD KEY `tx_internal` (`user_id`, `is_internal_transfer`),
  ADD KEY `tx_transfer_group` (`transfer_group`);

INSERT INTO `categories` (`user_id`, `parent_id`, `name`, `slug`, `type`, `color`, `icon`, `is_system`, `sort_order`) VALUES
(NULL, NULL, 'Transfer (out)', 'transfer-out', 'expense', '#64748b', 'swap_horiz', 1, 90),
(NULL, NULL, 'Transfer (in)', 'transfer-in', 'income', '#64748b', 'swap_horiz', 1, 91);
