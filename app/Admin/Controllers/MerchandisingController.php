<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Marketing\Enums\SectionSource;
use App\Modules\Marketing\Enums\SectionStatus;
use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\BlockAnalytics;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Modules\Marketing\Services\LinkResolver;
use App\Modules\Marketing\Services\SectionValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MerchandisingController extends Controller
{
    public function index(Request $request, HomepageMerchandiser $merchandiser, BlockAnalytics $analytics, SectionValidator $validator): View
    {
        $page = Page::where('slug', $request->query('page', Page::HOME))->firstOrFail();
        $sections = $page->sections()->orderBy('sort_order')->get();

        return view('admin.merchandising.index', [
            'page' => $page,
            'sections' => $sections,
            // A live mini-preview (resolved items) per section for the canvas cards.
            'previews' => $sections->mapWithKeys(fn (HomepageSection $s) => [$s->id => $merchandiser->resolveSection($s)->items]),
            // Publish-readiness problems per section — surfaced contextually on the card.
            'problems' => $sections->mapWithKeys(fn (HomepageSection $s) => [$s->id => $validator->problems($s)]),
            // The chosen banners (ordered, all states) per banner-row, for the picker on edit.
            'bannerChoices' => $this->bannerChoices($sections),
            // Impression/click/CTR per section — closes the merchandising loop.
            'stats' => $analytics->forSections($sections->pluck('id')),
            'draftCount' => $page->sections()->where('status', SectionStatus::Draft->value)->count(),
            'previewUrl' => URL::temporarySignedRoute('storefront.preview', now()->addDays(7), ['slug' => $page->slug]),
            'types' => SectionType::cases(),
            'sources' => SectionSource::cases(),
            'categories' => Category::active()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'collections' => ProductCollection::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * The ordered, chosen banners (all states) for each banner-row section — so
     * the picker can rehydrate on edit without an N+1 query per card.
     *
     * @param  Collection<int, HomepageSection>  $sections
     * @return Collection<int, Collection<int, array<string, mixed>>>
     */
    private function bannerChoices(Collection $sections): Collection
    {
        $rows = $sections->filter(fn (HomepageSection $s) => $s->type === SectionType::BannerRow);
        $banners = Banner::whereIn('id', $rows->flatMap->bannerIds()->unique())->get()->keyBy('id');

        return $rows->mapWithKeys(fn (HomepageSection $s) => [$s->id => collect($s->bannerIds())
            ->map(fn (int $id) => $banners->get($id))
            ->filter()
            ->map(fn (Banner $b) => ['id' => $b->id, 'title' => $b->title, 'thumb' => $b->imageUrl(), 'gradient' => $b->gradient(), 'live' => $b->isLive()])
            ->values(),
        ]);
    }

    /** Publish every complete draft section for a page; skip (and report) incomplete ones. */
    public function publish(Request $request, SectionValidator $validator): RedirectResponse
    {
        $slug = $request->input('page', Page::HOME);
        $drafts = HomepageSection::forPlacement($slug)->where('status', SectionStatus::Draft->value)->get();

        $published = 0;
        $skipped = [];

        foreach ($drafts as $section) {
            if ($validator->isPublishable($section)) {
                $section->update(['status' => SectionStatus::Published->value]);
                $published++;
            } else {
                $skipped[] = $section->title ?: '(untitled '.$section->type->label().')';
            }
        }

        HomepageMerchandiser::forget($slug);

        $message = $published > 0 ? "{$published} section(s) published and now live." : 'No sections were published.';
        if ($skipped !== []) {
            $message .= ' Skipped '.count($skipped).' incomplete: '.implode(', ', $skipped).'.';
        }

        return back()->with('status', $message);
    }

    /** Persist a drag-and-drop reorder (array of section ids in display order). */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach (array_values($data['ids']) as $index => $id) {
            HomepageSection::where('id', $id)->update(['sort_order' => $index]);
        }

        HomepageMerchandiser::forget($request->input('page', Page::HOME));

        return response()->json(['ok' => true]);
    }

    public function store(Request $request, SectionValidator $validator): RedirectResponse
    {
        $section = new HomepageSection($this->validated($request));
        $this->guardPublishTransition($section, wasLive: false, validator: $validator);
        $section->save();

        return back()->with('status', 'Section created.');
    }

    public function update(Request $request, HomepageSection $section, SectionValidator $validator): RedirectResponse
    {
        $wasLive = $this->isLiveState($section);
        $section->fill($this->validated($request));
        $this->guardPublishTransition($section, $wasLive, $validator);
        $section->save();

        return back()->with('status', 'Section updated.');
    }

    /** Publicly visible = published AND active. */
    private function isLiveState(HomepageSection $section): bool
    {
        return $section->status === SectionStatus::Published && (bool) $section->is_active;
    }

    /**
     * Block only the moment a block BECOMES publicly visible while incomplete —
     * so you can freely edit an already-live block, but can't publish an empty
     * one. Problems surface as contextual, field-free errors.
     */
    private function guardPublishTransition(HomepageSection $section, bool $wasLive, SectionValidator $validator): void
    {
        if ($wasLive || ! $this->isLiveState($section)) {
            return;
        }

        $problems = $validator->problems($section);
        if ($problems !== []) {
            throw ValidationException::withMessages(['publish' => $problems]);
        }
    }

    public function destroy(HomepageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'Section removed.');
    }

    /**
     * Handle a direct image upload for editorial sections.
     * Stores to the public disk and returns the web-accessible URL.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $path = $request->file('image')->store('editorial', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_column(SectionType::cases(), 'value'))],
            'placement' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', Rule::in(array_column(SectionSource::cases(), 'value'))],
            'source_ref' => ['nullable', 'string', 'max:60'],
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([SectionStatus::Draft->value, SectionStatus::Published->value])],
            'link' => ['nullable', 'array'],
            'link.type' => ['nullable', Rule::in(LinkResolver::TYPES)],
            'link.ref' => ['nullable', 'string', 'max:2048'],
            'link.label' => ['nullable', 'string', 'max:255'],
            'item_limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            // Editorial (rich-text / image+copy) content lives in the settings bag.
            'settings' => ['nullable', 'array'],
            'settings.eyebrow' => ['nullable', 'string', 'max:80'],
            'settings.body' => ['nullable', 'string', 'max:2000'],
            'settings.image_url' => ['nullable', 'string', 'max:2048'],
            'settings.align' => ['nullable', Rule::in(['left', 'right', 'center', 'start'])],
            'settings.theme' => ['nullable', Rule::in(['default', 'accent'])],
            'settings.carousel' => ['nullable', 'in:0,1'], // banner_row: rotate in one slot
            'settings.banner_ids' => ['nullable', 'array'], // banner_row: ordered, chosen banners
            'settings.banner_ids.*' => ['integer', 'exists:banners,id'],

            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        unset($data['link']);

        // Only touch the structured link when the full form was submitted (the
        // visibility toggle omits it and must not clear it).
        if ($request->has('link')) {
            $type = $request->input('link.type');
            $data['cta_link'] = $type ? [
                'type' => $type,
                'ref' => $request->input('link.ref'),
                'label' => $request->input('link.label'),
            ] : null;
        }

        return $data;
    }
}
