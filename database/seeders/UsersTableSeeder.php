<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('users')->insert([
            'ulb_id' => 1, // Assuming `ulbs` table has at least 5 records
            'name' => 'agency_admin',
            'email' => 'agency_admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password: "password"
            'role' => 'agency_admin',
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'ulb_id' => 1, // Assuming `ulbs` table has at least 5 records
            'name' => 'municipal_office',
            'email' => 'municipal_office@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password: "password"
            'role' => 'municipal_office',
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        //   $roles = ['agency_admin', 'municipal_office', 'tax_collector'];

        //      for ($i = 1; $i <= 10; $i++) {
        //          DB::table('users')->insert([
        //              'ulb_id' => 1, // Assuming `ulbs` table has at least 5 records
        //              'name' => 'User '.$i,
        //              'email' => 'user'.$i.'@example.com',
        //              'email_verified_at' => now(),
        //              'password' => Hash::make('password'), // Default password: "password"
        //              'role' => $roles[array_rand($roles)],
        //              'remember_token' => Str::random(10),
        //              'created_at' => now(),
        //              'updated_at' => now(),
        //          ]);
        //      }
    }
}
