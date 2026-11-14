<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_quiz_sessions', function (Blueprint $table) {
            $table->json('level_codes')->nullable()->after('user_id');
            $table->json('category_codes')->nullable()->after('level_codes');
        });

        DB::table('article_quiz_sessions')->orderBy('id')->chunkById(100, function ($sessions): void {
            foreach ($sessions as $session) {
                $levelCodes = filled($session->level_code) ? [strtoupper((string) $session->level_code)] : null;
                $categoryCodes = filled($session->category_code) ? [strtolower((string) $session->category_code)] : null;

                DB::table('article_quiz_sessions')->where('id', $session->id)->update([
                    'level_codes' => $levelCodes !== null ? json_encode($levelCodes) : null,
                    'category_codes' => $categoryCodes !== null ? json_encode($categoryCodes) : null,
                ]);
            }
        });

        Schema::table('article_quiz_sessions', function (Blueprint $table) {
            $table->dropIndex(['level_code']);
            $table->dropIndex(['category_code']);
            $table->dropColumn(['level_code', 'category_code']);
        });
    }

    public function down(): void
    {
        Schema::table('article_quiz_sessions', function (Blueprint $table) {
            $table->string('level_code', 8)->nullable()->after('user_id');
            $table->string('category_code', 64)->nullable()->after('level_code');
        });

        DB::table('article_quiz_sessions')->orderBy('id')->chunkById(100, function ($sessions): void {
            foreach ($sessions as $session) {
                $levels = json_decode((string) $session->level_codes, true);
                $categories = json_decode((string) $session->category_codes, true);

                DB::table('article_quiz_sessions')->where('id', $session->id)->update([
                    'level_code' => is_array($levels) && $levels !== [] ? (string) $levels[0] : null,
                    'category_code' => is_array($categories) && $categories !== [] ? (string) $categories[0] : null,
                ]);
            }
        });

        Schema::table('article_quiz_sessions', function (Blueprint $table) {
            $table->index('level_code');
            $table->index('category_code');
            $table->dropColumn(['level_codes', 'category_codes']);
        });
    }
};
