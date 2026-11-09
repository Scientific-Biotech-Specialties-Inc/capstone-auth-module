<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a `nav_group` column to `roles`. This is independent of the role's
     * display name — it exists so that CRMS-capstone (and any other
     * consuming system) can route a user to the correct area of the app by
     * group ("admin" | "manager" | "staff") instead of hardcoding literal
     * role names. New custom roles created later (e.g. "Regulatory Officer")
     * just need this field set to become routable with zero frontend changes.
     *
     * NOTE: uses DB::table() rather than the Role Eloquent model. Migrations
     * run in historical order against whatever columns existed at the time
     * they were written; going through the model ties this migration to
     * the model's current global scopes (e.g. a later SoftDeletes trait
     * filtering on `deleted_at`, which does not exist yet at this point in
     * migration history on a fresh `migrate`).
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('nav_group')->nullable()->after('description');
        });

        // Backfill existing roles so nothing breaks for current users.
        $backfill = [
            'admin'   => ['Super Admin', 'IT Admin', 'Admin'],
            'manager' => ['Manager', 'Supervisor'],
            'staff'   => ['Sales', 'Employee', 'Finance'],
        ];

        foreach ($backfill as $group => $roleNames) {
            DB::table('roles')->whereIn('name', $roleNames)->update(['nav_group' => $group]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('nav_group');
        });
    }
};
