<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editorial lifecycle, distinct from the schedule window + visibility.
        // Existing rows become 'published' so nothing changes on upgrade.
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->string('status')->default('published')->after('is_active'); // draft | published
        });
    }

    public function down(): void
    {
        Schema::table('homepage_sections', fn (Blueprint $table) => $table->dropColumn('status'));
    }
};
