<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MealSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('meals')->upsert([
            ['id' => 1, 'name' => 'RO غداء', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'BB فطور', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'HB', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'FB', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['name', 'updated_at']);
    }
}
