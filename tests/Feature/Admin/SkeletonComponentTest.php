<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class SkeletonComponentTest extends TestCase
{
    public function test_text_type_renders_the_requested_number_of_shimmer_lines(): void
    {
        $view = $this->blade('<x-admin.skeleton type="text" :lines="4" />');

        $view->assertSeeInOrder(['gb-skel-lines', 'gb-skel-line'], false);
        $this->assertSame(4, substr_count((string) $view, 'gb-skel-line"'));
    }

    public function test_table_type_renders_rows_times_cols_cells(): void
    {
        $view = $this->blade('<x-admin.skeleton type="table" :rows="3" :cols="5" />');

        $view->assertSee('gb-skel-table', false);
        $this->assertSame(3, substr_count((string) $view, 'gb-skel-tr'));
        $this->assertSame(15, substr_count((string) $view, 'gb-skel-cell'));
    }

    public function test_stat_type_renders_one_tile_per_row(): void
    {
        $view = $this->blade('<x-admin.skeleton type="stat" :rows="4" />');

        $this->assertSame(4, substr_count((string) $view, 'gb-skel-stat"'));
    }

    public function test_block_type_honours_width_height_and_circle(): void
    {
        $view = $this->blade('<x-admin.skeleton width="40px" height="40px" circle />');

        $view->assertSee('gb-skel-circle', false);
        $view->assertSee('width: 40px', false);
        $view->assertSee('height: 40px', false);
    }

    public function test_it_is_hidden_from_assistive_tech(): void
    {
        $this->blade('<x-admin.skeleton type="card" />')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('gb-skel-media', false);
    }
}
