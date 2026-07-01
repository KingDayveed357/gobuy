<?php

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Immutable, append-only admin audit entry. Records who did what, to what, when
 * and from where. Updates and deletes are blocked at the model layer so history
 * cannot be rewritten.
 *
 * @property string $event
 */
class AdminActivity extends Model
{
    /**
     * Append-only: there is no updated_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Admin activity log is immutable.'));
        static::deleting(fn () => throw new RuntimeException('Admin activity log is immutable.'));
    }

    /**
     * The acting admin (may be archived — show them regardless).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class)->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether this entry is an authentication event (login/logout/failed).
     */
    public function isAuthEvent(): bool
    {
        return str_starts_with($this->event, 'auth.');
    }

    /**
     * A human-readable one-liner for the activity feed (falls back to a
     * de-slugged event name for older rows).
     */
    public function summary(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return match ($this->event) {
            'auth.login' => 'Signed in',
            'auth.logout' => 'Signed out',
            'auth.failed' => 'Failed sign-in attempt',
            default => Str::of($this->event)->after('admin.')->replace(['.', '-'], ' ')->headline()->toString(),
        };
    }
}
