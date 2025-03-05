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
        DB::statement('SET @auto_increment = 1;'); // or use a database-specific query if needed
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 1;');

        DB::table('categories')->insert([
         ['ulb_id' => 1, 'category_code' => '01', 'category' => 'RESIDENTIAL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '02', 'category' => 'DHABA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '03', 'category' => 'HOTEL GUEST HOUSE & HOSTEL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '04', 'category' => 'DHARAMSHALA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '05', 'category' => 'RESTAURANT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '06', 'category' => 'BAKERY FOOD COURT & BAKERY OUTLET', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '07', 'category' => 'SWEET SHOP', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '08', 'category' => 'FAST FOOD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '09', 'category' => 'THELA AND KHOMCHA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '10', 'category' => 'PAN AND TEA STALL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '11', 'category' => 'SHOPPING COMPLEX AC', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '12', 'category' => 'SHOPPING COMPLEX NON AC', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '13', 'category' => 'KARKHANA AND INDUSTRIES', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '14', 'category' => 'SHOPS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '15', 'category' => 'GODOWN AND COLD STORAGE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '16', 'category' => 'VEGETABLE AND FRUIT STALL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '17', 'category' => 'OFFICE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '18', 'category' => 'HOSPITAL, DISPENCERY AND LABORATORIES', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '19', 'category' => 'SCHOOL, COACHING AND EDUCATIONAL INSTITUTE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '20', 'category' => 'BANQUET AND MARRIAGE HALL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['ulb_id' => 1, 'category_code' => '21', 'category' => 'APARTMENT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         // Add remaining categories
     ]);

     $categories = DB::table('categories')->pluck('id', 'category_code');

     DB::table('sub_categories')->insert([
         ['category_id' => $categories['01'], 'subcategory_code' => '01', 'sub_category' => 'EWS', 'rate' => 20, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['01'], 'subcategory_code' => '02', 'sub_category' => 'LIG', 'rate' => 30, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['01'], 'subcategory_code' => '03', 'sub_category' => 'MIG', 'rate' => 50, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['01'], 'subcategory_code' => '04', 'sub_category' => 'HIG', 'rate' => 80, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['02'], 'subcategory_code' => '01', 'sub_category' => 'DHABA', 'rate' => 350, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['03'], 'subcategory_code' => '01', 'sub_category' => 'UPTO 10 ROOMS', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['03'], 'subcategory_code' => '02', 'sub_category' => '11-20 ROOMS', 'rate' => 1500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['03'], 'subcategory_code' => '03', 'sub_category' => '21-30 ROOMS', 'rate' => 2000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['03'], 'subcategory_code' => '04', 'sub_category' => '31-50 ROOMS', 'rate' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['03'], 'subcategory_code' => '05', 'sub_category' => '51 ROOMS & ABOVE', 'rate' => 10000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['03'], 'subcategory_code' => '06', 'sub_category' => '5 STAR HOTEL', 'rate' => 15000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['04'], 'subcategory_code' => '01', 'sub_category' => 'DHARAMSHALA', 'rate' => 800, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['05'], 'subcategory_code' => '01', 'sub_category' => 'RESTAURANT', 'rate' => 1500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['06'], 'subcategory_code' => '01', 'sub_category' => 'BAKERY FOOD COURT & BAKERY OUTLET', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['07'], 'subcategory_code' => '01', 'sub_category' => 'SWEET SHOP', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['08'], 'subcategory_code' => '01', 'sub_category' => 'FAST FOOD', 'rate' => 500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['09'], 'subcategory_code' => '01', 'sub_category' => 'THELA AND KHOMCHA', 'rate' => 200, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['10'], 'subcategory_code' => '01', 'sub_category' => 'PAN AND TEA STALL', 'rate' => 100, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['11'], 'subcategory_code' => '01', 'sub_category' => 'SHOPPING COMPLEX AC', 'rate' => 10000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['12'], 'subcategory_code' => '01', 'sub_category' => 'SHOPPING COMPLEX NON AC', 'rate' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         
         ['category_id' => $categories['13'], 'subcategory_code' => '01', 'sub_category' => 'SMALL', 'rate' => 500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['13'], 'subcategory_code' => '02', 'sub_category' => 'MEDIUM', 'rate' => 2000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['13'], 'subcategory_code' => '03', 'sub_category' => 'LARGE', 'rate' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         
         ['category_id' => $categories['14'], 'subcategory_code' => '01', 'sub_category' => 'WHOLESALE SHOP', 'rate' => 1500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['14'], 'subcategory_code' => '02', 'sub_category' => 'MAIN ROAD SHOP', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['14'], 'subcategory_code' => '03', 'sub_category' => 'MOHALLA SHOP', 'rate' => 250, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['14'], 'subcategory_code' => '04', 'sub_category' => 'OTHER SHOP', 'rate' => 150, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['15'], 'subcategory_code' => '01', 'sub_category' => 'GODOWN AND COLD STORAGE', 'rate' => 1500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['16'], 'subcategory_code' => '01', 'sub_category' => 'VEGETABLE AND FRUIT STALL', 'rate' => 200, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         
         ['category_id' => $categories['17'], 'subcategory_code' => '01', 'sub_category' => 'OFFICES UPTO 02 ROOMS OR 10 SQ MTR', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['17'], 'subcategory_code' => '02', 'sub_category' => 'OFFICES BETWEEN 03 TO 05 ROOMS OR BETWEEN 11 TO 25 SQ MTR', 'rate' => 250, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['17'], 'subcategory_code' => '03', 'sub_category' => 'OFFICES BETWEEN 06 TO 10 ROOMS OR BETWEEN 26 TO 50 SQ MTR', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['17'], 'subcategory_code' => '04', 'sub_category' => 'OFFICES BETWEEN 11 TO 20 ROOMS OR BETWEEN 51 TO 100 SQ MTR', 'rate' => 1500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['17'], 'subcategory_code' => '05', 'sub_category' => 'OFFICES ABOVE 20 ROOMS OR ABOVE 100 SQ MTR', 'rate' => 2500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['18'], 'subcategory_code' => '01', 'sub_category' => 'WITHOUT BED', 'rate' => 400, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['18'], 'subcategory_code' => '02', 'sub_category' => 'UPTO 20 BEDS', 'rate' => 500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['18'], 'subcategory_code' => '03', 'sub_category' => 'BEDS BETWEEN 21 TO 50', 'rate' => 10000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['18'], 'subcategory_code' => '04', 'sub_category' => 'ABOVE 50 BEDS', 'rate' => 20000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['19'], 'subcategory_code' => '01', 'sub_category' => 'GOVERNMENT', 'rate' => 200, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['19'], 'subcategory_code' => '02', 'sub_category' => 'PRIVATE', 'rate' => 1000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['19'], 'subcategory_code' => '03', 'sub_category' => 'RESIDENTIAL UPTO 50 ROOMS', 'rate' => 2000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['19'], 'subcategory_code' => '04', 'sub_category' => 'RESIDENTIAL ABOVE 50 ROOMS', 'rate' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         ['category_id' => $categories['20'], 'subcategory_code' => '01', 'sub_category' => 'UPTO 3000 SQ MTR', 'rate' => 2500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         ['category_id' => $categories['20'], 'subcategory_code' => '02', 'sub_category' => 'ABOVE 3000 SQ MTR', 'rate' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
         
         ['category_id' => $categories['21'], 'subcategory_code' => '01', 'sub_category' => 'APARTMENT', 'rate' => 80, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

         // Add more sub-categories as per the table provided
     ]);

        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 14;');
    }
}
