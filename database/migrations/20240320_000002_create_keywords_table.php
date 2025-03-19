<?php

use RocketSourcer\Database\Migration;

class CreateKeywordsTable extends Migration
{
    protected string $table = 'keywords';

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
            `keyword` varchar(255) NOT NULL,
            `search_volume` int unsigned DEFAULT NULL,
            `competition` decimal(5,2) DEFAULT NULL,
            `cpc` decimal(10,2) DEFAULT NULL,
            `category` varchar(100) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `last_updated` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `keywords_user_id_index` (`user_id`),
            KEY `keywords_keyword_index` (`keyword`),
            CONSTRAINT `keywords_user_id_foreign` FOREIGN KEY (`user_id`) 
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