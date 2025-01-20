<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $currentTimestamp = Carbon::now()->format('Y-m-d H:i:s');

        $wards = [];
        for ($i = 1; $i <= 54; $i++) {
            $wards[] = [
                'id' => $i,
                'ulb_id' => 21,
                'ward_name' => (string) $i,
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ];
        }

        DB::table('wards')->insert($wards);
    }
}
