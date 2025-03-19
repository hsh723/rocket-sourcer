<?php

use RocketSourcer\Database\Migration;

class CreateSettingsTable extends Migration
{
    protected string $table = 'settings';

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
            `key` varchar(100) NOT NULL,
            `value` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `settings_user_id_key_unique` (`user_id`, `key`),
            CONSTRAINT `settings_user_id_foreign` FOREIGN KEY (`user_id`) 
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