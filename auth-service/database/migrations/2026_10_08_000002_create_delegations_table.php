<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A Delegation hands an entire identity ROLE (not a granular permission)
     * from one user to another for a bounded date range. While active, the
     * delegate is treated as an additional holder of that role — e.g. for
     * CMS approval-workflow steps that are satisfied by "anyone holding role
     * R" (Option C from the workflow engine plan: auth-module owns the
     * delegation record, consumers just ask "who holds role R now").
     */
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('delegate_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['scheduled', 'active', 'expired', 'revoked'])->default('scheduled');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['role_id', 'status']);
            $table->index(['delegate_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
