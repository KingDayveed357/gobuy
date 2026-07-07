<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageMerchandiser $merchandiser): View
    {
        return view('storefront.home', [
            'sections' => $merchandiser->resolveFor('home'),
        ]);
    }
}
