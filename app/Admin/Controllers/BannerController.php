<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::orderBy('placement')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $banner = Banner::create($this->validated($request));
        $this->syncImages($request, $banner);

        return back()->with('status', 'Banner created.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->validated($request));
        $this->syncImages($request, $banner);

        return back()->with('status', 'Banner updated.');
    }

    private function syncImages(Request $request, Banner $banner): void
    {
        if ($request->hasFile('image')) {
            $banner->addMediaFromRequest('image')->toMediaCollection(Banner::MEDIA_IMAGE);
        }

        if ($request->hasFile('mobile_image')) {
            $banner->addMediaFromRequest('mobile_image')->toMediaCollection(Banner::MEDIA_MOBILE);
        }
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('status', 'Banner removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_variant' => ['nullable', 'in:light,dark,primary,outline'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'placement' => ['required', 'in:home_hero,home_strip'],
            'layout' => ['required', 'in:hero,split,grid'],
            'theme' => ['required', 'in:indigo,sky,emerald,amber,rose,slate'],
            'text_theme' => ['required', 'in:light,dark'],
            'overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_point' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
