<?php

use RocketSourcer\Database\Migration;

class CreateUsersTable extends Migration
{
    protected string $table = 'users';

    /**
     * 마이그레이션 실행
     *
     * @return void
     */
    public function up(): void
    {
        $sql = "CREATE TABLE `{$this->table}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `name` varchar(100) NOT NULL,
            `roles` json DEFAULT NULL,
            `permissions` json DEFAULT NULL,
            `remember_token` varchar(100) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_email_unique` (`email`)
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