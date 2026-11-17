<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_gamification_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->bigInteger('total_xp')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->unsignedInteger('daily_goal')->default(20);
            $table->string('timezone', 64)->default('Europe/Berlin');
            $table->boolean('show_on_leaderboard')->default(false);
            $table->string('leaderboard_display_name', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('reason', 40);
            $table->string('source_type', 40);
            $table->string('source_id', 64)->nullable();
            $table->string('idempotency_key', 160)->unique();
            $table->date('local_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['created_at', 'user_id']);
            $table->index(['user_id', 'local_date', 'reason']);
        });

        Schema::create('user_daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->integer('xp_earned')->default(0);
            $table->integer('bonus_xp_earned')->default(0);
            $table->unsignedInteger('quizzes_completed')->default(0);
            $table->unsignedInteger('questions_answered')->default(0);
            $table->unsignedInteger('qualifying_activity_count')->default(0);
            $table->unsignedInteger('daily_goal')->default(20);
            $table->timestamp('goal_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'activity_date']);
            $table->index(['activity_date', 'user_id']);
        });

        Schema::create('gamification_processed_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type', 40);
            $table->string('source_id', 64);
            $table->string('mode', 40)->nullable();
            $table->unsignedInteger('total_questions');
            $table->unsignedInteger('correct_answers');
            $table->boolean('is_perfect')->default(false);
            $table->integer('xp_total')->default(0);
            $table->date('local_date');
            $table->json('summary')->nullable();
            $table->timestamp('processed_at');

            $table->unique(['activity_type', 'source_id']);
            $table->index(['user_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_processed_events');
        Schema::dropIfExists('user_daily_activities');
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('user_gamification_profiles');
    }
};
