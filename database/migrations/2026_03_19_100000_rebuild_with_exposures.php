<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables in reverse dependency order
        Schema::dropIfExists('scores');
        Schema::dropIfExists('judges');
        Schema::dropIfExists('contestants');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('contests');

        // Contests
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['single', 'double', 'group'])->default('single');
            $table->integer('judge_count')->default(5);
            $table->enum('status', ['setup', 'active', 'completed'])->default('setup');
            $table->timestamps();
        });

        // Exposures (rounds/segments) — each with its own criteria
        Schema::create('exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Best in Professional Attire"
            $table->integer('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->decimal('carry_over_percentage', 5, 2)->default(0); // how much of prior exposures carry into this one
            $table->boolean('is_locked')->default(false); // locked = scoring finished
            $table->timestamps();
        });

        // Criteria now belongs to an exposure, not directly to contest
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exposure_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('percentage', 5, 2);
            $table->decimal('min_score', 5, 2)->default(1);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Contestants
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->integer('number');
            $table->string('name');
            $table->string('name2')->nullable();
            $table->string('team_name')->nullable();
            $table->timestamps();
        });

        // Judges
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('number');
            $table->timestamps();
        });

        // Scores — now tied to an exposure
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exposure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contestant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->timestamps();

            $table->unique(['exposure_id', 'contestant_id', 'judge_id', 'criteria_id'], 'scores_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
        Schema::dropIfExists('judges');
        Schema::dropIfExists('contestants');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('exposures');
        Schema::dropIfExists('contests');
    }
};
