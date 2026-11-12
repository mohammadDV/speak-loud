<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts_of_speech', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('display_name', 100);
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('difficulty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lexemes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('language_id');
            $table->string('lemma');
            $table->string('normalized_lemma');
            $table->string('slug')->nullable();
            $table->string('pronunciation')->nullable();
            $table->string('disambiguation')->nullable();
            $table->string('status', 32)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->index(['language_id', 'normalized_lemma']);
            $table->index(['language_id', 'status']);
            $table->unique(['language_id', 'slug']);
        });

        Schema::create('lexeme_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lexeme_id')->constrained('lexemes')->cascadeOnDelete();
            $table->string('form');
            $table->string('normalized_form');
            $table->string('form_type', 32)->default('inflected');
            $table->json('grammatical_features')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['lexeme_id', 'normalized_form']);
            $table->index('normalized_form');
        });

        Schema::create('senses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lexeme_id')->constrained('lexemes')->cascadeOnDelete();
            $table->foreignId('part_of_speech_id')->nullable()->constrained('parts_of_speech')->nullOnDelete();
            $table->text('definition')->nullable();
            $table->unsignedSmallInteger('definition_language_id')->nullable();
            $table->string('usage_label')->nullable();
            $table->string('domain')->nullable();
            $table->unsignedInteger('frequency_rank')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('definition_language_id')->references('id')->on('languages')->nullOnDelete();
            $table->index(['lexeme_id', 'sort_order']);
            $table->index('status');
        });

        Schema::create('sense_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sense_id')->constrained('senses')->cascadeOnDelete();
            $table->unsignedSmallInteger('target_language_id');
            $table->string('translation_text');
            $table->string('translation_type', 32)->default('equivalent');
            $table->text('notes')->nullable();
            $table->string('register')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->foreign('target_language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->index(['sense_id', 'target_language_id']);
        });

        Schema::create('sense_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sense_id')->constrained('senses')->cascadeOnDelete();
            $table->foreignId('difficulty_level_id')->constrained('difficulty_levels')->restrictOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->index(['sense_id', 'difficulty_level_id']);
        });

        Schema::create('german_noun_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sense_id')->unique()->constrained('senses')->cascadeOnDelete();
            $table->string('grammatical_gender', 16)->nullable();
            $table->string('article', 8)->nullable();
            $table->string('plural_form')->nullable();
            $table->text('grammar_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sense_id')->constrained('senses')->cascadeOnDelete();
            $table->unsignedSmallInteger('language_id');
            $table->text('sentence');
            $table->text('usage_note')->nullable();
            $table->foreignId('difficulty_level_id')->nullable()->constrained('difficulty_levels')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->index(['sense_id', 'language_id']);
        });

        Schema::create('example_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('example_id')->constrained('examples')->cascadeOnDelete();
            $table->unsignedSmallInteger('language_id');
            $table->text('translation');
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->index(['example_id', 'language_id']);
        });

        Schema::create('collocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sense_id')->constrained('senses')->cascadeOnDelete();
            $table->string('phrase');
            $table->string('pattern_type', 64)->nullable();
            $table->string('phrase_type', 32)->default('collocation');
            $table->text('explanation')->nullable();
            $table->string('frequency_label', 32)->nullable();
            $table->foreignId('difficulty_level_id')->nullable()->constrained('difficulty_levels')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sense_id', 'phrase_type']);
        });

        Schema::create('collocation_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collocation_id')->constrained('collocations')->cascadeOnDelete();
            $table->unsignedSmallInteger('language_id');
            $table->string('translation');
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->restrictOnDelete();
            $table->index(['collocation_id', 'language_id']);
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('version')->default(1);
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 32);
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reviews');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('collocation_translations');
        Schema::dropIfExists('collocations');
        Schema::dropIfExists('example_translations');
        Schema::dropIfExists('examples');
        Schema::dropIfExists('german_noun_details');
        Schema::dropIfExists('sense_levels');
        Schema::dropIfExists('sense_translations');
        Schema::dropIfExists('senses');
        Schema::dropIfExists('lexeme_forms');
        Schema::dropIfExists('lexemes');
        Schema::dropIfExists('difficulty_levels');
        Schema::dropIfExists('parts_of_speech');
    }
};
