-- User-level exclusion from admin/global analytics & platform reports only.
SET NAMES utf8mb4;

ALTER TABLE `users`
  ADD COLUMN `include_in_analytics` tinyint(1) NOT NULL DEFAULT 1
  AFTER `preference_mute_low_balance`;

UPDATE `users` SET `include_in_analytics` = 0 WHERE `username` IN (
    'demo',
    'demo_office',
    'demo_freelance',
    'demo_student',
    'demo_family',
    'demo_sidebiz',
    'demo_struggle'
);
