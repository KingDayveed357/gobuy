<?php

namespace App\Modules\Logistics\Database\Seeders;

use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\PickupLocation;
use App\Support\Money;
use Illuminate\Database\Seeder;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Port Harcourt & Rivers', 'slug' => 'ph-rivers',
                'base_fee' => Money::fromNaira(1000), 'per_kg_fee' => Money::fromNaira(200),
                'free_over_subtotal' => Money::fromNaira(50000), 'sort_order' => 1,
                'states' => ['Rivers'],
            ],
            [
                'name' => 'South-South', 'slug' => 'south-south',
                'base_fee' => Money::fromNaira(2500), 'per_kg_fee' => Money::fromNaira(400),
                'free_over_subtotal' => null, 'sort_order' => 2,
                'states' => ['Bayelsa', 'Akwa Ibom', 'Cross River', 'Delta', 'Edo'],
            ],
            [
                'name' => 'Nationwide', 'slug' => 'nationwide',
                'base_fee' => Money::fromNaira(4000), 'per_kg_fee' => Money::fromNaira(600),
                'free_over_subtotal' => null, 'sort_order' => 3,
                'states' => [], // fallback zone for any unmapped state
            ],
        ];

        foreach ($zones as $data) {
            $states = $data['states'];
            unset($data['states']);

            $zone = DeliveryZone::updateOrCreate(['slug' => $data['slug']], $data);
            $zone->states()->delete();
            foreach ($states as $state) {
                $zone->states()->create(['state' => $state]);
            }
        }

        $pickups = [
            ['name' => 'GoBuy Port Harcourt Hub', 'address' => '24 Aba Road', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'phone' => '08030000001', 'opening_hours' => 'Mon–Sat, 9am–6pm'],
            ['name' => 'GoBuy Trans-Amadi Pickup', 'address' => '7 Trans-Amadi Industrial Layout', 'city' => 'Port Harcourt', 'state' => 'Rivers', 'phone' => '08030000002', 'opening_hours' => 'Mon–Fri, 9am–5pm'],
        ];

        foreach ($pickups as $pickup) {
            \App\Modules\Logistics\Models\Location::updateOrCreate(['name' => $pickup['name']], $pickup);
        }
    }
}
