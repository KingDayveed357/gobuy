<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase A — RBAC/staff foundations: the staff-lifecycle columns on `admins`
     * (invite + suspend + archive) and a flag marking shipped "system" roles.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->timestamp('invited_at')->nullable()->after('is_active');
            $table->foreignId('invited_by_id')->nullable()->after('invited_at')->constrained('admins')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable()->after('invited_by_id');
            $table->softDeletes(); // archive / offboard
        });

        Schema::table('roles', function (Blueprint $table): void {
            // Seeded default roles are marked so the UI can protect Super Admin and
            // re-seed idempotently. Custom roles created by the owner stay false.
            $table->boolean('is_system')->default(false)->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('invited_by_id');
            $table->dropColumn(['invited_at', 'suspended_at', 'deleted_at']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('is_system');
        });
    }
};
