-- Add native transfer rows (money between wallets) without counting as income/expense.
-- Run after 003 (wallet types) where applicable.

SET NAMES utf8mb4;

ALTER TABLE `transactions`
  MODIFY COLUMN `type` ENUM('income','expense','transfer') NOT NULL;

ALTER TABLE `transactions`
  MODIFY COLUMN `wallet_id` INT UNSIGNED NULL,
  MODIFY COLUMN `category_id` INT UNSIGNED NULL;

ALTER TABLE `transactions`
  ADD COLUMN `from_wallet_id` INT UNSIGNED NULL AFTER `wallet_id`,
  ADD COLUMN `to_wallet_id` INT UNSIGNED NULL AFTER `from_wallet_id`,
  ADD KEY `tx_from_wallet` (`from_wallet_id`),
  ADD KEY `tx_to_wallet` (`to_wallet_id`);

ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_from_wallet_fk` FOREIGN KEY (`from_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_to_wallet_fk` FOREIGN KEY (`to_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;
