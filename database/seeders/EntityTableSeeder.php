<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntityTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Define bounds for Ranchi area
        $longitudeMin = 85.25;
        $longitudeMax = 85.45;
        $latitudeMin = 23.25;
        $latitudeMax = 23.45;

        // Insert 400 entities
        for ($i = 0; $i < 400; $i++) {
            DB::table('entities')->insert([
                'ulb_id' => 1, // Assuming 10 ULBs exist
                'ward_id' => 1,
                'cluster_id' => null, // Assuming 5 clusters exist or null
                'subcategory_id' => rand(1, 6), // Assuming 20 subcategories exist
                'verifiedby_id' => rand(1, 2), // Assuming 50 users exist
                'appliedtc_id' => rand(1, 2), // Assuming 50 users exist
                'lastpayment_id' => null, // Assuming 100 payments exist

                'holding_no' => $faker->unique()->numerify('HLD-####'),
                'entity_name' => $faker->company,
                'entity_address' => $faker->address,
                'pincode' => $faker->numerify('834###'), // Ranchi pin codes
                'mobile_no' => $faker->numerify('98########'),
                'landmark' => $faker->streetName,
                'whatsapp_no' => $faker->numerify('91##########'),

                'longitude' => $faker->randomFloat(7, $longitudeMin, $longitudeMax),
                'latitude' => $faker->randomFloat(7, $latitudeMin, $latitudeMax),
                'inclusion_date' => $faker->date(),
                'verification_date' => $faker->boolean(50) ? $faker->date() : null,
                'opening_demand' => $faker->randomFloat(2, 1000, 50000),
                'monthly_demand' => $faker->randomFloat(2, 500, 5000),
                'is_active' => $faker->boolean(),
                'is_verified' => $faker->boolean(),
                'usage_type' => $faker->randomElement(['Residential', 'Commercial', 'Industrial', 'Institutional']),
                'status' => 'verified',

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
