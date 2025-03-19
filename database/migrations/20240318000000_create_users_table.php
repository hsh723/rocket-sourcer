<?php

namespace RocketSourcer\Database\Migrations;

use RocketSourcer\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->createTable('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $this->dropTable('users');
    }
} 