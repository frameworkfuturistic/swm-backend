<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        //   $categories = [
        //       'Residential',
        //       'Commercial',
        //       'Industrial',
        //       'Institutional',
        //       'Public',
        //   ];

        //   foreach ($categories as $category) {
        //       DB::table('categories')->insert([
        //           'ulb_id' => rand(1, 5), // Assuming there are 5 ULBs in the `ulbs` table
        //           'category' => $category,
        //           'created_at' => now(),
        //           'updated_at' => now(),
        //       ]);
        //   }
        DB::statement('SET @auto_increment = 1;'); // or use a database-specific query if needed
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 1;');

        $categories = [
            ['id' => 1, 'ulb_id' => 21, 'category' => 'CINEMA HALL', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 2, 'ulb_id' => 21, 'category' => 'DHABA/DHARMSHALA/RESTAURANT', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 3, 'ulb_id' => 21, 'category' => 'FACTORY/WORKSHOP/INDUSTRY', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 4, 'ulb_id' => 21, 'category' => 'GODOWN/COLD STORAGE', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 5, 'ulb_id' => 21, 'category' => 'HOSPITALS/DISPENCERY/MEDICAL LABS', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 6, 'ulb_id' => 21, 'category' => 'HOSTEL/HOTEL/GUEST HOUSE', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 7, 'ulb_id' => 21, 'category' => 'MALL/SHOPPING COMPLEX', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 8, 'ulb_id' => 21, 'category' => 'MARRIAGE HALL', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 9, 'ulb_id' => 21, 'category' => 'OFFICE ROOM', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 10, 'ulb_id' => 21, 'category' => 'PETROL PUMP', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 11, 'ulb_id' => 21, 'category' => 'RESIDENTIAL', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 12, 'ulb_id' => 21, 'category' => 'SCHOOL/COACHING/EDUCATIONAL INSTITUTE', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
            ['id' => 13, 'ulb_id' => 21, 'category' => 'SHOPS', 'created_at' => Carbon::create(2025, 1, 20, 15, 31), 'updated_at' => Carbon::create(2025, 1, 20, 15, 31)],
        ];

        DB::table('categories')->insert($categories);
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 14;');
    }
}
