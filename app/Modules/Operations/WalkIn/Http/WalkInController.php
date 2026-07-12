<?php

namespace App\Modules\Operations\WalkIn\Http;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Hosts the walk-in / in-store point-of-sale screen (a Livewire component).
 * Reached only when the ops.walk_in module is enabled — see routes-walk-in.php.
 */
class WalkInController extends Controller
{
    public function index(): View
    {
        return view('admin.walk-in.index');
    }
}
