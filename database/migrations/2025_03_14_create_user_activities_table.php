<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 마이그레이션 실행
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type', 50);
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // 인덱스 추가
            $table->index('activity_type');
            $table->index('created_at');
            $table->index(['user_id', 'activity_type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * 마이그레이션 롤백
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_activities');
    }
}; 