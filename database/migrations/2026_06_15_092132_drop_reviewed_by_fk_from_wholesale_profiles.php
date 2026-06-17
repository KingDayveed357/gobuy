<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `reviewed_by` now stores an admins.id (reviewer is an Admin, not a User),
     * so the old foreign key to the users table is dropped. We keep it a plain
     * nullable column rather than cross-linking the customer/admin boundaries.
     */
    public function up(): void
    {
        Schema::table('wholesale_profiles', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_profiles', function (Blueprint $table) {
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
