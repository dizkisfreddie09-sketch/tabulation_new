<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['single', 'double', 'group'])->default('single');
            $table->integer('judge_count')->default(5);
            $table->enum('status', ['setup', 'active', 'completed'])->default('setup');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contests');
    }
};
