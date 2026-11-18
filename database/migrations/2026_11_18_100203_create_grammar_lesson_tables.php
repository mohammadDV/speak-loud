<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('cefr_level', 8)->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['status', 'position']);
        });

        Schema::create('course_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('language_code', 16);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'language_code']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('code');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('status', 32)->default('draft');
            $table->string('cefr_level', 8)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'code']);
            $table->index(['course_id', 'status', 'position']);
        });

        Schema::create('lesson_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('language_code', 16);
            $table->string('title');
            $table->longText('body_markdown');
            $table->timestamps();

            $table->unique(['lesson_id', 'language_code']);
        });

        Schema::create('lesson_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('import_key')->unique();
            $table->string('topic', 64);
            $table->string('answer');
            $table->json('distractors');
            $table->string('blank_token')->nullable();
            $table->string('source_lemma')->nullable();
            $table->string('sense_import_key')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 32)->default('published');
            $table->timestamps();

            $table->index(['lesson_id', 'status']);
            $table->index(['lesson_id', 'topic']);
        });

        Schema::create('lesson_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_question_id')->constrained('lesson_questions')->cascadeOnDelete();
            $table->string('language_code', 16);
            $table->text('prompt');
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->unique(['lesson_question_id', 'language_code'], 'lesson_question_translations_unique');
        });

        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->timestamp('last_completed_at')->nullable();
            $table->unsignedTinyInteger('best_percentage')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('grammar_quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('lesson_ids');
            $table->unsignedTinyInteger('word_count');
            $table->string('quiz_type', 32);
            $table->string('translation_language_code', 16);
            $table->unsignedTinyInteger('correct_count');
            $table->unsignedTinyInteger('incorrect_count');
            $table->unsignedTinyInteger('percentage');
            $table->json('question_ids');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('grammar_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grammar_quiz_session_id')
                ->constrained('grammar_quiz_sessions')
                ->cascadeOnDelete();
            $table->foreignId('lesson_question_id')
                ->constrained('lesson_questions')
                ->cascadeOnDelete();
            $table->text('prompt');
            $table->string('correct_answer');
            $table->text('selected_answer');
            $table->boolean('is_correct');
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['grammar_quiz_session_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_quiz_answers');
        Schema::dropIfExists('grammar_quiz_sessions');
        Schema::dropIfExists('user_lesson_progress');
        Schema::dropIfExists('lesson_question_translations');
        Schema::dropIfExists('lesson_questions');
        Schema::dropIfExists('lesson_translations');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_translations');
        Schema::dropIfExists('courses');
    }
};
