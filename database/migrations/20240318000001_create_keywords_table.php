<?php

namespace RocketSourcer\Database\Migrations;

use RocketSourcer\Database\Migration;

class CreateKeywordsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('keywords', function ($table) {
            $table->id();
            $table->string('keyword')->unique();
            $table->string('status')->default('pending');
            $table->integer('search_volume')->nullable();
            $table->decimal('competition', 5, 4)->nullable();
            $table->decimal('cpc', 10, 2)->nullable();
            $table->json('categories')->nullable();
            $table->json('trends')->nullable();
            $table->json('related_keywords')->nullable();
            $table->json('metadata')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignKey('user_id', 'users');
            $table->index('status');
            $table->index('search_volume');
        });
    }

    public function down(): void
    {
        $this->dropTable('keywords');
    }
} 