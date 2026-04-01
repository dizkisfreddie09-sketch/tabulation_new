<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('status');
            $table->string('tabulator_name')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn(['logo', 'tabulator_name']);
        });
    }
};
