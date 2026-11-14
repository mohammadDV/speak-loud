<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level_code', 8)->nullable()->index();
            $table->string('category_code', 64)->nullable()->index();
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

        Schema::create('article_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_quiz_session_id')
                ->constrained('article_quiz_sessions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('sense_id')->index();
            $table->string('lemma');
            $table->string('correct_article', 8);
            $table->string('selected_article', 8);
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['article_quiz_session_id', 'position']);
        });

        Schema::create('user_sense_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sense_id')->index();
            $table->boolean('is_important')->default(false);
            $table->boolean('is_learned')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'sense_id']);
            $table->index(['user_id', 'is_important']);
            $table->index(['user_id', 'is_learned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sense_flags');
        Schema::dropIfExists('article_quiz_answers');
        Schema::dropIfExists('article_quiz_sessions');
    }
};
