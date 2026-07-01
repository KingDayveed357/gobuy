<?php

namespace App\Admin\Models;

use App\Admin\Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Spatie resolves roles/permissions against this guard.
     */
    protected string $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'two_factor_enabled',
        'invited_at',
        'invited_by_id',
        'suspended_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * The platform owner. Backed by the single Super Admin role so authorization
     * has one source of truth (see the Gate::before bypass in AppServiceProvider).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('rbac.super_admin_role'));
    }

    /**
     * Active admins who hold a given ability — the standard recipient set for
     * admin notifications. Super Admins are included via Gate::before.
     *
     * @return Collection<int, self>
     */
    public static function withAbility(string $ability): Collection
    {
        return static::query()->where('is_active', true)->get()
            ->filter(fn (self $admin) => $admin->can($ability))
            ->values();
    }

    /**
     * Derived lifecycle status for the staff UI: archived → suspended → invited → active.
     */
    public function status(): string
    {
        return match (true) {
            $this->trashed() => 'archived',
            ! $this->is_active => 'suspended',
            $this->invited_at !== null && $this->last_login_at === null => 'invited',
            default => 'active',
        };
    }

    /**
     * The admin who invited this one (null for seeded owners).
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by_id');
    }

    public function initials(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}
