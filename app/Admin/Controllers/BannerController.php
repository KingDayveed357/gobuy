<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Services\LinkResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::orderBy('placement')->orderBy('sort_order')->get();
        $now = now();

        $summary = [
            'live' => $banners->filter(fn (Banner $b) => $b->isLive())->count(),
            'scheduled' => $banners->filter(fn (Banner $b) => $b->is_active && $b->starts_at && $b->starts_at->gt($now))->count(),
            'draft' => $banners->filter(fn (Banner $b) => ! $b->is_active && (! $b->ends_at || $b->ends_at->gt($now)))->count(),
            'expired' => $banners->filter(fn (Banner $b) => $b->ends_at && $b->ends_at->lt($now))->count(),
        ];

        return view('admin.banners.index', [
            'banners' => $banners,
            'summary' => $summary,
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
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_variant' => ['nullable', 'in:light,dark,primary,outline'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'array'],
            'link.type' => ['nullable', Rule::in(LinkResolver::TYPES)],
            'link.ref' => ['nullable', 'string', 'max:2048'],
            'link.label' => ['nullable', 'string', 'max:255'],
            'placement' => ['required', 'in:home_hero,home_strip'],
            'layout' => ['required', 'in:hero,split,grid'],
            'theme' => ['required', 'in:indigo,sky,emerald,amber,rose,slate'],
            'text_theme' => ['required', 'in:light,dark'],
            'overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_point' => ['nullable', 'string', 'max:30'],
            // Premium controls — nullable so the quick activate/deactivate toggle
            // (which omits them) keeps the stored value; the full form always sends them.
            'height' => ['nullable', 'in:sm,md,lg'],
            'content_position' => ['nullable', 'in:start,center,end'],
            'title_size' => ['nullable', 'in:sm,md,lg'],
            'cta_size' => ['nullable', 'in:sm,md,lg'],
            'cta_radius' => ['nullable', 'in:pill,rounded,square'],
            'ribbon' => ['nullable', 'string', 'max:24'],
            'countdown_to' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['link'], $data['image'], $data['mobile_image']);

        // Only touch the structured link when the full form was submitted — the
        // quick activate/deactivate toggle omits it and must not wipe the link.
        if ($request->has('link')) {
            $data['cta_link'] = $this->buildLink($request);
        }

        return $data;
    }

    /**
     * @return array{type: string, ref: ?string, label: ?string}|null
     */
    private function buildLink(Request $request): ?array
    {
        $type = $request->input('link.type');

        return $type ? [
            'type' => $type,
            'ref' => $request->input('link.ref'),
            'label' => $request->input('link.label'),
        ] : null;
    }
}
