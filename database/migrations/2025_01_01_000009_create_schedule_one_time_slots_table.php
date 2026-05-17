<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_one_time_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id')->unique();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->index('start_datetime', 'idx_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_one_time_slots');
    }
};
