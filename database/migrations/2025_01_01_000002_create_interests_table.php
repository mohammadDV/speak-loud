<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->autoIncrement()->primary();
            $table->string('slug', 100)->unique();
            $table->string('name_en', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};
