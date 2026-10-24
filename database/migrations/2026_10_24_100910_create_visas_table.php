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
        if (Schema::hasTable('visas')) {
            return;
        }

        Schema::create('visas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->string('status', 20)->default('pending');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejected_reason', 255)->nullable();
            $table->string('visa_number', 100)->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamp('first_downloaded_at')->nullable();
            $table->timestamp('last_downloaded_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['booking_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visas')) {
            Schema::drop('visas');
        }
    }
};
