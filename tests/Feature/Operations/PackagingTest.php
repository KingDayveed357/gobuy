<?php

namespace Tests\Feature\Operations;

use App\Livewire\Admin\Packaging\ManagePackaging;
use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Operations\Packaging\Models\PackagingUnit;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PackagingTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.packaging', '1');
    }

    public function test_the_packaging_screen_is_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.packaging.index'))
            ->assertOk()
            ->assertSeeLivewire(ManagePackaging::class);
    }

    public function test_the_packaging_screen_404s_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.packaging', '0');

        $this->get(route('admin.packaging.index'))->assertNotFound();
    }

    public function test_base_units_convert_packs_to_base_units(): void
    {
        $variant = Product::factory()->create()->primaryVariant();
        $carton = PackagingUnit::create(['product_variant_id' => $variant->id, 'name' => 'Carton', 'multiplier' => 12]);

        $this->assertSame(36, $carton->baseUnits(3));
        $this->assertSame(0, $carton->baseUnits(0));
    }

    public function test_unit_price_uses_its_own_price_or_derives_from_the_base(): void
    {
        $variant = Product::factory()->priced(500)->create()->primaryVariant(); // ₦500 base

        $derived = PackagingUnit::create(['product_variant_id' => $variant->id, 'name' => 'Pack', 'multiplier' => 6]);
        $this->assertTrue($derived->unitPrice()->equals(Money::fromNaira(3000))); // 500 × 6

        $own = PackagingUnit::create(['product_variant_id' => $variant->id, 'name' => 'Carton', 'multiplier' => 12, 'retail_price' => Money::fromNaira(5500)]);
        $this->assertTrue($own->unitPrice()->equals(Money::fromNaira(5500)));
    }

    public function test_the_livewire_screen_adds_edits_and_removes_packaging(): void
    {
        $variant = Product::factory()->create()->primaryVariant();

        $component = Livewire::test(ManagePackaging::class)
            ->call('choose', $variant->id)
            ->set('name', 'Carton')
            ->set('multiplier', 12)
            ->set('price', '5500')
            ->call('save');

        $this->assertDatabaseHas('packaging_units', ['product_variant_id' => $variant->id, 'name' => 'Carton', 'multiplier' => 12, 'retail_price' => 550000]);

        $unit = PackagingUnit::where('product_variant_id', $variant->id)->first();
        $component->call('edit', $unit->id)->set('multiplier', 24)->call('save');
        $this->assertSame(24, $unit->fresh()->multiplier);

        $component->call('delete', $unit->id);
        $this->assertDatabaseMissing('packaging_units', ['id' => $unit->id]);
    }
}
