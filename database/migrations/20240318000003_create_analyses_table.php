<?php

namespace RocketSourcer\Database\Migrations;

use RocketSourcer\Database\Migration;

class CreateAnalysesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('analyses', function ($table) {
            $table->id();
            $table->morphs('analyzable');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->json('result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignKey('user_id', 'users');
            $table->index(['analyzable_type', 'analyzable_id']);
            $table->index('type');
            $table->index('status');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        $this->dropTable('analyses');
    }
} 