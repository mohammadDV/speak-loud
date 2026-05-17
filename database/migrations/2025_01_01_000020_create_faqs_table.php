<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->autoIncrement();
            $table->unsignedSmallInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('faq_categories')->nullOnDelete();
            $table->string('question', 500);
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE faqs ADD FULLTEXT ft_faq (question, answer)');
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
