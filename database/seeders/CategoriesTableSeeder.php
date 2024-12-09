<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            'Residential',
            'Commercial',
            'Industrial',
            'Institutional',
            'Public',
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'ulb_id' => rand(1, 5), // Assuming there are 5 ULBs in the `ulbs` table
                'category' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
