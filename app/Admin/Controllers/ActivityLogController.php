<?php

namespace App\Admin\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Models\AdminActivity;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Owner-only read view over the immutable audit log (Phase B). One screen serves
 * both "everything that happened" and "login history" (the logins filter).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->string('view')->toString(); // '' (all) or 'logins'
        $q = $request->string('q')->toString();

        $activities = AdminActivity::query()
            ->with('admin')
            ->when($request->filled('actor'), fn ($query) => $query->where('admin_id', $request->integer('actor')))
            ->when($view === 'logins', fn ($query) => $query->where('event', 'like', 'auth.%'))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('description', 'like', "%{$q}%")
                ->orWhere('event', 'like', "%{$q}%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'actors' => Admin::withTrashed()->orderBy('name')->get(['id', 'name']),
            'view' => $view,
        ]);
    }
}
