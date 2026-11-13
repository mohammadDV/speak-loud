<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('display_name', 100);
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('category_lexeme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('lexeme_id')->constrained('lexemes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'lexeme_id']);
            $table->index('lexeme_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_lexeme');
        Schema::dropIfExists('categories');
    }
};
