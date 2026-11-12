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
        // The full SBSI branch list is seeded by migration 2026_11_12_000001_seed_sbsi_branches.
        $branches = [
            [
                'name' => 'SBSI Makati Branch',
                'address' => '6023 Sacred Heart cor. Kamagong Sts., San Antonio, 1203 Makati City, Metro Manila',
            ],
        ];

        foreach ($branches as $branchData) {
            Branch::firstOrCreate(['name' => $branchData['name']], $branchData);
        }

        $mainBranch = Branch::where('name', 'SBSI Makati Branch')->first();
        if ($mainBranch) {
            UserProfile::whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
        }
    }
}
