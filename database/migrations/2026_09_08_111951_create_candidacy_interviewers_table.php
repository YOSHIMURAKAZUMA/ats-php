<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidacy_interviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidacy_id')->constrained('candidacies')->restrictOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->tinyInteger('round');
            $table->timestamp('assigned_at');
            $table->unique(['candidacy_id', 'interviewer_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidacy_interviewers');
    }
};
