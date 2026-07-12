<?php

namespace App\Modules\Operations\Register\Http;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Hosts the cash register / day-close screen (a Livewire component). Reached
 * only when the ops.register module is enabled — see routes-register.php.
 */
class RegisterController extends Controller
{
    public function index(): View
    {
        return view('admin.register.index');
    }
}
