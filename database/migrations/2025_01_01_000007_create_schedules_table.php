<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['recurring', 'one_time']);
            $table->unsignedSmallInteger('language_id');
            $table->unsignedTinyInteger('max_participants')->default(1);
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('language_id')->references('id')->on('languages');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index('language_id', 'idx_language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
