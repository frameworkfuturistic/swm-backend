<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('params')->insert([
            [
                'ulb_id' => 1,
                'param_name' => 'CURRENT_YEAR',
                'param_string' => '',
                'param_int' => 2024,
                'param_bool' => 0,
                'param_date' => null,
                'param_type' => 'INT',
            ],
        ]);
    }
}
