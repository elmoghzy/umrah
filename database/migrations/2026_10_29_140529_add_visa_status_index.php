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
        Schema::table('visas', function (Blueprint $table) {
            Schema::table('visas', function (Blueprint $table): void {
                $table->index(['status', 'deleted_at'], 'visas_status_deleted_at_index');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visas', function (Blueprint $table) {
            Schema::table('visas', function (Blueprint $table): void {
                $table->dropIndex('visas_status_deleted_at_index');
            });
        });
    }
};
