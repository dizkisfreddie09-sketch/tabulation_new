<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contestant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->timestamps();

            $table->unique(['contestant_id', 'judge_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
