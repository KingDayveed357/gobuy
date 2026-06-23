<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive columns that make returns correct and cheap to compute:
     *  - orders.delivered_at  : authoritative return-window clock
     *  - orders.refunded_total: single over-refund guard shared by the legacy
     *                           admin partial-refund path AND the Returns module
     *  - order_items.returned_quantity: O(1), race-safe "already returned" tally
     *  - products.is_returnable / return_window_days: per-product policy override
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('placed_at');
            $table->unsignedBigInteger('refunded_total')->default(0)->after('total'); // kobo
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('returned_quantity')->default(0)->after('quantity');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_returnable')->default(true)->after('status');
            $table->unsignedSmallInteger('return_window_days')->nullable()->after('is_returnable');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'refunded_total']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_returnable', 'return_window_days']);
        });
    }
};
