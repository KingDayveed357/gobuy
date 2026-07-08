<?php

namespace App\Admin\Support;

use App\Admin\Models\Admin;
use Illuminate\Support\Facades\Route;

/**
 * AdminSearchIndex
 *
 * Builds a permission-filtered, URL-resolved search index from:
 *   1. config/admin-navigation.php  — navigation pages (DRY, co-located metadata)
 *   2. config/admin-search-index.php — additional actions not in the nav
 *
 * The resolved array is JSON-encoded and embedded in the page as
 * window.__gbPalette so the client-side palette can search it without
 * any network requests.
 *
 * Future-proofing: database-backed search (products, orders, customers) can be
 * added by injecting additional "source" resolvers into this class without
 * touching the static config or the JS engine.
 */
class AdminSearchIndex
{
    /**
     * Resolve the full, permission-filtered index for the given admin.
     *
     * @return list<array<string, mixed>>
     */
    public function resolve(Admin $admin): array
    {
        $entries = [];

        // ── Source 1: navigation config ──────────────────────────────────────
        $navConfig = config('admin-navigation', []);
        foreach ($navConfig as $entry) {
            $type = $entry['type'] ?? 'link';

            // Sections are never searchable
            if ($type === 'section') {
                continue;
            }

            // Explicit opt-out
            if (($entry['search'] ?? true) === false) {
                continue;
            }

            // Super-admin gate
            if (($entry['super_admin'] ?? false) && ! $admin->isSuperAdmin()) {
                continue;
            }

            if ($type === 'link') {
                if (! $this->adminCan($admin, $entry['permission'] ?? null)) {
                    continue;
                }

                $indexed = $this->buildFromNavLink($entry);
                if ($indexed !== null) {
                    $entries[] = $indexed;
                }
                continue;
            }

            if ($type === 'group') {
                if (! $this->adminCan($admin, $entry['permission'] ?? null)) {
                    continue;
                }

                foreach ($entry['items'] ?? [] as $item) {
                    // Item-level super-admin check (rare but possible)
                    if (($item['super_admin'] ?? false) && ! $admin->isSuperAdmin()) {
                        continue;
                    }

                    if (isset($item['permission']) && ! $admin->can($item['permission'])) {
                        continue;
                    }

                    if (($item['search'] ?? true) === false) {
                        continue;
                    }

                    $indexed = $this->buildFromNavLink($item, $entry['label'] ?? null);
                    if ($indexed !== null) {
                        $entries[] = $indexed;
                    }
                }
            }
        }

        // ── Source 2: actions registry ───────────────────────────────────────
        $actionConfig = config('admin-search-index', []);
        foreach ($actionConfig as $action) {
            $permission = $action['permission'] ?? null;

            if ($permission === 'super_admin') {
                if (! $admin->isSuperAdmin()) {
                    continue;
                }
            } elseif ($permission !== null && ! $admin->can($permission)) {
                continue;
            }

            $indexed = $this->buildFromAction($action);
            if ($indexed !== null) {
                $entries[] = $indexed;
            }
        }

        return $entries;
    }

    // ── Builders ─────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $item
     * @param  string|null           $groupLabel   parent group label (for fallback category)
     * @return array<string, mixed>|null
     */
    private function buildFromNavLink(array $item, ?string $groupLabel = null): ?array
    {
        $route = $item['route'] ?? null;
        if ($route === null) {
            return null;
        }

        $url = $this->routeUrl($route);
        if ($url === null) {
            return null;
        }

        // Derive category from breadcrumb or group label
        $breadcrumb = $item['breadcrumb'] ?? null;
        $category   = $breadcrumb
            ? (explode(' > ', $breadcrumb)[0] ?? $groupLabel ?? 'Navigation')
            : ($groupLabel ?? 'Navigation');

        return [
            'id'         => 'nav.' . $route,
            'label'      => $item['label'] ?? '',
            'subtitle'   => $item['subtitle'] ?? null,
            'breadcrumb' => $breadcrumb,
            'url'        => $url,
            'icon'       => $item['icon'] ?? 'link',
            'category'   => $category,
            'type'       => 'navigate',
            'keywords'   => $item['keywords'] ?? [],
            'aliases'    => $item['aliases'] ?? [],
            'priority'   => $item['priority'] ?? 50,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    private function buildFromAction(array $action): ?array
    {
        $route = $action['route'] ?? null;
        if ($route === null) {
            return null;
        }

        $url = $this->routeUrl($route);
        if ($url === null) {
            return null;
        }

        return [
            'id'         => $action['id'] ?? ('action.' . $route),
            'label'      => $action['label'] ?? '',
            'subtitle'   => $action['subtitle'] ?? null,
            'breadcrumb' => $action['breadcrumb'] ?? null,
            'url'        => $url,
            'icon'       => $action['icon'] ?? 'zap',
            'category'   => $action['category'] ?? 'Actions',
            'type'       => 'action',
            'keywords'   => $action['keywords'] ?? [],
            'aliases'    => $action['aliases'] ?? [],
            'priority'   => $action['priority'] ?? 50,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Safely generate a URL for a named route; returns null if the route
     * does not exist or cannot be generated without required parameters.
     */
    private function routeUrl(string $name): ?string
    {
        try {
            if (! Route::has($name)) {
                return null;
            }

            return route($name);
        } catch (\Exception) {
            return null;
        }
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
}
