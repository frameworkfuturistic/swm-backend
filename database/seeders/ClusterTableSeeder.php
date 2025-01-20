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
        $ulb_id = 1;  // Adjust ULB ID as needed
        $running_counter = 1;  // Start counter

        for ($i = 1; $i <= 20; $i++) {
            $cluster_code = sprintf('C%02d%04d', $ulb_id, $running_counter); // Format: C + ulb_id padded to 2 digits + counter padded to 4 digits
            DB::table('clusters')->insert([
                'ulb_id' => $ulb_id,
                'ward_id' => $faker->numberBetween(1, 10),
                'cluster_name' => $faker->unique()->words(3, true),
                'cluster_address' => $faker->optional()->address,
                'pincode' => $faker->optional()->regexify('[1-9][0-9]{5}'),
                'landmark' => $faker->optional()->streetName,
                'cluster_type' => $faker->randomElement(['Apartment', 'Building', 'Govt-Building', 'Colony', 'Other']),
                'mobile_no' => $faker->optional()->numerify('###########'),
                'whatsapp_no' => $faker->optional()->numerify('###########'),
                'longitude' => $faker->optional()->longitude,
                'latitude' => $faker->optional()->latitude,
                'inclusion_date' => $faker->optional()->date(),
                'verification_date' => $faker->optional()->date(),
                'is_active' => $faker->boolean(80),
                'is_verified' => $faker->boolean(90),
                'vrno' => 1,
                'cluster_code' => $cluster_code,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => $faker->optional(0.1)->dateTime(),
            ]);
            $running_counter++;  // Increment the counter for the next cluster
        }
    }
}
