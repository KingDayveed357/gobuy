<?php

namespace App\Admin\Controllers;

use App\Admin\Mail\StaffInvitationMail;
use App\Admin\Models\Admin;
use App\Admin\Models\AdminActivity;
use App\Admin\Notifications\SecurityAlertNotification;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Owner-only staff lifecycle: invite → activate → assign role → suspend →
 * reactivate → replace → archive. Roles are reusable, so replacing a person is
 * just suspend-and-reinvite with the same role.
 */
class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'active';

        $staff = $this->scopeForStatus(Admin::query()->with('roles'), $status)
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', [
            'staff' => $staff,
            'status' => $status,
            'roles' => $this->assignableRoles(),
            'invitePrefill' => $request->string('invite')->toString(), // role to preselect (from "Replace")
            'counts' => [
                'active' => $this->scopeForStatus(Admin::query(), 'active')->count(),
                'invited' => $this->scopeForStatus(Admin::query(), 'invited')->count(),
                'suspended' => $this->scopeForStatus(Admin::query(), 'suspended')->count(),
                'archived' => $this->scopeForStatus(Admin::query(), 'archived')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'role' => ['required', Rule::in($this->assignableRoles()->pluck('name')->all())],
        ]);

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Str::random(40), // placeholder until they set their own
            'is_active' => true,
            'invited_at' => now(),
            'invited_by_id' => Auth::guard('admin')->id(),
        ]);
        $admin->assignRole($data['role']);

        Mail::to($admin->email)->send(new StaffInvitationMail($admin, $this->activationUrl($admin)));
        $this->alertOwners('Staff invited', "{$admin->name} ({$admin->email}) was invited as {$data['role']}.");

        return redirect()->route('admin.staff.index', ['status' => 'invited'])
            ->with('status', "Invitation sent to {$admin->email}.");
    }

    public function show(Admin $admin): View
    {
        $admin->load(['roles', 'invitedBy']);
        $isInvited = $admin->invited_at !== null && $admin->last_login_at === null;

        return view('admin.staff.show', [
            'staff' => $admin,
            'roles' => $this->assignableRoles(),
            'activity' => AdminActivity::where('admin_id', $admin->id)->latest()->limit(25)->get(),
            'canManage' => $this->isManageable($admin),
            'isInvited' => $isInvited,
            // Local-only: surface the exact invite link so developers can test it
            // without opening a mail client. Production keeps emailing it.
            'localActivationUrl' => $isInvited && app()->isLocal() ? $this->activationUrl($admin) : null,
        ]);
    }

    public function resendInvite(Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);

        if ($admin->last_login_at !== null) {
            return back()->with('error', 'This person has already activated their account.');
        }

        Mail::to($admin->email)->send(new StaffInvitationMail($admin, $this->activationUrl($admin)));

        return back()->with('status', "Invitation re-sent to {$admin->email}.");
    }

    public function changeRole(Request $request, Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);

        $data = $request->validate(['role' => ['required', Rule::in($this->assignableRoles()->pluck('name')->all())]]);
        $admin->syncRoles([$data['role']]);
        $this->alertOwners('Role changed', "{$admin->name} is now a {$data['role']}.");

        return back()->with('status', "{$admin->name} is now a {$data['role']}.");
    }

    public function suspend(Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);
        $this->forceSignOut($admin->forceFill(['is_active' => false, 'suspended_at' => now()]));
        $this->alertOwners('Staff suspended', "{$admin->name}'s access was suspended.");

        return back()->with('status', "{$admin->name}'s access has been suspended.");
    }

    public function reactivate(Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);
        $admin->forceFill(['is_active' => true, 'suspended_at' => null])->save();

        return back()->with('status', "{$admin->name}'s access has been restored.");
    }

    public function replace(Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);
        $role = $admin->roles->first()?->name;
        $this->forceSignOut($admin->forceFill(['is_active' => false, 'suspended_at' => now()]));

        return redirect()->route('admin.staff.index', ['invite' => $role])
            ->with('status', "{$admin->name} was suspended. Invite their replacement below.");
    }

    public function archive(Admin $admin): RedirectResponse
    {
        $this->guardManageable($admin);
        $this->forceSignOut($admin);
        $admin->delete(); // soft delete — the auth guard can no longer resolve them
        $this->alertOwners('Staff archived', "{$admin->name} was archived and lost all access.");

        return redirect()->route('admin.staff.index')->with('status', "{$admin->name} has been archived.");
    }

    /**
     * In-app alert to the OTHER owners (never the actor) about a sensitive change.
     */
    private function alertOwners(string $title, string $message): void
    {
        $owners = Admin::role(config('rbac.super_admin_role'))
            ->where('id', '!=', Auth::guard('admin')->id())
            ->get();

        if ($owners->isNotEmpty()) {
            Notification::send($owners, new SecurityAlertNotification($title, $message));
        }
    }

    /**
     * @param  Builder<Admin>  $query
     * @return Builder<Admin>
     */
    private function scopeForStatus($query, string $status)
    {
        return match ($status) {
            'archived' => $query->onlyTrashed(),
            'invited' => $query->where('is_active', true)->whereNotNull('invited_at')->whereNull('last_login_at'),
            'suspended' => $query->where('is_active', false),
            default => $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('invited_at')->orWhereNotNull('last_login_at')),
        };
    }

    /**
     * Rotate the remember-token so persistent cookies die; the EnsureAdminIsActive
     * middleware (and soft-delete for archive) blocks the live session on the very
     * next request.
     */
    private function forceSignOut(Admin $admin): void
    {
        $admin->setRememberToken(Str::random(60));
        $admin->save();
    }

    private function guardManageable(Admin $admin): void
    {
        abort_unless($this->isManageable($admin), 403);
    }

    private function isManageable(Admin $admin): bool
    {
        return ! $admin->isSuperAdmin() && $admin->id !== Auth::guard('admin')->id();
    }

    /**
     * Assignable roles never include the owner role — staff can't be promoted to
     * Super Admin through the UI.
     *
     * @return Collection<int, Role>
     */
    private function assignableRoles(): Collection
    {
        return Role::where('guard_name', 'admin')
            ->where('name', '!=', config('rbac.super_admin_role'))
            ->orderBy('name')
            ->get();
    }

    private function activationUrl(Admin $admin): string
    {
        return URL::temporarySignedRoute('admin.staff.activate', now()->addDays(7), ['admin' => $admin->id]);
    }
}
