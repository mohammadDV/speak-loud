<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lexemes', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('disambiguation');
            $table->unique(['language_id', 'import_key']);
        });

        Schema::table('senses', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('lexeme_id');
            $table->unique(['lexeme_id', 'import_key']);
        });

        Schema::table('examples', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('sense_id');
            $table->unique(['sense_id', 'import_key']);
        });

        Schema::table('collocations', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('sense_id');
            $table->unique(['sense_id', 'import_key']);
        });
    }

    public function down(): void
    {
        Schema::table('collocations', function (Blueprint $table) {
            $table->dropUnique(['sense_id', 'import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('examples', function (Blueprint $table) {
            $table->dropUnique(['sense_id', 'import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('senses', function (Blueprint $table) {
            $table->dropUnique(['lexeme_id', 'import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('lexemes', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'import_key']);
            $table->dropColumn('import_key');
        });
    }
};
