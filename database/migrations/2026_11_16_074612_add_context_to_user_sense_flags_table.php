<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_sense_flags', function (Blueprint $table) {
            $table->string('context', 32)->default('article')->after('sense_id');
        });

        Schema::table('user_sense_flags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'sense_id']);
            $table->unique(['user_id', 'sense_id', 'context']);
            $table->index(['user_id', 'context', 'is_important']);
            $table->index(['user_id', 'context', 'is_learned']);
        });
    }

    public function down(): void
    {
        Schema::table('user_sense_flags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'sense_id', 'context']);
            $table->dropIndex(['user_id', 'context', 'is_important']);
            $table->dropIndex(['user_id', 'context', 'is_learned']);
            $table->dropColumn('context');
            $table->unique(['user_id', 'sense_id']);
        });
    }
};
