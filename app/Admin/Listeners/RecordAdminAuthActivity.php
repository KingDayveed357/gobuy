<?php

namespace App\Admin\Listeners;

use App\Admin\Models\Admin;
use App\Admin\Services\AdminActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Writes login history into the audit log. Scoped to the `admin` guard so it
 * never records customer (web) auth.
 */
class RecordAdminAuthActivity
{
    public function __construct(private readonly AdminActivityLogger $logger) {}

    public function onLogin(Login $event): void
    {
        if ($event->guard !== 'admin' || ! $event->user instanceof Admin) {
            return;
        }

        $this->logger->record('auth.login', $event->user, description: "{$event->user->name} signed in");
    }

    public function onLogout(Logout $event): void
    {
        if ($event->guard !== 'admin' || ! $event->user instanceof Admin) {
            return;
        }

        $this->logger->record('auth.logout', $event->user, description: "{$event->user->name} signed out");
    }

    public function onFailed(Failed $event): void
    {
        if ($event->guard !== 'admin') {
            return;
        }

        $email = $event->credentials['email'] ?? null;

        $this->logger->record('auth.failed', null, description: 'Failed sign-in attempt', properties: ['email' => $email]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            Failed::class => 'onFailed',
        ];
    }
}
