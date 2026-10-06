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
        Schema::table('notifications', function (Blueprint $table) {
            // どのステータス変更についての通知か(再送時に変更前後のステータスを辿るため)。
            // ステータス変更1回につき通知レコードは1件なので一意とする
            $table->foreignId('candidacy_status_history_id')
                ->after('candidacy_id')
                ->unique()
                ->constrained('candidacy_status_histories')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('candidacy_status_history_id');
        });
    }
};
