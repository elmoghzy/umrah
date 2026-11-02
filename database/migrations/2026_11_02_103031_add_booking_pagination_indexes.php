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
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['deleted_at', 'id'], 'bookings_deleted_at_id_index');
        });

        Schema::table('visas', function (Blueprint $table) {
            $table->index(['booking_id', 'status', 'deleted_at'], 'visas_booking_status_deleted_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_deleted_at_id_index');
        });

        Schema::table('visas', function (Blueprint $table) {
            $table->dropIndex('visas_booking_status_deleted_at_index');
        });
    }
};
