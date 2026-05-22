<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['direct', 'schedule_group'])->default('direct')->after('id');
            $table->foreignId('schedule_id')->nullable()->after('type')->constrained('schedules')->cascadeOnDelete();
            $table->unique('schedule_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['user_a_id']);
            $table->dropForeign(['user_b_id']);
            $table->dropUnique(['user_a_id', 'user_b_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('user_a_id')->nullable()->change();
            $table->unsignedBigInteger('user_b_id')->nullable()->change();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('user_a_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_b_id')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::table('conversations')->update(['type' => 'direct']);

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropUnique(['schedule_id']);
            $table->dropColumn(['type', 'schedule_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['user_a_id']);
            $table->dropForeign(['user_b_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('user_a_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_b_id')->nullable(false)->change();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('user_a_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_b_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_a_id', 'user_b_id']);
        });
    }
};
