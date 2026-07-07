<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft'); // draft | scheduled | live | ended
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('accent_color')->nullable();
            $table->string('badge_text')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // Optional membership — content stays valid standalone; a campaign just
        // coordinates its members' schedule + activation.
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('placement')->constrained()->nullOnDelete();
        });
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('promotional_prices', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['homepage_sections', 'banners', 'coupons', 'promotional_prices'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('campaign_id');
            });
        }
        Schema::dropIfExists('campaigns');
    }
};
