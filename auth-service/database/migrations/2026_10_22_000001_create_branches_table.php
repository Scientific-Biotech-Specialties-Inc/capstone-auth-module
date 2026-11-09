<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('user_profiles') && !Schema::hasColumn('user_profiles', 'branch_id')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('department_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_profiles') && Schema::hasColumn('user_profiles', 'branch_id')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        Schema::dropIfExists('branches');
    }
};
