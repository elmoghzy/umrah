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
        if (Schema::hasTable('booking_transfers')) {
            return;
        }

        Schema::create('booking_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('transport_id')->constrained('transports');
            $table->foreignId('from_city_id')->constrained('cities');
            $table->foreignId('to_city_id')->constrained('cities');
            $table->date('travel_date')->nullable();
            $table->time('travel_time')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('booking_transfers')) {
            Schema::drop('booking_transfers');
        }
    }
};
