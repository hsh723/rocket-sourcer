<?php

namespace RocketSourcer\Database\Migrations;

use RocketSourcer\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('url')->unique();
            $table->string('status')->default('pending');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('review_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->json('categories')->nullable();
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->json('metadata')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignKey('user_id', 'users');
            $table->index('status');
            $table->index('price');
            $table->index('review_count');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        $this->dropTable('products');
    }
} 