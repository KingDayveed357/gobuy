<?php

namespace App\Modules\Operations\Packaging\Http;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\Packaging\ManagePackaging;
use Illuminate\Contracts\View\View;

/**
 * Packaging units — the screen is the Livewire
 * {@see ManagePackaging} component.
 */
class PackagingController extends Controller
{
    public function index(): View
    {
        return view('admin.packaging.index');
    }
}
