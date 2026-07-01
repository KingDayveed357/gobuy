<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Owner-only role management (behind the super_admin gate). Roles are reusable
 * templates: configure permissions once, assign to any number of staff. The
 * Super Admin role is immutable here — its access comes from Gate::before, not
 * from stored permissions, so it is never editable/cloneable/deletable.
 */
class RoleController extends Controller
{
    private const GUARD = 'admin';

    public function index(): View
    {
        $roles = Role::where('guard_name', self::GUARD)
            ->withCount('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'peopleCounts' => $roles->mapWithKeys(fn (Role $r) => [$r->id => $r->users()->count()]),
            'superAdminRole' => config('rbac.super_admin_role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(['guard_name' => self::GUARD]),
            'modules' => config('rbac.modules'),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => self::GUARD]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', "Role “{$role->name}” created.");
    }

    public function edit(Role $role): View|RedirectResponse
    {
        if ($this->isSuperAdminRole($role)) {
            return redirect()->route('admin.roles.index')->with('error', 'The Super Admin role cannot be edited.');
        }

        return view('admin.roles.edit', [
            'role' => $role,
            'modules' => config('rbac.modules'),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($this->isSuperAdminRole($role)) {
            return redirect()->route('admin.roles.index')->with('error', 'The Super Admin role cannot be edited.');
        }

        $data = $this->validateRole($request, $role);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', "Role “{$role->name}” updated.");
    }

    public function clone(Role $role): RedirectResponse
    {
        if ($this->isSuperAdminRole($role)) {
            return back()->with('error', 'The Super Admin role cannot be cloned.');
        }

        $clone = Role::create(['name' => $this->uniqueCloneName($role->name), 'guard_name' => self::GUARD]);
        $clone->syncPermissions($role->permissions->pluck('name')->all());

        return redirect()->route('admin.roles.edit', $clone)
            ->with('status', "Cloned “{$role->name}”. Adjust and save.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isSuperAdminRole($role)) {
            return back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Reassign the staff on this role before deleting it.');
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', "Role “{$name}” deleted.");
    }

    /**
     * @return array{name: string, permissions?: array<int, string>}
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $catalog = collect(config('rbac.modules'))->flatMap(fn (array $perms) => array_keys($perms))->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->where('guard_name', self::GUARD)->ignore($role?->id)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in($catalog)], // only grantable catalog permissions
        ]);
    }

    private function isSuperAdminRole(Role $role): bool
    {
        return $role->name === config('rbac.super_admin_role');
    }

    private function uniqueCloneName(string $base): string
    {
        $name = "{$base} (copy)";
        $i = 1;

        while (Role::where(['name' => $name, 'guard_name' => self::GUARD])->exists()) {
            $name = "{$base} (copy ".(++$i).')';
        }

        return $name;
    }
}
