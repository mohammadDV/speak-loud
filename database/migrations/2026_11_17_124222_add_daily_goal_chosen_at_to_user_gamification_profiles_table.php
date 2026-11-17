<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_gamification_profiles', function (Blueprint $table) {
            $table->timestamp('daily_goal_chosen_at')->nullable()->after('daily_goal');
        });

        // Existing learners already have a goal in practice — don't force onboarding.
        DB::table('user_gamification_profiles')
            ->whereNull('daily_goal_chosen_at')
            ->update(['daily_goal_chosen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('user_gamification_profiles', function (Blueprint $table) {
            $table->dropColumn('daily_goal_chosen_at');
        });
    }
};
