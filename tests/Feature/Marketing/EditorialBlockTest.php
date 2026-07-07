<?php

namespace Tests\Feature\Marketing;

use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EditorialBlockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_rich_text_block_renders_its_copy_on_the_homepage(): void
    {
        HomepageSection::create([
            'type' => SectionType::RichText->value,
            'title' => 'Our Story',
            'settings' => ['eyebrow' => 'About us', 'body' => 'Built for Nigeria.', 'align' => 'center', 'theme' => 'accent'],
            'is_active' => true,
            'status' => 'published',
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('About us')
            ->assertSee('Our Story')
            ->assertSee('Built for Nigeria.')
            ->assertSee('gb-editorial--accent', false);
    }

    public function test_an_editorial_media_block_renders_its_image(): void
    {
        HomepageSection::create([
            'type' => SectionType::EditorialMedia->value,
            'title' => 'The Collection',
            'settings' => ['image_url' => 'https://cdn.example.test/story.jpg', 'align' => 'right', 'body' => 'Discover more.'],
            'is_active' => true,
            'status' => 'published',
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('The Collection')
            ->assertSee('story.jpg', false)
            ->assertSee('flex-md-row-reverse', false); // media aligned right
    }

    public function test_editorial_blocks_survive_resolution_despite_having_no_items(): void
    {
        $section = HomepageSection::create([
            'type' => SectionType::RichText->value,
            'title' => 'Standalone Copy',
            'settings' => ['body' => 'No products needed.'],
            'is_active' => true,
            'status' => 'published',
        ]);

        $resolved = app(HomepageMerchandiser::class)->resolveFor('home');

        // A content block with zero items would be rejected; an editorial one is kept.
        $this->assertTrue($resolved->contains(fn ($r) => $r->section->id === $section->id));
    }

    public function test_sections_carry_the_scroll_reveal_hook(): void
    {
        HomepageSection::create([
            'type' => SectionType::RichText->value,
            'title' => 'Reveal Me',
            'settings' => ['body' => 'x'],
            'is_active' => true,
            'status' => 'published',
        ]);

        $this->get(route('home'))->assertOk()->assertSee('gb-reveal', false);
    }
}
