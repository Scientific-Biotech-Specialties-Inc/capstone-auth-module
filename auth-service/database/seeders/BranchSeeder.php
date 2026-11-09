<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Office',
                'address' => 'Makati City, Metro Manila',
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(['name' => $branchData['name']], $branchData);
        }

        $mainBranch = Branch::where('name', 'Main Office')->first();
        if ($mainBranch) {
            UserProfile::whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
        }
    }
}
