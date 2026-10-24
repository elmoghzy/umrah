<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->unique(['booking_id', 'customer_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_residents');
    }
};
