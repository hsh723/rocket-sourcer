<?php

use RocketSourcer\Database\Migration;

class CreateAnalysesTable extends Migration
{
    protected string $table = 'analyses';

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
            `product_id` int unsigned NOT NULL,
            `keyword_id` int unsigned NOT NULL,
            `relevance_score` decimal(5,2) DEFAULT NULL,
            `competition_score` decimal(5,2) DEFAULT NULL,
            `profit_potential` decimal(10,2) DEFAULT NULL,
            `estimated_sales` int unsigned DEFAULT NULL,
            `recommendation` text,
            `analysis_data` json DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `analyses_user_id_index` (`user_id`),
            KEY `analyses_product_id_index` (`product_id`),
            KEY `analyses_keyword_id_index` (`keyword_id`),
            CONSTRAINT `analyses_user_id_foreign` FOREIGN KEY (`user_id`) 
            REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `analyses_product_id_foreign` FOREIGN KEY (`product_id`) 
            REFERENCES `products` (`id`) ON DELETE CASCADE,
            CONSTRAINT `analyses_keyword_id_foreign` FOREIGN KEY (`keyword_id`) 
            REFERENCES `keywords` (`id`) ON DELETE CASCADE
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