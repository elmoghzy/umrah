<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('meal_id')->nullable()->constrained();
            $table->foreignId('city_id')->constrained();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
