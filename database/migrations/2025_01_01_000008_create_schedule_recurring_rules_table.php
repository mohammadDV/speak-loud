<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_recurring_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id')->unique();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE schedule_recurring_rules MODIFY day_of_week SET('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_recurring_rules');
    }
};
