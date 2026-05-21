<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('profile_slug', 50)->nullable()->unique()->after('username');
            $table->boolean('is_private')->default(false)->after('is_available');
        });

        DB::table('user_profiles')
            ->whereNull('profile_slug')
            ->update(['profile_slug' => DB::raw('username')]);
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['profile_slug', 'is_private']);
        });
    }
};
