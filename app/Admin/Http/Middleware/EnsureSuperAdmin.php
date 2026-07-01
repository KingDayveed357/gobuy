<?php

namespace App\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates routes that may NEVER be delegated — staff/role management, security &
 * system/financial settings. These capabilities belong to the platform owner
 * only and are intentionally not modelled as grantable permissions, so they
 * cannot appear as a toggle in the permission matrix.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('admin')->user()?->isSuperAdmin(), 403);

        return $next($request);
    }
}
