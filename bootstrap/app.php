<?php

use App\Admin\Http\Middleware\EnsureAdminIsActive;
use App\Admin\Http\Middleware\EnsureModuleEnabled;
use App\Admin\Http\Middleware\EnsureSuperAdmin;
use App\Admin\Http\Middleware\RecordAdminActivity;
use App\Modules\Customer\Http\Middleware\EnsureEmailVerified;
use App\Modules\Inventory\Console\ReconcileInventory;
use App\Modules\Marketing\Console\AuditLinksCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AuditLinksCommand::class,
        ReconcileInventory::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'payment/webhook',
        ]);

        // Unauthenticated admins go to the admin login, not the storefront login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));

        $middleware->alias([
            'admin.active' => EnsureAdminIsActive::class,
            'admin.activity' => RecordAdminActivity::class,
            'super_admin' => EnsureSuperAdmin::class,
            'verified.otp' => EnsureEmailVerified::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'module' => EnsureModuleEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
