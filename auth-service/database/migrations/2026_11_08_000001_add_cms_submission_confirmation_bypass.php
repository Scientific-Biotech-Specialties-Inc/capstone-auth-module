<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'slug' => 'cms.contracts.bypass_submission_confirmation',
            'name' => 'Bypass Submission Confirmation',
            'system' => 'cms',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'cms.contracts.bypass_submission_confirmation');
        $id = $permission->value('id');

        if ($id !== null) {
            DB::table('role_permission')->where('permission_id', $id)->delete();
            $permission->delete();
        }
    }
};
