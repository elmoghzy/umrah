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
            Schema::table('bookings', function (Blueprint $table): void {
                $table->index(['created_at', 'deleted_at'], 'bookings_created_at_deleted_at_index');
            });

            Schema::table('booking_details', function (Blueprint $table): void {
                $table->index(['check_in', 'city_id'], 'booking_details_check_in_city_id_index');
                $table->index(['hotel_id', 'check_in'], 'booking_details_hotel_id_check_in_index');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            Schema::table('booking_details', function (Blueprint $table): void {
                $table->dropIndex('booking_details_check_in_city_id_index');
                $table->dropIndex('booking_details_hotel_id_check_in_index');
            });

            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropIndex('bookings_created_at_deleted_at_index');
            });
        });
    }
};
