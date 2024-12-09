<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DenialReasonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $reasons = [
            'Taxpayer unavailable',
            'Refused to pay',
            'Disputed bill amount',
            'Door closed',
            'Incomplete application form',
            'Missing supporting documents',
            'Insufficient funds',
            'Non-compliance with regulations',
            'Invalid contact information',
            'Failure to meet deadlines',
            'Unverified identity',
            'Duplicate application',
            'Misrepresentation of facts',
            'Unauthorized usage',
        ];

        foreach ($reasons as $index => $reason) {
            DB::table('denial_reasons')->insert([
                'ulb_id' => 1, // Assuming there are 5 ULBs in the `ulbs` table
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
