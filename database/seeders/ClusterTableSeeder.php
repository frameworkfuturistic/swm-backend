<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClusterTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 20; $i++) {
            DB::table('clusters')->insert([
                'ulb_id' => 1, // Adjust range as per your ULB records
                'ward_id' => $faker->numberBetween(1, 10), // Adjust range as per your Wards records
                //  'verifiedby_id' => $faker->optional()->numberBetween(1, 100), // Assuming 100 users
                //  'appliedtc_id' => $faker->optional()->numberBetween(1, 100),
                'cluster_name' => $faker->unique()->words(3, true), // Generates unique cluster name
                'cluster_address' => $faker->optional()->address,
                'pincode' => $faker->optional()->regexify('[1-9][0-9]{5}'), // Valid 6-digit PIN code
                'landmark' => $faker->optional()->streetName,
                'cluster_type' => $faker->randomElement(['Apartment', 'Building', 'Govt Institution', 'Colony', 'Other', 'None']),
                'mobile_no' => $faker->optional()->numerify('###########'),
                'whatsapp_no' => $faker->optional()->numerify('###########'),
                'longitude' => $faker->optional()->longitude,
                'latitude' => $faker->optional()->latitude,
                'inclusion_date' => $faker->optional()->date(),
                'verification_date' => $faker->optional()->date(),
                'is_active' => $faker->boolean(80), // 80% chance of being active
                'is_verified' => $faker->boolean(90), // 50% chance of being verified
                'vrno' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => $faker->optional(0.1)->dateTime(), // 10% chance of soft deletion
            ]);
        }
    }
}
