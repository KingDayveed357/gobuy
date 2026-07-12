<?php

namespace App\Admin\Support;

use App\Admin\Models\Admin;
use App\Support\Commerce\CommerceModules;
use Illuminate\Support\Facades\Route;

class AdminNavigation
{
    public function __construct(private readonly CommerceModules $modules) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function resolve(Admin $admin): array
    {
        $items = [];

        foreach (config('admin-navigation', []) as $entry) {
            $type = $entry['type'] ?? 'link';

            // Owner-only entries (staff & role management) — never shown to delegated staff.
            if (($entry['super_admin'] ?? false) && ! $admin->isSuperAdmin()) {
                continue;
            }

            // Entries owned by an optional module vanish while that module is off.
            if (isset($entry['module']) && ! $this->modules->enabled($entry['module'])) {
                continue;
            }

            if ($type === 'section') {
                if ($this->adminCan($admin, $entry['permission'] ?? null)) {
                    $items[] = $entry;
                }

                continue;
            }

            if ($type === 'group') {
                $groupItems = $this->filterGroupItems($admin, $entry['items'] ?? []);

                if ($groupItems === [] || ! $this->adminCan($admin, $entry['permission'] ?? null)) {
                    continue;
                }

                $entry['items'] = $groupItems;
                $entry['expanded'] = $this->groupHasActiveChild($groupItems);
                $entry['active'] = $entry['expanded'];

                $items[] = $entry;

                continue;
            }

            if ($type === 'link') {
                // Skip entries that are search-only (not intended for the sidebar).
                if (($entry['sidebar'] ?? true) === false) {
                    continue;
                }

                if (! $this->adminCan($admin, $entry['permission'] ?? null)) {
                    continue;
                }

                $entry['active'] = $this->routeIs($entry['active'] ?? [$entry['route'] ?? '']);
                $items[] = $entry;
            }
        }

        return $this->pruneEmptySections($items);
    }

    /**
     * Drop section headers that end up with no groups or links beneath them —
     * e.g. an "Operations" section whose modules are all disabled. Keeps the
     * sidebar free of orphaned labels regardless of which modules are on.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function pruneEmptySections(array $items): array
    {
        $pruned = [];

        foreach ($items as $index => $entry) {
            if (($entry['type'] ?? null) === 'section') {
                $next = $items[$index + 1] ?? null;

                if ($next === null || ($next['type'] ?? null) === 'section') {
                    continue; // nothing renders under this section
                }
            }

            $pruned[] = $entry;
        }

        return array_values($pruned);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function groupHasActiveChild(array $items): bool
    {
        foreach ($items as $item) {
            if ($item['active'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string|list<string>|null  $permission
     */
    private function adminCan(Admin $admin, string|array|null $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if ($admin->can($perm)) {
                    return true;
                }
            }

            return false;
        }

        return $admin->can($permission);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function filterGroupItems(Admin $admin, array $items): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (isset($item['permission']) && ! $admin->can($item['permission'])) {
                continue;
            }

            if (isset($item['module']) && ! $this->modules->enabled($item['module'])) {
                continue;
            }

            $item['active'] = $this->routeIs($item['active'] ?? [$item['route'] ?? '']);
            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * @param  list<string>  $patterns
     */
    private function routeIs(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern !== '' && Route::is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
