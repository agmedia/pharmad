-- Apply ONCE, instead of migration 2026_09_29_080000_extend_contract_withdrawals_table.
-- Requires existing contract_withdrawals table (024). Back up the database first.
ALTER TABLE `contract_withdrawals`
  ADD `withdrawal_scope` VARCHAR(16) NULL,
  ADD `consumer_notification_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD `admin_notification_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD `consumer_last_attempt_at` TIMESTAMP NULL,
  ADD `admin_last_attempt_at` TIMESTAMP NULL,
  ADD `consumer_notification_error` TEXT NULL,
  ADD `admin_notification_error` TEXT NULL,
  MODIFY `address_line` VARCHAR(255) NULL,
  MODIFY `postal_code` VARCHAR(32) NULL,
  MODIFY `city` VARCHAR(120) NULL,
  MODIFY `country_code` VARCHAR(2) NULL DEFAULT NULL;
