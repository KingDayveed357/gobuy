<?php

namespace Tests\Feature\Operations;

use App\Admin\Models\Admin;
use App\Livewire\Admin\Register\Register;
use App\Modules\Catalog\Models\Product;
use App\Modules\Operations\Register\Exceptions\RegisterException;
use App\Modules\Operations\Register\Models\CashSession;
use App\Modules\Operations\Register\Services\RegisterService;
use App\Modules\Operations\WalkIn\Services\WalkInSaleService;
use App\Modules\Order\Enums\PaymentMethod;
use App\Support\Commerce\CommerceModules;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actingAsAdmin('Super Admin');
        app(CommerceModules::class)->enable('ops.register'); // cascades ops.walk_in on
    }

    private function sell(int $variantId, int $qty, PaymentMethod $method): void
    {
        app(WalkInSaleService::class)->record([['variant_id' => $variantId, 'quantity' => $qty]], $method);
    }

    public function test_the_screen_is_reachable_when_on_and_404s_when_off(): void
    {
        $this->get(route('admin.register.index'))->assertOk()->assertSeeLivewire(Register::class);

        app(CommerceModules::class)->disable('ops.register');
        $this->get(route('admin.register.index'))->assertNotFound();
    }

    public function test_only_one_session_can_be_open_at_a_time(): void
    {
        $register = app(RegisterService::class);
        $register->open($this->admin, Money::fromNaira(5000));

        $this->expectException(RegisterException::class);
        $register->open($this->admin, Money::fromNaira(1000));
    }

    public function test_a_session_expects_the_float_plus_the_days_sales(): void
    {
        $variant = Product::factory()->stock(20)->priced(1000)->create()->primaryVariant();
        $session = app(RegisterService::class)->open($this->admin, Money::fromNaira(5000));

        $this->sell($variant->id, 2, PaymentMethod::Cash);      // ₦2,000 cash
        $this->sell($variant->id, 1, PaymentMethod::PosTerminal); // ₦1,000 POS

        $expected = $session->expected();
        $this->assertSame(Money::fromNaira(7000)->kobo, $expected['cash']->kobo, 'float + cash sales');
        $this->assertSame(Money::fromNaira(1000)->kobo, $expected['pos']->kobo);
        $this->assertSame(0, $expected['transfer']->kobo);
        $this->assertSame(2, $session->windowSales()['count']);
    }

    public function test_closing_with_an_exact_count_balances(): void
    {
        $variant = Product::factory()->stock(20)->priced(1000)->create()->primaryVariant();
        $register = app(RegisterService::class);
        $session = $register->open($this->admin, Money::fromNaira(5000));
        $this->sell($variant->id, 2, PaymentMethod::Cash);

        $expected = $session->expected();
        $closed = $register->close($session, $expected['cash'], $expected['pos'], $expected['transfer'], null, $this->admin);

        $this->assertFalse($closed->isOpen());
        $this->assertSame(Money::fromNaira(7000)->kobo, $closed->expected_cash->kobo); // snapshot frozen
        foreach ($closed->variance() as $variance) {
            $this->assertSame(0, $variance->kobo);
        }
    }

    public function test_a_short_count_records_a_negative_variance(): void
    {
        $variant = Product::factory()->stock(20)->priced(1000)->create()->primaryVariant();
        $register = app(RegisterService::class);
        $session = $register->open($this->admin, Money::fromNaira(5000));
        $this->sell($variant->id, 2, PaymentMethod::Cash); // expected cash ₦7,000

        // Only ₦6,500 in the drawer — ₦500 short.
        $closed = $register->close($session, Money::fromNaira(6500), Money::zero(), Money::zero(), 'gave change', $this->admin);

        $this->assertSame(Money::fromNaira(-500)->kobo, $closed->variance()['cash']->kobo);
    }

    public function test_the_livewire_screen_opens_and_closes_the_day(): void
    {
        Livewire::test(Register::class)
            ->set('openingFloat', '5000')
            ->call('open');

        $this->assertNotNull(CashSession::current());

        Livewire::test(Register::class)
            ->set('countedCash', '5000')
            ->call('close');

        $this->assertNull(CashSession::current(), 'the day is closed');
    }
}
