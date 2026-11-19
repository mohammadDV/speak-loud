<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plural_quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('level_codes')->nullable();
            $table->json('category_codes')->nullable();
            $table->unsignedTinyInteger('word_count');
            $table->string('translation_language_code', 16);
            $table->unsignedTinyInteger('correct_count');
            $table->unsignedTinyInteger('incorrect_count');
            $table->unsignedTinyInteger('percentage');
            $table->json('sense_ids');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('plural_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plural_quiz_session_id')
                ->constrained('plural_quiz_sessions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('sense_id')->index();
            $table->string('lemma');
            $table->string('correct_plural');
            $table->string('selected_plural');
            $table->boolean('is_correct');
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['plural_quiz_session_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plural_quiz_answers');
        Schema::dropIfExists('plural_quiz_sessions');
    }
};
