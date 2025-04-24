<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Entity;
use App\Models\Ratepayer;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatepayerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   //  public function run()
   //  {
   //      $faker = Faker::create();

   //      for ($i = 1; $i <= 1000; $i++) {
   //          DB::table('ratepayers')->insert([
   //              'ulb_id' => 21, // Assuming 50 ULBs
   //              'ward_id' => $faker->numberBetween(1, 10), // Assuming 200 Wards
   //              'entity_id' => $faker->boolean(50) ? $faker->numberBetween(1, 400) : null, // 50% chance of being null
   //             //  'cluster_id' => $faker->boolean(50) ? null : $faker->numberBetween(1, 20), // Mutually exclusive with entity_id
   //              'paymentzone_id' => $faker->numberBetween(1, 4),
   //              //  'paymentzone_id' => $faker->optional()->numberBetween(1, 5),
   //              'subcategory_id' => $faker->numberBetween(1, 5),
   //              'rate_id' => $faker->numberBetween(1, 3),
   //              'last_transaction_id' => null, //$faker->optional()->numberBetween(1, 500),
   //              'ratepayer_name' => $faker->name,
   //              'ratepayer_address' => $faker->address,
   //              'consumer_no' => $faker->unique()->regexify('[A-Z0-9]{10}'),
   //              'holding_no' => $faker->unique()->numerify('HLD-####'),
   //              'longitude' => $faker->optional()->longitude,
   //              'latitude' => $faker->optional()->latitude,
   //              'mobile_no' => $faker->optional()->numerify('###########'),
   //              'landmark' => $faker->optional()->streetName,
   //              'whatsapp_no' => $faker->optional()->numerify('###########'),
   //              'usage_type' => $faker->randomElement(['Residential', 'Commercial', 'Industrial', 'Institutional']),
   //              'status' => 'verified',
   //              'reputation' => 1,
   //              'current_demand' => $faker->optional()->numberBetween(100, 10000),
   //              'monthly_demand' => $faker->numberBetween(100, 5000),
   //              'vrno' => 1,
   //              'is_active' => $faker->boolean(80), // 80% chance of being active
   //              'created_at' => now(),
   //              'updated_at' => now(),
   //          ]);
   //      }
   //  }

   public function run()
    {
        // Seed ratepayers from entities
        Entity::all()->each(function (Entity $entity) {
            Ratepayer::create([
                'ulb_id' => 21,
                'ward_id' => $entity->ward_id,
                'entity_id' => $entity->id,
                'cluster_id' => null,
                'subcategory_id' => $entity->subcategory_id,
                'ratepayer_name' => $entity->entity_name,
                'ratepayer_address' => $entity->entity_address,
                'consumer_no' => $entity->holding_no,
                'holding_no' => $entity->holding_no,
                'longitude' => $entity->longitude,
                'latitude' => $entity->latitude,
                'mobile_no' => $entity->mobile_no,
                'landmark' => $entity->landmark,
                'whatsapp_no' => $entity->whatsapp_no,
                'usage_type' => $entity->usage_type,
                'status' => $entity->status,
                'is_active' => $entity->is_active,
                'vrno' => $entity->vrno,
                'created_at' => $entity->created_at,
                'updated_at' => $entity->updated_at,
            ]);
        });

        // Seed ratepayers from clusters
        Cluster::all()->each(function (Cluster $cluster) {
            Ratepayer::create([
                'ulb_id' => 21,
                'ward_id' => $cluster->ward_id,
                'entity_id' => null,
                'cluster_id' => $cluster->id,
                'subcategory_id' => DB::table('sub_categories')->inRandomOrder()->first()->id ?? 1, // Assuming a default subcategory if none exist
                'ratepayer_name' => $cluster->cluster_name,
                'ratepayer_address' => $cluster->cluster_address,
                'consumer_no' => $cluster->cluster_code,
                'holding_no' => null,
                'longitude' => $cluster->longitude,
                'latitude' => $cluster->latitude,
                'mobile_no' => $cluster->mobile_no,
                'landmark' => $cluster->landmark,
                'whatsapp_no' => $cluster->whatsapp_no,
                'usage_type' => 'Residential',
                'status' => 'verified', // Assuming clusters are verified by default
                'is_active' => $cluster->is_active,
                'vrno' => $cluster->vrno,
                'created_at' => $cluster->created_at,
                'updated_at' => $cluster->updated_at,
            ]);
        });
    }
}
