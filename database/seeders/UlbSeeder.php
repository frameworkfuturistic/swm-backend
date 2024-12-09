<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UlbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ulbs = [
            ['ulb_name' => 'Ranchi Municipal Corporation'],
            ['ulb_name' => 'Dhanbad Municipal Corporation'],
            ['ulb_name' => 'Jamshedpur Notified Area Committee'],
            ['ulb_name' => 'Bokaro Steel City Municipal Corporation'],
            ['ulb_name' => 'Hazaribagh Municipal Corporation'],
            ['ulb_name' => 'Deoghar Municipal Corporation'],
            ['ulb_name' => 'Giridih Municipal Corporation'],
            ['ulb_name' => 'Ramgarh Municipal Corporation'],
            ['ulb_name' => 'Chakradharpur Municipal Corporation'],
            ['ulb_name' => 'Medininagar Municipal Corporation'],
        ];

        DB::table('ulbs')->insert($ulbs);
    }
}
