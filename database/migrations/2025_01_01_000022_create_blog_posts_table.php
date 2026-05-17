<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('blog_categories')->nullOnDelete();
            $table->string('title', 300);
            $table->string('slug', 320)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('cover_image_path', 500)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        DB::statement('ALTER TABLE blog_posts ADD FULLTEXT ft_blog (title, excerpt, body)');
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
