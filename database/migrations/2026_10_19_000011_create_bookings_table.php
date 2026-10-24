<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('broker_id')->nullable()->constrained();
            $table->foreignId('airplane_id')->nullable()->constrained();
            $table->foreignId('booking_type_id')->nullable()->constrained();
            $table->unsignedInteger('booking_number')->unique();
            $table->text('notes')->nullable();
            $table->string('services_note', 500)->nullable();
            $table->decimal('service_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('stay_scope')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
