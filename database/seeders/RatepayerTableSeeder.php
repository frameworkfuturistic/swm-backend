<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatepayerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 1000; $i++) {
            DB::table('ratepayers')->insert([
                'ulb_id' => 1, // Assuming 50 ULBs
                'ward_id' => $faker->numberBetween(1, 10), // Assuming 200 Wards
                'entity_id' => $faker->boolean(50) ? $faker->numberBetween(1, 400) : null, // 50% chance of being null
                'cluster_id' => $faker->boolean(50) ? null : $faker->numberBetween(1, 20), // Mutually exclusive with entity_id
                'paymentzone_id' => $faker->numberBetween(1, 4),
                //  'paymentzone_id' => $faker->optional()->numberBetween(1, 5),
                'subcategory_id' => $faker->numberBetween(1, 5),
                'rate_id' => $faker->numberBetween(1, 3),
                'last_transaction_id' => null, //$faker->optional()->numberBetween(1, 500),
                'ratepayer_name' => $faker->name,
                'ratepayer_address' => $faker->address,
                'consumer_no' => $faker->unique()->regexify('[A-Z0-9]{10}'),
                'holding_no' => $faker->unique()->numerify('HLD-####'),
                'longitude' => $faker->optional()->longitude,
                'latitude' => $faker->optional()->latitude,
                'mobile_no' => $faker->optional()->numerify('###########'),
                'landmark' => $faker->optional()->streetName,
                'whatsapp_no' => $faker->optional()->numerify('###########'),
                'usage_type' => $faker->randomElement(['Residential', 'Commercial', 'Industrial', 'Institutional']),
                'status' => 'verified',
                'reputation' => 1,
                'current_demand' => $faker->optional()->numberBetween(100, 10000),
                'monthly_demand' => $faker->numberBetween(100, 5000),
                'vrno' => 1,
                'is_active' => $faker->boolean(80), // 80% chance of being active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
