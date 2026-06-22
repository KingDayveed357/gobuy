<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesale_profiles', function (Blueprint $table) {
            // Why they want wholesale access (free text from the applicant).
            $table->text('intent')->nullable()->after('business_address');
            $table->string('industry')->nullable()->after('intent');
            $table->string('status')->default('pending')->after('industry');
            // Pricing tier assigned on approval — readiness for tiered pricing.
            $table->string('tier')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_profiles', function (Blueprint $table) {
            $table->dropColumn(['intent', 'industry', 'status', 'tier']);
        });
    }
};
