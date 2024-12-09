<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Generate 10 dummy transactions
        for ($i = 0; $i < 10; $i++) {
            DB::table('transactions')->insert([
                'ulb_id' => rand(1, 5), // Assuming 5 ULBs
                'tc_id' => rand(1, 10), // Assuming 10 tax collectors
                'ratepayer_id' => rand(1, 10), // Assuming 10 ratepayers
                'entity_id' => rand(1, 10), // Assuming 10 entities
                'cluster_id' => rand(1, 5), // Assuming 5 clusters
                'payment_id' => rand(1, 5), // Assuming 5 payments
                'event_time' => Carbon::now()->subDays(rand(0, 30)), // Random event time within last 30 days
                'event_type' => $this->getRandomEventType(),
                'remarks' => Str::random(50),
                'auto_remarks' => Str::random(50),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    // Helper function to randomly pick an event type
    private function getRandomEventType()
    {
        $eventTypes = ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'OTHER'];

        return $eventTypes[array_rand($eventTypes)];
    }
}
