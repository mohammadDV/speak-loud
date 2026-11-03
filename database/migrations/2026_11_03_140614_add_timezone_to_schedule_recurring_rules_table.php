<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_recurring_rules', function (Blueprint $table) {
            $table->string('timezone', 64)->default('UTC')->after('end_time');
        });

        DB::table('schedule_recurring_rules')->whereNull('timezone')->orWhere('timezone', '')->update([
            'timezone' => 'UTC',
        ]);
    }

    public function down(): void
    {
        Schema::table('schedule_recurring_rules', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
