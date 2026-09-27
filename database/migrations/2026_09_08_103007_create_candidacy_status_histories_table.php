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
        Schema::create('candidacy_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidacy_id')->constrained('candidacies')->restrictOnDelete();
            $table->tinyInteger('from_status')->nullable();
            $table->tinyInteger('to_status');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidacy_status_histories');
    }
};
