CREATE TABLE `loyalty` (
                           `id` bigint UNSIGNED NOT NULL,
                           `user_id` bigint NOT NULL,
                           `reference_id` bigint NOT NULL,
                           `target` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                           `earned` bigint NOT NULL,
                           `spend` bigint NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                                                                                                                 (4, 2, 18737, 'order', 15, 100, '2025-07-17 06:57:49', '2025-07-17 06:57:49'),
                                                                                                                     (5, 2, 18738, 'order', 41, 100, '2025-07-17 12:41:49', '2025-07-17 12:41:49');


ALTER TABLE `loyalty`
    ADD PRIMARY KEY (`id`);


ALTER TABLE `loyalty`
    MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;
