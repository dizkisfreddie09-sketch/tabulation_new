<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->integer('number');
            $table->string('name');
            $table->string('name2')->nullable(); // for double contestant type
            $table->string('team_name')->nullable(); // for group contestant type
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contestants');
    }
};
