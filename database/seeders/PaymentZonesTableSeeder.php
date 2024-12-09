<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentZonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $paymentZones = [
            [
                'payment_zone' => 'Zone 1',
                'coordinates' => json_encode([
                    ['lat' => 28.7041, 'lng' => 77.1025],
                    ['lat' => 28.7051, 'lng' => 77.1035],
                    ['lat' => 28.7061, 'lng' => 77.1045],
                ]),
                'description' => 'Residential zone in area 1',
            ],
            [
                'payment_zone' => 'Zone 2',
                'coordinates' => json_encode([
                    ['lat' => 28.7091, 'lng' => 77.1065],
                    ['lat' => 28.7101, 'lng' => 77.1075],
                    ['lat' => 28.7111, 'lng' => 77.1085],
                ]),
                'description' => 'Commercial zone in area 2',
            ],
            [
                'payment_zone' => 'Zone 3',
                'coordinates' => json_encode([
                    ['lat' => 28.7151, 'lng' => 77.1105],
                    ['lat' => 28.7161, 'lng' => 77.1115],
                    ['lat' => 28.7171, 'lng' => 77.1125],
                ]),
                'description' => 'Industrial zone in area 3',
            ],
            [
                'payment_zone' => 'Zone 4',
                'coordinates' => json_encode([
                    ['lat' => 28.7201, 'lng' => 77.1135],
                    ['lat' => 28.7211, 'lng' => 77.1145],
                    ['lat' => 28.7221, 'lng' => 77.1155],
                ]),
                'description' => 'Public zone near park',
            ],
            [
                'payment_zone' => 'Zone 5',
                'coordinates' => json_encode([
                    ['lat' => 28.7251, 'lng' => 77.1165],
                    ['lat' => 28.7261, 'lng' => 77.1175],
                    ['lat' => 28.7271, 'lng' => 77.1185],
                ]),
                'description' => 'Institutional zone in area 5',
            ],
        ];

        foreach ($paymentZones as $zone) {
            DB::table('payment_zones')->insert([
                'ulb_id' => rand(1, 5), // Assuming there are 5 ULBs in the `ulbs` table
                'payment_zone' => $zone['payment_zone'],
                'coordinates' => $zone['coordinates'],
                'description' => $zone['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
