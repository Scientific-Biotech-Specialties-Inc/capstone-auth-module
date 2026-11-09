<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records every previous name a role has had. Used by the CMS Visual
     * Contract Workflow Tracker to show an "edited" icon (distinct from the
     * "deleted" icon) on steps whose assigned role still exists but was
     * renamed after the step acted on it, with the previous name available
     * on hover.
     */
    public function up(): void
    {
        Schema::create('role_name_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('previous_name');
            $table->string('new_name');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['role_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_name_history');
    }
};
