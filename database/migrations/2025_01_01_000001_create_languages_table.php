<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->autoIncrement()->primary();
            $table->char('code', 5)->unique();
            $table->string('name_en', 100);
            $table->string('name_native', 100);
            $table->boolean('is_active')->default(true);

            $table->index('code', 'idx_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
