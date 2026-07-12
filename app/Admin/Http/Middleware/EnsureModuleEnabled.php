<?php

namespace App\Admin\Http\Middleware;

use App\Support\Commerce\CommerceModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind an optional Commerce Operations module. Even if a route
 * were somehow registered while its module is off, this returns 404 — a disabled
 * module must be indistinguishable from one that was never installed.
 *
 * Usage: ->middleware('module:ops.walk_in')
 */
class EnsureModuleEnabled
{
    public function __construct(private readonly CommerceModules $modules) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($this->modules->enabled($module), 404);

        return $next($request);
    }
}
