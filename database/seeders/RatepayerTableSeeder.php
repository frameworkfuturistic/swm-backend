<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatepayerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('ratepayers')->insert([
            [
                'ulb_id' => 1,
                'entity_id' => 1,
                'cluster_id' => 1,
                'paymentzone_id' => 1,
                'last_payment_id' => 1,
                'last_transaction_id' => 1,
                'ratepayer_name' => 'John Doe',
                'ratepayer_address' => '123 Main St, Downtown',
                'consumer_no' => 'C12345',
                'mobile_no' => '1234567890',
                'landmark' => 'Near City Park',
                'whatsapp_no' => '1234567890',
                'bill_date' => Carbon::now(),
                'opening_demand' => 5000,
                'monthly_demand' => 1500,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'ulb_id' => 1,
                'entity_id' => 2,
                'cluster_id' => 2,
                'paymentzone_id' => 2,
                'last_payment_id' => 2,
                'last_transaction_id' => 2,
                'ratepayer_name' => 'Jane Smith',
                'ratepayer_address' => '456 Green Road, Suburbia',
                'consumer_no' => 'C67890',
                'mobile_no' => '9876543210',
                'landmark' => 'Opposite Green Park',
                'whatsapp_no' => '9876543210',
                'bill_date' => Carbon::now(),
                'opening_demand' => 8000,
                'monthly_demand' => 2000,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
