<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $subCategories = [
            ['category_id' => 1, 'sub_category' => 'CINEMA HALL'],
            ['category_id' => 2, 'sub_category' => 'BAKERY/FOOD COURT'],
            ['category_id' => 2, 'sub_category' => 'DHABA'],
            ['category_id' => 2, 'sub_category' => 'DHARMSHALA'],
            ['category_id' => 2, 'sub_category' => 'RESTAURANT'],
            ['category_id' => 3, 'sub_category' => 'DOMESTIC & SMALL SCALE'],
            ['category_id' => 3, 'sub_category' => 'LARGE SCALE'],
            ['category_id' => 3, 'sub_category' => 'MAIN MARKET'],
            ['category_id' => 3, 'sub_category' => 'MEDIUM SCALE'],
            ['category_id' => 3, 'sub_category' => 'SHOWROOM'],
            ['category_id' => 3, 'sub_category' => 'WHOLESALE'],
            ['category_id' => 4, 'sub_category' => 'DOMESTIC & SMALL SCALE'],
            ['category_id' => 4, 'sub_category' => 'FOR NON DANGEROUS GOOD'],
            ['category_id' => 5, 'sub_category' => '20-50 BED'],
            ['category_id' => 5, 'sub_category' => 'ABOVE 50 BED'],
            ['category_id' => 5, 'sub_category' => 'HIG'],
            ['category_id' => 5, 'sub_category' => 'MIG'],
            ['category_id' => 5, 'sub_category' => 'UPTO 20 BED'],
            ['category_id' => 5, 'sub_category' => 'WITHOUT BED'],
            ['category_id' => 6, 'sub_category' => '11-20 ROOM'],
            ['category_id' => 6, 'sub_category' => '21-30 ROOM'],
            ['category_id' => 6, 'sub_category' => '30-50 ROOM'],
            ['category_id' => 6, 'sub_category' => '5 STAR & ABOVE'],
            ['category_id' => 6, 'sub_category' => 'ABOVE 50 ROOM'],
            ['category_id' => 6, 'sub_category' => 'HIG'],
            ['category_id' => 6, 'sub_category' => 'UPTO 10 ROOM'],
            ['category_id' => 6, 'sub_category' => 'UPTO 20 BED'],
            ['category_id' => 7, 'sub_category' => 'AC'],
            ['category_id' => 7, 'sub_category' => 'NON-AC'],
            ['category_id' => 8, 'sub_category' => 'ABOVE 3000 SqMtr'],
            ['category_id' => 8, 'sub_category' => 'UPTO 3000 SqMtr'],
            ['category_id' => 9, 'sub_category' => '11-20 ROOM 100m2'],
            ['category_id' => 9, 'sub_category' => '2 ROOM 10m2'],
            ['category_id' => 9, 'sub_category' => '3-5 ROOM 25m2'],
            ['category_id' => 9, 'sub_category' => '6-10 ROOM 50m2'],
            ['category_id' => 9, 'sub_category' => 'ABOVE 20 ROOM 100m2'],
            ['category_id' => 9, 'sub_category' => 'combined'],
            ['category_id' => 10, 'sub_category' => 'HIG'],
            ['category_id' => 10, 'sub_category' => 'PETROL PUMP'],
            ['category_id' => 11, 'sub_category' => '3-5 ROOM 25m2'],
            ['category_id' => 11, 'sub_category' => 'COLONY SHOP'],
            ['category_id' => 11, 'sub_category' => 'DOMESTIC & SMALL SCALE'],
            ['category_id' => 11, 'sub_category' => 'HIG'],
            ['category_id' => 11, 'sub_category' => 'LIG'],
            ['category_id' => 11, 'sub_category' => 'MIG'],
            ['category_id' => 11, 'sub_category' => 'SLUM (E.W.S)'],
            ['category_id' => 11, 'sub_category' => 'UPTO 10 ROOM'],
            ['category_id' => 11, 'sub_category' => 'WITHOUT BED'],
            ['category_id' => 12, 'sub_category' => 'BOARDING ABOVE 50'],
            ['category_id' => 12, 'sub_category' => 'BOARDING UPTO 50'],
            ['category_id' => 12, 'sub_category' => 'GOVT.'],
            ['category_id' => 12, 'sub_category' => 'NON- GOVT.'],
            ['category_id' => 13, 'sub_category' => 'BAKERY/FOOD COURT'],
            ['category_id' => 13, 'sub_category' => 'COLONY SHOP'],
            ['category_id' => 13, 'sub_category' => 'DHABA'],
            ['category_id' => 13, 'sub_category' => 'FAST FOOD'],
            ['category_id' => 13, 'sub_category' => 'FRUITS & VEGETABLE'],
            ['category_id' => 13, 'sub_category' => 'HAAT BAZZAR'],
            ['category_id' => 13, 'sub_category' => 'MAIN MARKET'],
            ['category_id' => 13, 'sub_category' => 'MEAT SHOP'],
            ['category_id' => 13, 'sub_category' => 'MIG'],
            ['category_id' => 13, 'sub_category' => 'NON-AC'],
            ['category_id' => 13, 'sub_category' => 'OTHERS'],
            ['category_id' => 13, 'sub_category' => 'PAAN/CHAI SHOP'],
            ['category_id' => 13, 'sub_category' => 'PETROL PUMP'],
            ['category_id' => 13, 'sub_category' => 'RESTAURANT'],
            ['category_id' => 13, 'sub_category' => 'SHOWROOM'],
            ['category_id' => 13, 'sub_category' => 'SWEET SHOP'],
            ['category_id' => 13, 'sub_category' => 'THELA/KHOMCHA'],
            ['category_id' => 13, 'sub_category' => 'WHOLESALE'],
            ['category_id' => 13, 'sub_category' => 'WITHOUT BED'],
        ];

        $now = Carbon::now();
        foreach ($subCategories as &$subCategory) {
            $subCategory['created_at'] = $now;
            $subCategory['updated_at'] = $now;
        }

        DB::statement('SET @auto_increment = 1;'); // or use a database-specific query if needed
        DB::statement('ALTER TABLE sub_categories AUTO_INCREMENT = 1;');

        DB::table('sub_categories')->insert($subCategories);
        DB::statement('ALTER TABLE sub_categories AUTO_INCREMENT = 1;');
    }
}
