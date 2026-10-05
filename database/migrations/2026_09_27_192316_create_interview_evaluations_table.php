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
        Schema::create('interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidacy_id')->constrained();
            $table->foreignId('interviewer_id')->constrained('users');
            $table->tinyInteger('round');
            $table->integer('score');
            $table->text('comment')->nullable();
            $table->timestamps();

            // 1選考 × 1面接官 × 1ラウンド につき評価は1件(REQ-009)
            $table->unique(['candidacy_id', 'interviewer_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_evaluations');
    }
};
