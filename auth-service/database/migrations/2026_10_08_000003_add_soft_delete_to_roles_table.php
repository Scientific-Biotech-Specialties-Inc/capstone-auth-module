<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Roles are soft-deleted so that consumers like the CMS Visual Contract
     * Workflow Tracker can still resolve a role's last-known name for
     * historical display (with a "deleted role" warning), instead of a
     * foreign key simply vanishing.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
