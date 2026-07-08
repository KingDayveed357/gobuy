<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class MerchandisingAdminTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_create_a_section(): void
    {
        $this->post(route('admin.merchandising.store'), [
            'type' => 'product_rail', 'source' => 'featured', 'title' => 'Hot Right Now',
            'item_limit' => 8, 'sort_order' => 1, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('homepage_sections', [
            'title' => 'Hot Right Now', 'type' => 'product_rail', 'source' => 'featured',
        ]);
    }

    public function test_admin_can_update_and_delete_a_section(): void
    {
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Old', 'is_active' => true]);

        $this->put(route('admin.merchandising.update', $section), [
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'New Title', 'item_limit' => 8, 'is_active' => 1,
        ])->assertRedirect();
        $this->assertSame('New Title', $section->fresh()->title);

        $this->delete(route('admin.merchandising.destroy', $section))->assertRedirect();
        $this->assertDatabaseMissing('homepage_sections', ['id' => $section->id]);
    }

    public function test_an_invalid_type_is_rejected(): void
    {
        $this->post(route('admin.merchandising.store'), ['type' => 'nonsense'])
            ->assertSessionHasErrors('type');
    }

    public function test_a_new_section_can_be_staged_as_a_draft(): void
    {
        $this->post(route('admin.merchandising.store'), [
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'My Draft', 'is_active' => 1, 'status' => 'draft',
        ])->assertRedirect();

        $this->assertSame('draft', HomepageSection::firstWhere('title', 'My Draft')->status->value);
    }

    public function test_publishing_makes_draft_sections_go_live(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Now Selling']);
        $section = HomepageSection::create([
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'Staged Rail',
            'is_active' => true, 'status' => 'draft',
        ]);

        $this->post(route('admin.merchandising.publish'))->assertRedirect();

        $this->assertSame('published', $section->fresh()->status->value);
        $this->get(route('home'))->assertOk()->assertSee('Staged Rail');
    }

    public function test_admin_can_reorder_sections_by_drag(): void
    {
        $a = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'A', 'is_active' => true, 'sort_order' => 0]);
        $b = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'B', 'is_active' => true, 'sort_order' => 1]);

        $this->postJson(route('admin.merchandising.reorder'), ['ids' => [$b->id, $a->id], 'page' => 'home'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, (int) $b->fresh()->sort_order);
        $this->assertSame(1, (int) $a->fresh()->sort_order);
    }

    public function test_the_builder_renders_the_drag_canvas_with_a_preview(): void
    {
        Product::factory()->stock(5)->create();
        HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Canvas Rail', 'is_active' => true]);

        $this->get(route('admin.merchandising.index'))->assertOk()
            ->assertSee('Canvas Rail')
            ->assertSee('id="sectionCanvas"', false)
            ->assertSee('Sortable.min.js', false);
    }

    public function test_the_visibility_toggle_does_not_republish_a_draft(): void
    {
        $section = HomepageSection::create([
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'Kept Draft',
            'is_active' => true, 'status' => 'draft',
        ]);

        // Toggling visibility submits no status — the draft must stay a draft.
        $this->put(route('admin.merchandising.update', $section), [
            'type' => 'product_grid', 'is_active' => 0,
        ])->assertRedirect();

        $this->assertSame('draft', $section->fresh()->status->value);
    }

    public function test_admin_can_create_an_editorial_block_with_copy(): void
    {
        $this->post(route('admin.merchandising.store'), [
            'type' => 'rich_text', 'title' => 'Our Promise', 'status' => 'published', 'is_active' => 1,
            'settings' => ['eyebrow' => 'Why gobuy', 'body' => 'Fast, trusted delivery.', 'align' => 'center', 'theme' => 'accent'],
        ])->assertRedirect();

        $section = HomepageSection::firstWhere('title', 'Our Promise');
        $this->assertSame('rich_text', $section->type->value);
        $this->assertSame('Fast, trusted delivery.', $section->setting('body'));
        $this->assertSame('accent', $section->setting('theme'));
    }

    public function test_an_invalid_editorial_alignment_is_rejected(): void
    {
        $this->post(route('admin.merchandising.store'), [
            'type' => 'rich_text', 'title' => 'Bad Align', 'is_active' => 1,
            'settings' => ['align' => 'diagonal'],
        ])->assertSessionHasErrors('settings.align');
    }

    public function test_an_empty_block_cannot_be_published(): void
    {
        // An editorial block with no heading and no body would render blank —
        // publishing it (published + visible) must be blocked with a reason.
        $this->post(route('admin.merchandising.store'), [
            'type' => 'rich_text', 'status' => 'published', 'is_active' => 1, 'settings' => [],
        ])->assertSessionHasErrors('publish');

        $this->assertDatabaseCount('homepage_sections', 0);
    }

    public function test_an_empty_block_can_still_be_saved_as_a_draft(): void
    {
        // Drafts are work-in-progress — incompleteness is allowed until publish.
        $this->post(route('admin.merchandising.store'), [
            'type' => 'rich_text', 'status' => 'draft', 'is_active' => 1, 'settings' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('homepage_sections', 1);
    }

    public function test_the_builder_flags_an_incomplete_section_contextually(): void
    {
        // A product rail with no source resolves to nothing — the canvas must
        // warn the admin why, before they try to publish.
        HomepageSection::create(['type' => 'product_grid', 'title' => 'No Source Rail', 'is_active' => true, 'status' => 'draft', 'placement' => 'home']);

        $this->get(route('admin.merchandising.index'))->assertOk()
            ->assertSee('Incomplete')
            ->assertSee('where the products come from');
    }

    public function test_bulk_publish_skips_incomplete_drafts(): void
    {
        $complete = HomepageSection::create(['type' => 'rich_text', 'title' => 'Ready', 'is_active' => true, 'status' => 'draft']);
        $incomplete = HomepageSection::create(['type' => 'rich_text', 'is_active' => true, 'status' => 'draft', 'settings' => []]);

        $this->post(route('admin.merchandising.publish'))->assertRedirect();

        $this->assertSame('published', $complete->fresh()->status->value);
        $this->assertSame('draft', $incomplete->fresh()->status->value); // held back — nothing to show
    }
}
