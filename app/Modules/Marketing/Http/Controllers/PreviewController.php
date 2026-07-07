<?php

namespace App\Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use Illuminate\Contracts\View\View;

/**
 * Renders any storefront page (home or a landing page) with DRAFT content
 * included, reached only via a signed (no-login) link generated in the admin.
 * Lets a marketer assemble and share a page for sign-off before publishing.
 */
class PreviewController extends Controller
{
    public function show(HomepageMerchandiser $merchandiser, string $slug = 'home'): View
    {
        $sections = $merchandiser->resolveForPreview($slug);

        if ($slug === Page::HOME) {
            return view('storefront.home', ['sections' => $sections, 'preview' => true]);
        }

        $page = Page::where('slug', $slug)->firstOrFail(); // drafts are previewable

        return view('storefront.page', ['page' => $page, 'sections' => $sections, 'preview' => true]);
    }
}
