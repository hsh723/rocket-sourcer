<?php

use RocketSourcer\Database\Migration;

class CreateProductsTable extends Migration
{
    protected string $table = 'products';

    /**
     * 마이그레이션 실행
     *
     * @return void
     */
    public function up(): void
    {
        $sql = "CREATE TABLE `{$this->table}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int unsigned NOT NULL,
            `platform_id` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` text,
            `price` decimal(10,2) NOT NULL,
            `shipping_fee` decimal(10,2) DEFAULT '0.00',
            `category` varchar(100) DEFAULT NULL,
            `url` varchar(2048) NOT NULL,
            `image_url` varchar(2048) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `last_checked` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `products_user_id_index` (`user_id`),
            KEY `products_platform_id_index` (`platform_id`),
            CONSTRAINT `products_user_id_foreign` FOREIGN KEY (`user_id`) 
            REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->createTable($sql);
    }

    /**
     * 마이그레이션 롤백
     *
     * @return void
     */
    public function down(): void
    {
        $this->dropTable($this->table);
    }
} 