<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'public_token')) {
                $table->string('public_token', 64)->nullable();
            }

            if (! Schema::hasColumn('bookings', 'public_token_rotated_at')) {
                $table->timestamp('public_token_rotated_at')->nullable();
            }
        });

        if (! Schema::hasIndex('bookings', ['public_token'], 'unique')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->unique('public_token');
            });
        }

        DB::table('bookings')
            ->where(function ($query): void {
                $query->whereNull('public_token')->orWhere('public_token', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    do {
                        $token = Str::random(48);
                    } while (DB::table('bookings')->where('public_token', $token)->exists());

                    DB::table('bookings')->where('id', $booking->id)->update(['public_token' => $token]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasIndex('bookings', ['public_token'], 'unique')) {
                $table->dropUnique(['public_token']);
            }

            if (Schema::hasColumn('bookings', 'public_token_rotated_at')) {
                $table->dropColumn('public_token_rotated_at');
            }

            if (Schema::hasColumn('bookings', 'public_token')) {
                $table->dropColumn('public_token');
            }
        });
    }
};
