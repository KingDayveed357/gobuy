<?php

namespace Tests\Feature;

use App\Modules\Logistics\Database\Seeders\LogisticsSeeder;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Services\DeliveryFeeService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeliveryFeeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DeliveryFeeService $fees;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LogisticsSeeder::class);
        $this->fees = app(DeliveryFeeService::class);
    }

    public function test_pickup_is_always_free(): void
    {
        $quote = $this->fees->quote(Shipment::METHOD_PICKUP, 'Rivers', 5000, Money::fromNaira(10000));
        $this->assertTrue($quote['fee']->isZero());
    }

    public function test_local_zone_charges_base_plus_weight(): void
    {
        // Rivers: base ₦1,000 + ceil(2.5kg)=3 × ₦200 = ₦1,600.
        $quote = $this->fees->quote(Shipment::METHOD_HOME, 'Rivers', 2500, Money::fromNaira(5000));
        $this->assertSame(Money::fromNaira(1600)->kobo, $quote['fee']->kobo);
        $this->assertSame('Port Harcourt & Rivers', $quote['zone']->name);
    }

    public function test_free_delivery_above_threshold(): void
    {
        // Rivers free over ₦50,000.
        $quote = $this->fees->quote(Shipment::METHOD_HOME, 'Rivers', 2500, Money::fromNaira(60000));
        $this->assertTrue($quote['fee']->isZero());
    }

    public function test_unmapped_state_falls_back_to_nationwide(): void
    {
        $quote = $this->fees->quote(Shipment::METHOD_HOME, 'Kano', 0, Money::fromNaira(5000));
        $this->assertSame('Nationwide', $quote['zone']->name);
        $this->assertSame(Money::fromNaira(4000)->kobo, $quote['fee']->kobo);
    }

    public function test_south_south_state_maps_to_its_zone(): void
    {
        $quote = $this->fees->quote(Shipment::METHOD_HOME, 'Delta', 0, Money::fromNaira(5000));
        $this->assertSame('South-South', $quote['zone']->name);
    }
}
