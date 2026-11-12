<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the SBSI branch list.
 */
return new class extends Migration
{
    /** Legacy name to new name and address */
    private const RENAMES = [
        'Main Office' => [
            'SBSI Makati Branch',
            '6023 Sacred Heart cor. Kamagong Sts., San Antonio, 1203 Makati City, Metro Manila',
            'Makati City, Metro Manila',
        ],
        'North Luzon Hub' => [
            'SBSI Isabela Branch',
            'Blk 8 Lot 2 Molave Subdivision, Brgy. Patul, Santiago City',
            'Blk 8 Lot 2 Molave Subdivision, Brgy. Patul, Santiago City, Isabela',
        ],
        'Cebu City Office' => [
            'SBSI Cebu Branch',
            '3264A Maria Theresa Village 2, Guadalupe, Cebu City',
            '3264A Maria Theresa Village 2, Guadalupe, Cebu City, Cebu',
        ],
    ];

    private const NEW_BRANCHES = [
        'SBSI Iloilo Branch' => 'Phil-Am Compound, Commission Civil St., Brgy. Our Lady of Fatima, Jaro, Iloilo City',
        'SBSI Cagayan de Oro Branch' => 'Casa Crisostomo Bldg., Manuel Vega St., Brgy. Consolacion, Cagayan de Oro City',
        'SBSI Davao Branch' => '165-Major Building, B-Bonifacio St., Brgy. 3-A Poblacion, Davao City',
        'SBSI Zamboanga Branch' => 'A&W Subdivision, Guiwan Highway, Zamboanga City',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $now = now();

        foreach (self::RENAMES as $legacyName => [$newName, $newAddress]) {
            $target = DB::table('branches')->where('name', $newName)->first();
            if ($target) {
                DB::table('branches')->where('id', $target->id)->update(['address' => $newAddress, 'updated_at' => $now]);

                continue;
            }

            $legacy = DB::table('branches')->where('name', $legacyName)->first();
            if ($legacy) {
                DB::table('branches')->where('id', $legacy->id)->update([
                    'name' => $newName,
                    'address' => $newAddress,
                    'updated_at' => $now,
                ]);

                continue;
            }

            DB::table('branches')->insert(['name' => $newName, 'address' => $newAddress, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach (self::NEW_BRANCHES as $name => $address) {
            $existing = DB::table('branches')->where('name', $name)->first();
            if ($existing) {
                DB::table('branches')->where('id', $existing->id)->update(['address' => $address, 'updated_at' => $now]);
            } else {
                DB::table('branches')->insert(['name' => $name, 'address' => $address, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $now = now();

        // Restore legacy names in place (ids unchanged, so user assignments survive the rollback).
        foreach (self::RENAMES as $legacyName => [$newName, , $legacyAddress]) {
            $renamed = DB::table('branches')->where('name', $newName)->first();
            $legacyTaken = DB::table('branches')->where('name', $legacyName)->exists();
            if ($renamed && ! $legacyTaken) {
                DB::table('branches')->where('id', $renamed->id)->update([
                    'name' => $legacyName,
                    'address' => $legacyAddress,
                    'updated_at' => $now,
                ]);
            }
        }

        // Remove inserted branches only when nobody is assigned to them; deleting an assigned branch
        foreach (array_keys(self::NEW_BRANCHES) as $name) {
            $branch = DB::table('branches')->where('name', $name)->first();
            if ($branch && ! DB::table('user_profiles')->where('branch_id', $branch->id)->exists()) {
                DB::table('branches')->where('id', $branch->id)->delete();
            }
        }
    }
};
