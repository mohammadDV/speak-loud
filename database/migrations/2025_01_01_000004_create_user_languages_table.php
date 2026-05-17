<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('language_id');
            $table->enum('type', ['native', 'learning']);
            $table->enum('level', ['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced', 'fluent'])->nullable();

            $table->unique(['user_id', 'language_id', 'type'], 'uq_user_lang_type');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('language_id')->references('id')->on('languages');
            $table->index(['language_id', 'type'], 'idx_language_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_languages');
    }
};
