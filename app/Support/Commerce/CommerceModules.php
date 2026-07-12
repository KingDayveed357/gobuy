<?php

namespace App\Support\Commerce;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * The single authority on which optional Commerce Operations modules exist and
 * which are switched on. Definitions come from config('commerce-modules'); the
 * on/off state lives in the cached Setting store (`modules.<key>`). A module is
 * only "enabled" when it is shipped, its own flag is on, AND every dependency is
 * enabled — so a disabled dependency can never leave a dependent half-live.
 *
 * Config is injectable so tests can drive the mechanism with a fake registry.
 */
class CommerceModules
{
    /**
     * @param  array<string, array<string, mixed>>|null  $definitions
     */
    public function __construct(private ?array $definitions = null) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return $this->definitions ??= config('commerce-modules.modules', []);
    }

    /**
     * Shipped (toggleable) modules, keyed by module key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function available(): array
    {
        return array_filter($this->definitions(), fn (array $def) => (bool) ($def['shipped'] ?? false));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    public function exists(string $key): bool
    {
        return $this->definition($key) !== null;
    }

    public function isShipped(string $key): bool
    {
        return (bool) ($this->definition($key)['shipped'] ?? false);
    }

    /**
     * Effectively on: shipped, its own flag set, and every dependency enabled.
     */
    public function enabled(string $key): bool
    {
        if (! $this->isShipped($key) || ! $this->flag($key)) {
            return false;
        }

        foreach ($this->dependencies($key) as $dependency) {
            if (! $this->enabled($dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function dependencies(string $key): array
    {
        return array_values($this->definition($key)['depends'] ?? []);
    }

    /**
     * Keys of modules that declare $key as a dependency.
     *
     * @return list<string>
     */
    public function dependents(string $key): array
    {
        $dependents = [];

        foreach ($this->definitions() as $candidate => $def) {
            if (in_array($key, $def['depends'] ?? [], true)) {
                $dependents[] = $candidate;
            }
        }

        return $dependents;
    }

    /**
     * Keys of every currently-enabled module.
     *
     * @return list<string>
     */
    public function enabledKeys(): array
    {
        return array_values(array_filter(array_keys($this->definitions()), fn (string $key) => $this->enabled($key)));
    }

    /**
     * Turn a module on, enabling any dependencies first. No-op if already on.
     */
    public function enable(string $key): void
    {
        if (! $this->isShipped($key)) {
            throw new InvalidArgumentException("Module [{$key}] is not available to enable.");
        }

        foreach ($this->dependencies($key) as $dependency) {
            $this->enable($dependency);
        }

        Setting::put('modules.'.$key, '1');
    }

    /**
     * Turn a module off, cascading off to anything that depends on it.
     */
    public function disable(string $key): void
    {
        foreach ($this->dependents($key) as $dependent) {
            $this->disable($dependent);
        }

        Setting::put('modules.'.$key, '0');
    }

    /** Whether the owner has switched this module's own flag on (ignores deps). */
    private function flag(string $key): bool
    {
        return Setting::get('modules.'.$key, '0') === '1';
    }
}
