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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 0:admin, 1:recruiter, 2:interviewer (App\Enums\UserRoleで定義予定)
            $table->unsignedTinyInteger('role');
            $table->timestamp('created_at')->useCurrent();

            // 同一ユーザーへの同一ロール重複付与を防止
            $table->unique(['user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
