<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\BlockEvent;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BlockTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function section(): HomepageSection
    {
        return HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Deals', 'is_active' => true]);
    }

    public function test_the_beacon_records_impression_and_click_events(): void
    {
        $section = $this->section();

        $this->postJson(route('storefront.track-block'), [
            'events' => [
                ['id' => $section->id, 'type' => 'impression'],
                ['id' => $section->id, 'type' => 'click'],
            ],
        ])->assertNoContent();

        $this->assertDatabaseHas('block_events', ['homepage_section_id' => $section->id, 'type' => 'impression']);
        $this->assertDatabaseHas('block_events', ['homepage_section_id' => $section->id, 'type' => 'click']);
    }

    public function test_an_invalid_event_type_is_rejected(): void
    {
        $this->postJson(route('storefront.track-block'), [
            'events' => [['id' => $this->section()->id, 'type' => 'purchase']],
        ])->assertStatus(422);
    }

    public function test_events_for_unknown_sections_are_silently_dropped(): void
    {
        $section = $this->section();

        $this->postJson(route('storefront.track-block'), [
            'events' => [
                ['id' => $section->id, 'type' => 'impression'],
                ['id' => 999999, 'type' => 'impression'], // stale/tampered — ignored, not an error
            ],
        ])->assertNoContent();

        $this->assertSame(1, BlockEvent::count());
    }

    public function test_a_live_section_carries_the_tracking_attribute_but_a_preview_does_not(): void
    {
        Product::factory()->stock(5)->create(['name' => 'On The Homepage']);
        HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Trackable', 'is_active' => true, 'status' => 'published']);

        // The rendered attribute (data-track-section="…") — distinct from the JS
        // selector literals in the tracking script that also mention the name.
        $this->get('/')->assertSee('data-track-section="', false);

        // Preview mode must not pollute production metrics.
        $previewUrl = URL::temporarySignedRoute('storefront.preview', now()->addHour());
        $this->get($previewUrl)->assertDontSee('data-track-section="', false);
    }
}
