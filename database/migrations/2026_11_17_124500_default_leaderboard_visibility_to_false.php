<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_gamification_profiles', function (Blueprint $table) {
            $table->boolean('show_on_leaderboard')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_gamification_profiles', function (Blueprint $table) {
            $table->boolean('show_on_leaderboard')->default(true)->change();
        });
    }
};
