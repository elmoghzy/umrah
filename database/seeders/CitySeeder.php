<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('cities')->upsert([
            ['id' => 1, 'name' => 'مكة', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'المدينة المنورة', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['name', 'updated_at']);
    }
}
