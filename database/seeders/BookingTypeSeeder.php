<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('booking_types')->upsert([
            ['id' => 1, 'name' => 'عام', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'خاص', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['name', 'updated_at']);
    }
}
