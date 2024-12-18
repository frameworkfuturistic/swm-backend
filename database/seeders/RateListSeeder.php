<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RateListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('rate_list')->insert([
            ['ulb_id' => 1, 'rate_list' => 'Standard Rate', 'amount' => 100, 'vrno' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ulb_id' => 1, 'rate_list' => 'Premium Rate', 'amount' => 200, 'vrno' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ulb_id' => 1, 'rate_list' => 'Discount Rate', 'amount' => 50, 'vrno' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
