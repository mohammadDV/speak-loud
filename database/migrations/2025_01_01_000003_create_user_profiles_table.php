<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('username', 50)->unique();
            $table->string('display_name', 100);
            $table->text('bio')->nullable();
            $table->enum('gender', ['male', 'female', 'non_binary', 'prefer_not_to_say'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('profile_image_path', 500)->nullable();
            $table->string('background_image_path', 500)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('nationality', 'idx_nationality');
            $table->index('country_code', 'idx_country');
        });

        DB::statement('ALTER TABLE user_profiles ADD FULLTEXT INDEX ft_bio (bio, display_name)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
