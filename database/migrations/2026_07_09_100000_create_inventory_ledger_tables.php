<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CO-1 keystone: the inventory ledger. `product_variants.stock` stays the Core
 * read-model, but every change now flows through an append-only movement log
 * against a location — the permanent audit trail that replaces the notebook.
 *
 * A single "Default" location is seeded so a plain single-location store never
 * knows locations exist; the multi-location UI is an optional module on top.
 * Existing stock is backfilled as opening balances so the ledger reconciles
 * from day one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('type', 32)->nullable(); // shop / storage / warehouse / supplier …
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->integer('on_hand')->default(0);
            $table->timestamps();

            $table->unique(['product_variant_id', 'inventory_location_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24); // sale / return / purchase / adjustment / opening …
            $table->integer('quantity');       // signed delta actually applied
            $table->integer('quantity_after');  // on-hand at this location afterwards
            $table->nullableMorphs('reference'); // order / return / adjustment / PO / transfer …
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable()->index(); // append-only: no updated_at

            $table->index(['product_variant_id', 'type']);
        });

        $this->seedDefaultLocationAndBackfill();
    }

    /**
     * Create the Default location and adopt current stock as opening balances,
     * so Σ movements == stock level == variants.stock for every variant.
     */
    private function seedDefaultLocationAndBackfill(): void
    {
        $now = now();

        $locationId = DB::table('inventory_locations')->insertGetId([
            'name' => 'Default', 'code' => 'default', 'is_default' => true,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('product_variants')->where('stock', '>', 0)->orderBy('id')
            ->chunk(500, function ($variants) use ($locationId, $now): void {
                $levels = [];
                $movements = [];

                foreach ($variants as $variant) {
                    $levels[] = [
                        'product_variant_id' => $variant->id,
                        'inventory_location_id' => $locationId,
                        'on_hand' => $variant->stock,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                    $movements[] = [
                        'product_variant_id' => $variant->id,
                        'inventory_location_id' => $locationId,
                        'type' => 'opening',
                        'quantity' => $variant->stock,
                        'quantity_after' => $variant->stock,
                        'note' => 'Opening balance (migration)',
                        'created_at' => $now,
                    ];
                }

                DB::table('stock_levels')->insert($levels);
                DB::table('inventory_movements')->insert($movements);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('inventory_locations');
    }
};
