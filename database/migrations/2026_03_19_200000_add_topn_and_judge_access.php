<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exposures', function (Blueprint $table) {
            $table->integer('top_n')->nullable()->after('carry_over_percentage');
        });

        Schema::table('judges', function (Blueprint $table) {
            $table->string('access_code', 32)->unique()->nullable()->after('number');
        });
    }

    public function down(): void
    {
        Schema::table('exposures', function (Blueprint $table) {
            $table->dropColumn('top_n');
        });

        Schema::table('judges', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};
