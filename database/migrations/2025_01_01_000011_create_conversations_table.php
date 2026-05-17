<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->unique()->constrained('claims')->cascadeOnDelete();
            $table->foreignId('user_a_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_b_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index('user_a_id');
            $table->index('user_b_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
