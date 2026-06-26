<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LogisticsController extends Controller
{
    public function index(): View
    {
        return view('admin.logistics.index');
    }
}
