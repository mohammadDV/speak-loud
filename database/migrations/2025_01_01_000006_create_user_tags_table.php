<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('tag', 50);

            $table->unique(['user_id', 'tag'], 'uq_user_tag');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('tag', 'idx_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tags');
    }
};
