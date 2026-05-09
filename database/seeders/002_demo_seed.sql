SET NAMES utf8mb4;

INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `decimal_places`, `is_base`, `is_active`, `sort_order`) VALUES
(1, 'MYR', 'Malaysian Ringgit', 'RM', 2, 1, 1, 0),
(2, 'USD', 'US Dollar', '$', 2, 0, 1, 1),
(3, 'SGD', 'Singapore Dollar', 'S$', 2, 0, 1, 2);

INSERT INTO `exchange_rates` (`from_currency_id`, `to_currency_id`, `rate`, `effective_date`) VALUES
(1, 1, 1.000000000000, CURDATE()),
(1, 2, 0.210000000000, CURDATE()),
(1, 3, 0.290000000000, CURDATE()),
(2, 1, 4.760000000000, CURDATE()),
(3, 1, 3.450000000000, CURDATE());

INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('superadmin', 'admin@khfinam.local', '$2y$10$LcCveUwSyNzOAPTbVUo0SOJPuFEI6TtQjw03KIEci0eAZaRJjW2t6', 'Super Admin', 'super_admin', 1),
('demo', 'demo@khfinam.local', '$2y$10$HK22QpA9VTkkqtSRefdv3eWyHgrATs72sId4vTV0dCQWPtUh0YaSO', 'Demo User', 'user', 1);

INSERT INTO `categories` (`id`, `user_id`, `parent_id`, `name`, `slug`, `type`, `color`, `icon`, `is_system`, `sort_order`) VALUES
(1, NULL, NULL, 'Salary', 'salary', 'income', '#059669', 'payments', 1, 1),
(2, NULL, NULL, 'Freelance', 'freelance', 'income', '#0d9488', 'work', 1, 2),
(3, NULL, NULL, 'Food & Dining', 'food', 'expense', '#dc2626', 'restaurant', 1, 3),
(4, NULL, NULL, 'Transport', 'transport', 'expense', '#ea580c', 'directions_car', 1, 4),
(5, NULL, NULL, 'Utilities', 'utilities', 'expense', '#7c3aed', 'bolt', 1, 5),
(6, NULL, NULL, 'Shopping', 'shopping', 'expense', '#db2777', 'shopping_bag', 1, 6),
(7, NULL, NULL, 'Side income', 'side-income', 'income', '#0891b2', 'savings', 1, 10);

INSERT INTO `wallets` (`user_id`, `name`, `wallet_type`, `currency_id`, `opening_balance`, `min_balance_threshold`, `is_default`, `is_active`, `notes`, `sort_order`)
SELECT u.id, 'Cash', 'cash', 1, 200.0000, 50.0000, 1, 1, 'Petty cash', 0 FROM users u WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `wallets` (`user_id`, `name`, `wallet_type`, `currency_id`, `opening_balance`, `min_balance_threshold`, `is_default`, `is_active`, `notes`, `sort_order`)
SELECT u.id, 'Maybank', 'bank', 1, 5000.0000, 500.0000, 0, 1, 'Main account', 1 FROM users u WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `wallets` (`user_id`, `name`, `wallet_type`, `currency_id`, `opening_balance`, `min_balance_threshold`, `is_default`, `is_active`, `notes`, `sort_order`)
SELECT u.id, 'Admin Wallet', 'cash', 1, 0.0000, NULL, 1, 1, NULL, 0 FROM users u WHERE u.username = 'superadmin' LIMIT 1;

INSERT INTO `transactions` (`user_id`, `wallet_id`, `category_id`, `parent_transaction_id`, `type`, `title`, `amount`, `amount_base`, `currency_id`, `exchange_rate_to_base`, `notes`, `transaction_date`, `deleted_at`, `created_by`, `recurring_schedule_id`, `is_consolidated_parent`)
SELECT u.id, w.id, 3, NULL, 'expense', 'Groceries — weekly', 120.50, 120.5000, 1, 1.000000000000, 'Supermarket', DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, u.id, NULL, 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Maybank' WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `transactions` (`user_id`, `wallet_id`, `category_id`, `parent_transaction_id`, `type`, `title`, `amount`, `amount_base`, `currency_id`, `exchange_rate_to_base`, `notes`, `transaction_date`, `deleted_at`, `created_by`, `recurring_schedule_id`, `is_consolidated_parent`)
SELECT u.id, w.id, 4, NULL, 'expense', 'Grab rides', 35.00, 35.0000, 1, 1.000000000000, NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, u.id, NULL, 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Cash' WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `transactions` (`user_id`, `wallet_id`, `category_id`, `parent_transaction_id`, `type`, `title`, `amount`, `amount_base`, `currency_id`, `exchange_rate_to_base`, `notes`, `transaction_date`, `deleted_at`, `created_by`, `recurring_schedule_id`, `is_consolidated_parent`)
SELECT u.id, w.id, 1, NULL, 'income', 'Monthly salary', 6500.00, 6500.0000, 1, 1.000000000000, NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL, u.id, NULL, 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Maybank' WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `transactions` (`user_id`, `wallet_id`, `category_id`, `parent_transaction_id`, `type`, `title`, `amount`, `amount_base`, `currency_id`, `exchange_rate_to_base`, `notes`, `transaction_date`, `deleted_at`, `created_by`, `recurring_schedule_id`, `is_consolidated_parent`)
SELECT u.id, w.id, 5, NULL, 'expense', 'Electric bill', 180.00, 180.0000, 1, 1.000000000000, NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL, u.id, NULL, 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Maybank' WHERE u.username = 'demo' LIMIT 1;

INSERT INTO `recurring_schedules` (`user_id`, `wallet_id`, `category_id`, `type`, `title`, `amount`, `currency_id`, `frequency`, `interval_value`, `start_date`, `end_date`, `next_occurrence`, `is_paused`)
SELECT u.id, w.id, 1, 'income', 'Salary deposit', 6500.00, 1, 'monthly', 1, DATE_SUB(CURDATE(), INTERVAL 60 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Maybank' WHERE u.username = 'demo' LIMIT 1;
INSERT INTO `recurring_schedules` (`user_id`, `wallet_id`, `category_id`, `type`, `title`, `amount`, `currency_id`, `frequency`, `interval_value`, `start_date`, `end_date`, `next_occurrence`, `is_paused`)
SELECT u.id, w.id, 5, 'expense', 'Internet', 99.00, 1, 'monthly', 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 0
FROM users u JOIN wallets w ON w.user_id = u.id AND w.name = 'Maybank' WHERE u.username = 'demo' LIMIT 1;

INSERT INTO `settings` (`scope`, `user_id`, `key_name`, `value`) VALUES
('global', NULL, 'app_installed', '1'),
('global', NULL, 'smtp_host', ''),
('global', NULL, 'smtp_port', '587'),
('global', NULL, 'smtp_user', ''),
('global', NULL, 'smtp_pass', ''),
('global', NULL, 'smtp_encryption', 'tls');

INSERT INTO `notifications` (`user_id`, `type`, `title`, `body`, `read_at`)
SELECT id, 'info', 'Welcome to KHFinaM', 'Your demo account is ready. Explore wallets and reports.', NULL FROM users WHERE username = 'demo' LIMIT 1;
INSERT INTO `notifications` (`user_id`, `type`, `title`, `body`, `read_at`)
SELECT id, 'warning', 'Low balance reminder', 'Review wallet thresholds in settings.', NULL FROM users WHERE username = 'demo' LIMIT 1;

INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `metadata`)
SELECT id, 'seed', 'system', '1', '127.0.0.1', JSON_OBJECT('note', 'demo seed') FROM users WHERE username = 'superadmin' LIMIT 1;
