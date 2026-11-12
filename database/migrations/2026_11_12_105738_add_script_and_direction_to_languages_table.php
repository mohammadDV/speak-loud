<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->string('script', 32)->nullable()->after('name_native');
            $table->string('direction', 3)->default('ltr')->after('script');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('languages', function (Blueprint $table) {
                $table->string('code', 16)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['script', 'direction']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('languages', function (Blueprint $table) {
                $table->char('code', 5)->change();
            });
        }
    }
};
