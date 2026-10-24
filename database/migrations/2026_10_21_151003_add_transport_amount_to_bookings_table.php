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
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'transport_amount')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->decimal('transport_amount', 12, 2)->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'transport_amount')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropColumn('transport_amount');
            });
        }
    }
};
