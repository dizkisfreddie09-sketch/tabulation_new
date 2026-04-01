<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->integer('male_count')->default(0)->after('contestant_count');
            $table->integer('female_count')->default(0)->after('male_count');
        });
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn(['male_count', 'female_count']);
        });
    }
};
