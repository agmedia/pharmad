ALTER TABLE `products`
  ADD `anchor_price` DECIMAL(15,4) NULL AFTER `price`,
  ADD `anchor_date` DATE NULL AFTER `anchor_price`,
  ADD `unit_measure` VARCHAR(50) NULL AFTER `anchor_date`,
  ADD `unit_price` DECIMAL(15,4) NULL AFTER `unit_measure`;

UPDATE `products`
SET `anchor_price` = `price`, `anchor_date` = '2026-09-10';

CREATE TABLE `price_list_exports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sequence` BIGINT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `generated_at` TIMESTAMP NOT NULL,
  `product_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `checksum` CHAR(64) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_list_exports_sequence_unique` (`sequence`),
  UNIQUE KEY `price_list_exports_filename_unique` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
