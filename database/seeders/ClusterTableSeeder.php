<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClusterTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('clusters')->insert([
            [
                'ulb_id' => 1,
                'verifiedby_id' => 1,
                'tc_id' => 1,
                'cluster_name' => 'Downtown Apartments',
                'address' => '123 Main St, Downtown',
                'landmark' => 'Near City Park',
                'pincode' => '123456',
                'cluster_type' => 'Apartment',
                'mobile' => '1234567890',
                'whatsapp_no' => '1234567890',
                'longitude' => 75.123456,
                'latitude' => 22.123456,
                'inclusion_date' => Carbon::now(),
                'verification_date' => Carbon::now(),
                'is_active' => true,
                'is_verified' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'ulb_id' => 1,
                'verifiedby_id' => 2,
                'tc_id' => 2,
                'cluster_name' => 'Green Valley',
                'address' => '456 Green Road, Suburbia',
                'landmark' => 'Opposite Green Park',
                'pincode' => '654321',
                'cluster_type' => 'Building',
                'mobile' => '9876543210',
                'whatsapp_no' => '9876543210',
                'longitude' => 75.654321,
                'latitude' => 22.654321,
                'inclusion_date' => Carbon::now(),
                'verification_date' => Carbon::now(),
                'is_active' => true,
                'is_verified' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
