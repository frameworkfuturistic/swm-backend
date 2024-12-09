<?php

namespace Database\Seeders;

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
            'Residential' => ['Apartment', 'House', 'Villa'],
            'Commercial' => ['Office', 'Shop', 'Restaurant'],
            'Industrial' => ['Factory', 'Warehouse'],
            'Institutional' => ['School', 'Hospital'],
            'Public' => ['Park', 'Playground', 'Bus Stop'],
        ];

        foreach ($subCategories as $category => $subCategoryList) {
            $categoryId = DB::table('categories')
                ->where('category', $category)
                ->first()->id;

            foreach ($subCategoryList as $subCategory) {
                DB::table('sub_categories')->insert([
                    'category_id' => $categoryId,
                    'sub_category' => $subCategory,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
