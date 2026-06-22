<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\SearchTerm;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PredictiveSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_suggestions_return_matching_active_products(): void
    {
        Product::factory()->create(['name' => 'Steel Safety Helmet']);
        Product::factory()->create(['name' => 'Leather Work Gloves']);

        $this->getJson(route('search.suggestions', ['q' => 'helmet']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Steel Safety Helmet'])
            ->assertJsonMissing(['name' => 'Leather Work Gloves']);
    }

    public function test_short_queries_return_no_products(): void
    {
        Product::factory()->create(['name' => 'Helmet']);

        $this->getJson(route('search.suggestions', ['q' => 'h']))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    public function test_searching_records_a_trending_term(): void
    {
        Product::factory()->create(['name' => 'Respirator Mask']);

        $this->get(route('products.index', ['q' => 'Respirator']))->assertOk();

        $this->assertDatabaseHas('search_terms', ['term' => 'respirator', 'hits' => 1]);

        // A second search increments the counter.
        $this->get(route('products.index', ['q' => 'respirator']))->assertOk();
        $this->assertSame(2, SearchTerm::firstWhere('term', 'respirator')->hits);
    }

    public function test_trending_is_exposed_to_suggestions(): void
    {
        SearchTerm::record('boots');
        SearchTerm::record('boots');
        SearchTerm::record('gloves');

        $this->getJson(route('search.suggestions', ['q' => '']))
            ->assertOk()
            ->assertJsonFragment(['trending' => ['boots', 'gloves']]);
    }
}
