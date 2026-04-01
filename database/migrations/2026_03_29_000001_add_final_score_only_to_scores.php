<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            // Make criteria_id nullable to support final score only mode
            $table->unsignedBigInteger('criteria_id')->nullable()->change();
            
            // Add flag to indicate if this is a final score only entry
            $table->boolean('is_final_score_only')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropColumn('is_final_score_only');
            // Revert criteria_id to not nullable
            $table->unsignedBigInteger('criteria_id')->nullable(false)->change();
        });
    }
};
