<?php

namespace RocketSourcer\Database\Migrations;

use RocketSourcer\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->json('metadata')->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignKey('user_id', 'users');
            $table->index('key');
            $table->index('type');
            $table->index('is_public');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        $this->dropTable('settings');
    }
} 