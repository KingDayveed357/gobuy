<?php

namespace Tests\Feature\Commerce;

use App\Models\Setting;
use App\Support\Commerce\CommerceModules;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommerceModulesTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** A fake registry so the mechanism is tested independently of shipped state. */
    private function modules(): CommerceModules
    {
        return new CommerceModules([
            'ledger' => ['label' => 'Ledger', 'depends' => [], 'shipped' => true],
            'transfers' => ['label' => 'Transfers', 'depends' => ['ledger'], 'shipped' => true],
            'soon' => ['label' => 'Coming Soon', 'depends' => [], 'shipped' => false],
        ]);
    }

    public function test_modules_are_off_by_default(): void
    {
        $this->assertFalse($this->modules()->enabled('ledger'));
        $this->assertSame([], $this->modules()->enabledKeys());
    }

    public function test_only_shipped_modules_are_available(): void
    {
        $this->assertEqualsCanonicalizing(['ledger', 'transfers'], array_keys($this->modules()->available()));
    }

    public function test_enabling_a_module_turns_it_on(): void
    {
        $modules = $this->modules();
        $modules->enable('ledger');

        $this->assertTrue($modules->enabled('ledger'));
    }

    public function test_enabling_a_dependent_pulls_in_its_dependency(): void
    {
        $modules = $this->modules();
        $modules->enable('transfers');

        $this->assertTrue($modules->enabled('transfers'));
        $this->assertTrue($modules->enabled('ledger'), 'the dependency should be enabled automatically');
    }

    public function test_a_dependent_is_inert_while_its_dependency_is_off(): void
    {
        // Force an inconsistent stored state: dependent on, dependency off.
        Setting::put('modules.transfers', '1');
        Setting::put('modules.ledger', '0');

        $this->assertFalse($this->modules()->enabled('transfers'));
    }

    public function test_disabling_a_dependency_cascades_off_its_dependents(): void
    {
        $modules = $this->modules();
        $modules->enable('transfers'); // both on

        $modules->disable('ledger');

        $this->assertFalse($modules->enabled('ledger'));
        $this->assertFalse($modules->enabled('transfers'), 'the dependent should switch off with its dependency');
    }

    public function test_an_unshipped_module_cannot_be_enabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->modules()->enable('soon');
    }
}
