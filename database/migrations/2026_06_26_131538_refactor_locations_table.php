<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys that point to pickup_locations before renaming
        // We'll rename the constraint to avoid issues, or Laravel handles it on renaming?
        // Actually, let's just make city and state nullable first
        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->string('city')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->boolean('is_pickup')->default(true)->after('is_active');
            $table->boolean('is_return')->default(false)->after('is_pickup');
            $table->boolean('is_default_return')->default(false)->after('is_return');
        });

        // Migrate existing return_centres
        if (Schema::hasTable('return_centres')) {
            $centres = DB::table('return_centres')->get();
            foreach ($centres as $centre) {
                DB::table('pickup_locations')->insert([
                    'name' => $centre->name,
                    'address' => $centre->address,
                    'is_active' => $centre->is_active,
                    'is_pickup' => false,
                    'is_return' => true,
                    'is_default_return' => $centre->is_default,
                    'created_at' => $centre->created_at,
                    'updated_at' => $centre->updated_at,
                ]);
            }
            Schema::dropIfExists('return_centres');
        }

        // Drop the shipments foreign key so we can safely rename the table in all DB drivers
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['pickup_location_id']);
        });

        Schema::rename('pickup_locations', 'locations');

        // Restore the foreign key pointing to the new table name
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('pickup_location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['pickup_location_id']);
        });

        Schema::rename('locations', 'pickup_locations');

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('pickup_location_id')->references('id')->on('pickup_locations')->nullOnDelete();
        });

        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->dropColumn(['is_pickup', 'is_return', 'is_default_return']);
        });

        Schema::create('return_centres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
