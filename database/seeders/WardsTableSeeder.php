<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $wards = [
            'Ward 1',
            'Ward 2',
            'Ward 3',
            'Ward 4',
            'Ward 5',
            'Ward 6',
            'Ward 7',
            'Ward 8',
            'Ward 9',
            'Ward 10',
        ];

        foreach ($wards as $index => $ward_name) {
            DB::table('wards')->insert([
                'ulb_id' => 1, // Assuming there are 5 ULBs in the `ulbs` table
                'ward_name' => $ward_name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
