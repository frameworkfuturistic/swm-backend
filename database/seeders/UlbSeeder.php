<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UlbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentTime = Carbon::createFromFormat('d/m/Y H:i', '20/01/2025 16:03');
        DB::statement('SET @auto_increment = 1;'); // or use a database-specific query if needed
        DB::statement('ALTER TABLE ulbs AUTO_INCREMENT = 1;');

        $ulbs = [
            ['id' => 1, 'ulb_name' => 'Hariharganj Nagar Panchayat'],
            ['id' => 2, 'ulb_name' => 'Barharwa Nagar Panchayat'],
            ['id' => 3, 'ulb_name' => 'Barki Sarai Nagar Panchayat'],
            ['id' => 4, 'ulb_name' => 'Basukinath Nagar Panchayat'],
            ['id' => 5, 'ulb_name' => 'Bisharampur Nagar Panchayat'],
            ['id' => 6, 'ulb_name' => 'Bundu Nagar Panchayat'],
            ['id' => 7, 'ulb_name' => 'Chaibasa Nagar Parishad'],
            ['id' => 8, 'ulb_name' => 'Chakradharpur Nagar Parishad'],
            ['id' => 9, 'ulb_name' => 'Chakulia Nagar Panchayat'],
            ['id' => 10, 'ulb_name' => 'Chas Municipal Corporation'],
            ['id' => 11, 'ulb_name' => 'Chatra Nagar Parishad'],
            ['id' => 12, 'ulb_name' => 'Chhattarpur Nagar Panchayat'],
            ['id' => 13, 'ulb_name' => 'Chirkunda Nagar Panchayat'],
            ['id' => 14, 'ulb_name' => 'Deoghar Municipal Corporation'],
            ['id' => 15, 'ulb_name' => 'Dhanbad Municipal Corporation'],
            ['id' => 16, 'ulb_name' => 'Dhanwar Nagar Panchayat'],
            ['id' => 17, 'ulb_name' => 'Domchanch Nagar Panchayat'],
            ['id' => 18, 'ulb_name' => 'Dumka Nagar Parishad'],
            ['id' => 19, 'ulb_name' => 'Garhwa Nagar Parishad'],
            ['id' => 20, 'ulb_name' => 'Giridih Municipal Corporation'],
            ['id' => 21, 'ulb_name' => 'Ranchi Municipal Corporation'],
            ['id' => 22, 'ulb_name' => 'Gumla Nagar Parishad'],
            ['id' => 23, 'ulb_name' => 'Adityapur Municipal Corporation'],
            ['id' => 24, 'ulb_name' => 'Hazaribagh Municipal Corporation'],
            ['id' => 25, 'ulb_name' => 'Hussainabad Nagar Panchayat'],
            ['id' => 26, 'ulb_name' => 'Jamtara Nagar Panchayat'],
            ['id' => 27, 'ulb_name' => 'Jamshedpur NAC'],
            ['id' => 28, 'ulb_name' => 'Jhumritilaiya Nagar Parishad'],
            ['id' => 29, 'ulb_name' => 'Jugsalai Nagar Parishad'],
            ['id' => 30, 'ulb_name' => 'Kapali Nagar Panchayat'],
            ['id' => 31, 'ulb_name' => 'Khunti Nagar Panchayat'],
            ['id' => 32, 'ulb_name' => 'Koderma Nagar Panchayat'],
            ['id' => 33, 'ulb_name' => 'Latehar Nagar Panchayat'],
            ['id' => 34, 'ulb_name' => 'Lohardaga Nagar Parishad'],
            ['id' => 35, 'ulb_name' => 'Madhupur Nagar Parishad'],
            ['id' => 36, 'ulb_name' => 'Mahagama Nagar Panchayat'],
            ['id' => 37, 'ulb_name' => 'Mango Municipal Corporation'],
            ['id' => 38, 'ulb_name' => 'Manjhiaon Nagar Panchayat'],
            ['id' => 39, 'ulb_name' => 'Medininagar Municipal Corporation'],
            ['id' => 40, 'ulb_name' => 'Mihijham Nagar Parishad'],
            ['id' => 41, 'ulb_name' => 'Pakur Nagar Parishad'],
            ['id' => 42, 'ulb_name' => 'Phusro Nagar Parishad'],
            ['id' => 43, 'ulb_name' => 'Rajmahal Nagar Panchayat'],
            ['id' => 44, 'ulb_name' => 'Ramgarh Cantonment Board'],
            ['id' => 45, 'ulb_name' => 'Ramgarh Nagar Parishad'],
            ['id' => 46, 'ulb_name' => 'Godda Nagar Panchayat'],
            ['id' => 47, 'ulb_name' => 'Sahibganj Nagar Parishad'],
            ['id' => 48, 'ulb_name' => 'Saraikela Nagar Panchayat'],
            ['id' => 49, 'ulb_name' => 'Simdega Nagar Parishad'],
            ['id' => 50, 'ulb_name' => 'Sri Bansidhar Nagar Panchayat'],
        ];

        foreach ($ulbs as &$ulb) {
            $ulb['created_at'] = $currentTime;
            $ulb['updated_at'] = $currentTime;
        }

        DB::table('ulbs')->insert($ulbs);
        DB::statement('ALTER TABLE ulbs AUTO_INCREMENT = 51;');
    }
}
