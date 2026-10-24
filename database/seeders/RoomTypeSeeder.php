<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $types = [
            [1, 'أحادية', 1],
            [2, 'ثنائية', 2],
            [3, 'ثلاثية', 3],
            [4, 'رباعية', 4],
            [5, 'خماسية', 5],
            [6, 'سداسية', 6],
        ];

        DB::table('room_types')->upsert(
            array_map(fn (array $type): array => [
                'id' => $type[0],
                'name' => $type[1],
                'size' => $type[2],
                'price' => 0,
                'total_price' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $types),
            ['id'],
            ['name', 'size', 'price', 'total_price', 'updated_at'],
        );
    }
}
