<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::withCount('sections')->orderByRaw("slug = 'home' desc")->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Page::create($this->validated($request));

        return back()->with('status', 'Page created. Add sections to build it out.');
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return back()->with('status', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->isHome(), 403, 'The homepage cannot be deleted.');

        $page->sections()->delete();
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page and its sections removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        // The homepage's slug is fixed and reserved.
        $slugRule = $page?->isHome()
            ? ['nullable']
            : ['nullable', 'alpha_dash', 'max:80', 'not_in:home,preview,p', Rule::unique('pages', 'slug')->ignore($page)];

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => $slugRule,
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['nullable', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED])],
        ]);

        // Never let the home slug be changed.
        if ($page?->isHome()) {
            unset($data['slug']);
        }

        return $data;
    }
}
