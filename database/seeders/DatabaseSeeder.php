<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//php artisan db:seed

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(UlbSeeder::class);
        $this->call(ParamSeeder::class);
        $this->call(WardsTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(RateListSeeder::class);
        $this->call(SubCategoriesTableSeeder::class);
        $this->call(DenialReasonsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(PaymentZonesTableSeeder::class);
        $this->call(ClusterTableSeeder::class);
        $this->call(EntityTableSeeder::class);
        $this->call(RatepayerTableSeeder::class);
        //   $this->call(TransactionTableSeeder::class);

        //   User::factory()->create([
        //       'name' => 'Test User',
        //       'email' => 'test@example.com',
        //   ]);
    }
}
