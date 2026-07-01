<?php

namespace App\Admin\Http\Middleware;

use App\Admin\Services\AdminActivityLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every successful state-changing admin request (POST/PUT/PATCH/DELETE)
 * into the audit log — broad, cheap coverage of "who changed what, when, from
 * where." Read requests and auth routes (handled by RecordAdminAuthActivity) are
 * skipped, as are failed/denied responses.
 */
class RecordAdminActivity
{
    /**
     * Auth routes are logged by the auth-event subscriber instead.
     *
     * @var list<string>
     */
    private const SKIP_ROUTES = ['admin.logout', 'admin.login'];

    public function __construct(private readonly AdminActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->maybeRecord($request, $response);

        return $response;
    }

    private function maybeRecord(Request $request, Response $response): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        if ($response->getStatusCode() >= 400) {
            return; // validation errors, denials — not real state changes
        }

        $admin = Auth::guard('admin')->user();
        $routeName = $request->route()?->getName();

        if (! $admin || ! $routeName || in_array($routeName, self::SKIP_ROUTES, true)) {
            return;
        }

        $subject = $this->resolveSubject($request);
        $action = Str::headline(str_replace(['admin.', '.', '-'], ['', ' ', ' '], $routeName));
        $label = $this->subjectLabel($subject);
        $description = $label ? "{$action} · {$label}" : $action;

        $this->logger->record($routeName, $admin, $subject, $description, request: $request);
    }

    /**
     * The first Eloquent model bound to the route is the thing being acted on.
     */
    private function resolveSubject(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * A human-friendly reference for the subject (order number, name, …) so the
     * audit feed reads like a sentence rather than a route name.
     */
    private function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        foreach (['order_number', 'reference', 'name', 'title', 'code', 'email'] as $attribute) {
            if (! empty($subject->{$attribute})) {
                return (string) $subject->{$attribute};
            }
        }

        return '#'.$subject->getKey();
    }
}
