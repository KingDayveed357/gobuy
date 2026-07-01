<?php

namespace App\Admin\Services;

use App\Admin\Models\Admin;
use App\Admin\Models\AdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The single write path for the admin audit log. Keeps capture points (auth
 * listener, activity middleware, explicit sensitive-action calls) trivial and
 * consistent.
 */
class AdminActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        ?Admin $actor = null,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?Request $request = null,
    ): AdminActivity {
        $request ??= request();

        return AdminActivity::create([
            'admin_id' => $actor?->getKey(),
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255) ?: null,
        ]);
    }
}
