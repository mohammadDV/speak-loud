<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lexemes', function (Blueprint $table) {
            $table->index(['language_id', 'status', 'normalized_lemma'], 'lexemes_lang_status_lemma_idx');
        });

        Schema::table('sense_translations', function (Blueprint $table) {
            $table->index(['target_language_id', 'status'], 'sense_translations_lang_status_idx');
        });

        Schema::table('examples', function (Blueprint $table) {
            $table->index('status', 'examples_status_idx');
        });

        Schema::table('collocations', function (Blueprint $table) {
            $table->index('status', 'collocations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lexemes', function (Blueprint $table) {
            $table->dropIndex('lexemes_lang_status_lemma_idx');
        });

        Schema::table('sense_translations', function (Blueprint $table) {
            $table->dropIndex('sense_translations_lang_status_idx');
        });

        Schema::table('examples', function (Blueprint $table) {
            $table->dropIndex('examples_status_idx');
        });

        Schema::table('collocations', function (Blueprint $table) {
            $table->dropIndex('collocations_status_idx');
        });
    }
};
