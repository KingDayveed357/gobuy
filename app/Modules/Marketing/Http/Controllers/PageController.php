<?php

namespace App\Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use Illuminate\Contracts\View\View;

/**
 * Renders a published landing page (/p/{slug}) from its merchandising sections —
 * the same engine as the homepage, so campaigns get premium destinations instead
 * of the generic filtered product grid.
 */
class PageController extends Controller
{
    public function show(string $slug, HomepageMerchandiser $merchandiser): View
    {
        abort_if($slug === Page::HOME, 404); // the homepage lives at "/"

        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('storefront.page', [
            'page' => $page,
            'sections' => $merchandiser->resolveFor($page->slug),
        ]);
    }
}
