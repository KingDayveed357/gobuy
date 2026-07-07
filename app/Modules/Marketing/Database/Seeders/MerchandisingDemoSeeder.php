<?php

namespace App\Modules\Marketing\Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\CampaignService;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Models\PromotionalPrice;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A single, idempotent pass that populates every merchandising surface built in
 * M0–M7 with realistic, premium demo content — banners, campaigns, coupons,
 * flash sales, curated collections, editorial storytelling and more. Safe to
 * re-run: every record is keyed on a natural identity (name/slug/code), so it
 * enriches rather than duplicates.
 *
 * Deliberately does NOT fabricate cross-sell/upsell/frequently-bought-together/
 * bundle/personalized-recommendation data — those have no backing feature yet.
 */
class MerchandisingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->categories();
        $brands = $this->brands();
        $products = $this->heroProducts($categories, $brands);

        $collections = $this->collections($products);
        $this->homeStripBanners($categories);
        $this->homepageSections($collections);
        $this->standaloneCoupons();
        $this->onSaleEvergreen($products);
        $this->campaigns($products, $collections);
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $names = ['Groceries', 'Electronics', 'Home & Kitchen', 'Fashion'];

        return collect($names)->mapWithKeys(function (string $name) {
            $slug = Str::slug($name);

            return [$slug => Category::firstOrCreate(['slug' => $slug], ['name' => $name])];
        })->all();
    }

    /**
     * @return array<string, Brand>
     */
    private function brands(): array
    {
        $names = ['GoBuy Basics', 'SafeGuard Pro', 'PowerMax', 'UrbanLiving'];

        return collect($names)->mapWithKeys(function (string $name) {
            $slug = Str::slug($name);

            return [$slug => Brand::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true])];
        })->all();
    }

    /**
     * A handful of named, photographed "hero" products spanning every category
     * — these anchor the flash sale, clearance, collections and editorial
     * blocks below so the storefront tells one coherent story.
     *
     * @param  array<string, Category>  $categories
     * @param  array<string, Brand>  $brands
     * @return array<string, Product>
     */
    private function heroProducts(array $categories, array $brands): array
    {
        $specs = [
            'headphones' => ['gobuy Studio Monitor Headphones', $categories['electronics'], $brands['powermax'], 65000, 54000, '3.png'],
            'led_strip' => ['gobuy Smart LED Strip Lights 5m', $categories['electronics'], $brands['powermax'], 18000, 15000, '16.png'],
            'air_fryer' => ['gobuy Air Fryer XL 8L', $categories['home-kitchen'], $brands['urbanliving'], 95000, 82000, '8.png'],
            'cookware' => ['gobuy Non-Stick Cookware Set (10pc)', $categories['home-kitchen'], $brands['urbanliving'], 72000, 61000, '21.png'],
            'tote_bag' => ['gobuy Designer Tote Bag', $categories['fashion'], $brands['gobuy-basics'], 32000, 27000, '12.png'],
            'sneakers' => ['gobuy Running Sneakers Elite', $categories['fashion'], $brands['gobuy-basics'], 48000, 40000, '19.png'],
        ];

        return collect($specs)->mapWithKeys(function (array $spec, string $key) {
            [$name, $category, $brand, $retail, $wholesale, $image] = $spec;

            $product = Product::firstWhere('name', $name) ?? Product::factory()
                ->for($category)
                ->for($brand, 'brand')
                ->featured()
                ->priced($retail, $wholesale)
                ->stock(fake()->numberBetween(30, 150))
                ->create(['name' => $name]);

            $this->attachImage($product, $image);

            return [$key => $product];
        })->all();
    }

    private function attachImage(Product $product, string $file): void
    {
        $path = public_path("theme/img/products/{$file}");

        if (is_file($path) && $product->getMedia(Product::MEDIA_COLLECTION)->isEmpty()) {
            $product->addMedia($path)
                ->preservingOriginal()
                ->usingFileName(Str::uuid().'.png')
                ->toMediaCollection(Product::MEDIA_COLLECTION);
        }
    }

    /**
     * Featured collections — hand-curated rails. "Trending Now" is merchandiser
     * curation, not a real analytics feature (there's no trending algorithm yet).
     *
     * @param  array<string, Product>  $products
     * @return array<string, ProductCollection>
     */
    private function collections(array $products): array
    {
        $editors = ProductCollection::firstOrCreate(
            ['slug' => 'editors-picks'],
            ['name' => "Editor's Picks", 'description' => 'Hand-picked favourites from our merchandising team.', 'is_active' => true],
        );
        $this->populateCollection($editors, [
            $products['headphones'], $products['tote_bag'], $products['air_fryer'], $products['sneakers'],
        ]);

        $trending = ProductCollection::firstOrCreate(
            ['slug' => 'trending-now'],
            ['name' => 'Trending Now', 'description' => 'What shoppers are adding to cart this week.', 'is_active' => true],
        );
        $this->populateCollection($trending, [
            $products['led_strip'], $products['sneakers'], $products['air_fryer'], $products['headphones'],
        ]);

        $clearance = ProductCollection::firstOrCreate(
            ['slug' => 'clearance-picks'],
            ['name' => 'Clearance Picks', 'description' => 'Deep-discounted stock clearing out.', 'is_active' => true],
        );
        $this->populateCollection($clearance, [$products['cookware'], $products['sneakers']]);

        return ['editors-picks' => $editors, 'trending-now' => $trending, 'clearance-picks' => $clearance];
    }

    /**
     * @param  list<Product>  $products
     */
    private function populateCollection(ProductCollection $collection, array $products): void
    {
        if ($collection->products()->exists()) {
            return; // already populated on a previous run
        }

        $collection->products()->attach(
            collect($products)->mapWithKeys(fn (Product $p, int $i) => [$p->id => ['sort_order' => $i]])->all(),
        );
    }

    /**
     * "Marketing cards" — a 3-up banner grid, each one a category doorway.
     *
     * @param  array<string, Category>  $categories
     */
    private function homeStripBanners(array $categories): void
    {
        $cards = [
            ['title' => 'Electronics Edit', 'subtitle' => 'Power, sound & smart home', 'theme' => 'sky', 'category' => $categories['electronics']],
            ['title' => 'Home & Kitchen Refresh', 'subtitle' => 'Upgrade the everyday', 'theme' => 'emerald', 'category' => $categories['home-kitchen']],
            ['title' => 'Fashion Drop', 'subtitle' => 'New season, new look', 'theme' => 'rose', 'category' => $categories['fashion']],
        ];

        foreach ($cards as $i => $card) {
            Banner::firstOrCreate(['title' => $card['title'], 'placement' => 'home_strip'], [
                'subtitle' => $card['subtitle'],
                'cta_label' => 'Shop now',
                'cta_link' => ['type' => 'category', 'ref' => $card['category']->id, 'label' => 'Shop now'],
                'placement' => 'home_strip',
                'layout' => 'grid',
                'theme' => $card['theme'],
                'text_theme' => 'light',
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    /**
     * Layers rich homepage sections on top of the baseline composition already
     * seeded by DatabaseSeeder (category grid, hero banners, top deals, best
     * sellers, new arrivals at sort_order 1–5) — continuing from sort_order 6.
     *
     * @param  array<string, ProductCollection>  $collections
     */
    private function homepageSections(array $collections): void
    {
        $electronics = Category::where('slug', 'electronics')->first();

        $sections = [
            6 => [
                'type' => 'countdown_deal', 'source' => 'best_sellers',
                'title' => 'Flash Sale — Ends Soon', 'subtitle' => "Prices this good don't last",
                'item_limit' => 8, 'cta_label' => 'Shop the sale',
                'ends_at' => now()->addHours(46),
            ],
            7 => [
                'type' => 'banner_row', 'source_ref' => 'home_strip',
                'title' => 'Shop by the moment', 'item_limit' => 3,
            ],
            8 => [
                'type' => 'product_rail', 'source' => 'manual', 'source_ref' => (string) $collections['editors-picks']->id,
                'title' => "Editor's Picks", 'subtitle' => 'Curated by our merchandising team',
                'item_limit' => 8, 'cta_label' => 'See all picks',
            ],
            9 => [
                'type' => 'product_rail', 'source' => 'manual', 'source_ref' => (string) $collections['trending-now']->id,
                'title' => 'Trending Now', 'item_limit' => 8, 'cta_label' => 'See what\'s hot',
            ],
            10 => [
                'type' => 'product_grid', 'source' => 'on_sale',
                'title' => 'On Sale Right Now', 'item_limit' => 12, 'cta_label' => 'Shop all deals',
            ],
            11 => [
                'type' => 'brand_rail',
                'title' => 'Shop by Brand', 'item_limit' => 12,
            ],
            12 => [
                'type' => 'rich_text',
                'title' => 'Built for how Nigeria shops',
                'settings' => [
                    'eyebrow' => 'Why gobuy',
                    'body' => "Same-day dispatch in Lagos, Abuja & Port Harcourt.\nSecure payments via Paystack, or pay on delivery.\nWholesale pricing for registered businesses.",
                    'align' => 'center', 'theme' => 'accent',
                ],
                'cta_label' => 'Explore gobuy', 'cta_link' => ['type' => 'products', 'label' => 'Explore gobuy'],
            ],
            13 => [
                'type' => 'editorial_media',
                'title' => 'Home Office, Upgraded',
                'settings' => [
                    'eyebrow' => 'Lifestyle',
                    'body' => 'A tidy desk, better light, and power that keeps up with your day — everything you need to make working from home feel intentional.',
                    // Root-relative path — do NOT use asset() here; it bakes
                    // in the server URL at seed-time (e.g. http:/127.0.0.1:8000)
                    // which then breaks on any other host or port.
                    'image_url' => '/theme/img/products/27.png', 'align' => 'right',
                ],
                'cta_label' => 'Shop electronics',
                'cta_link' => $electronics ? ['type' => 'category', 'ref' => $electronics->id, 'label' => 'Shop electronics'] : null,
            ],
        ];

        foreach ($sections as $sortOrder => $section) {
            HomepageSection::firstOrCreate(
                ['placement' => 'home', 'sort_order' => $sortOrder],
                $section + ['placement' => 'home', 'is_active' => true, 'status' => 'published'],
            );
        }
    }

    /** General-purpose coupons that aren't tied to any single campaign. */
    private function standaloneCoupons(): void
    {
        Coupon::firstOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percentage', 'value' => 10, 'is_active' => true,
            'eligibility' => 'both', 'usage_limit_per_user' => 1,
        ]);

        Coupon::firstOrCreate(['code' => 'BULK5K'], [
            'type' => 'fixed', 'value' => 5000, 'is_active' => true,
            'eligibility' => 'wholesale', 'min_cart_value' => 100000,
        ]);
    }

    /**
     * One evergreen discount so "On Sale" isn't only populated by time-boxed
     * campaigns — real storefronts always have a handful of standing deals.
     *
     * @param  array<string, Product>  $products
     */
    private function onSaleEvergreen(array $products): void
    {
        $variant = $products['air_fryer']->primaryVariant();

        if ($variant) {
            PromotionalPrice::firstOrCreate(
                ['product_variant_id' => $variant->id, 'label' => 'Member Price'],
                ['price' => Money::fromNaira(85000), 'is_active' => true],
            );
        }
    }

    /**
     * Three campaigns, one mechanism (M5): each groups a landing page, a
     * homepage-visible member, a coupon and promotional pricing under a single
     * schedule and launch switch. Together they demonstrate promotional
     * campaigns, flash sales, clearance sales and a scheduled holiday campaign.
     *
     * @param  array<string, Product>  $products
     * @param  array<string, ProductCollection>  $collections
     */
    private function campaigns(array $products, array $collections): void
    {
        $service = new CampaignService;

        $this->flashSaleCampaign($service, $products);
        $this->clearanceCampaign($service, $collections);
        $this->festiveSeasonCampaign($service, $products);
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function flashSaleCampaign(CampaignService $service, array $products): void
    {
        $campaign = Campaign::firstOrCreate(['slug' => 'weekend-flash-sale'], [
            'name' => 'Weekend Flash Sale', 'status' => Campaign::STATUS_DRAFT,
            'badge_text' => 'FLASH SALE', 'accent_color' => '#e63757',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(46),
        ]);

        if (! $campaign->wasRecentlyCreated) {
            return; // already built + launched on a previous run
        }

        $page = Page::firstOrCreate(['slug' => 'weekend-flash-sale'], [
            'title' => 'Weekend Flash Sale', 'status' => Page::STATUS_DRAFT,
        ]);
        $campaign->update(['page_id' => $page->id]);

        HomepageSection::create([
            'placement' => $page->slug, 'campaign_id' => $campaign->id,
            'type' => 'countdown_deal', 'source' => 'best_sellers',
            'title' => 'Flash Sale — Today Only', 'item_limit' => 8,
            'is_active' => true, 'status' => 'draft', 'sort_order' => 0,
        ]);

        // Coordinated across the app — this banner rides among the homepage
        // hero banners once launched, exactly the R1 fix (one switch, not four).
        Banner::create([
            'title' => 'Weekend Flash Sale — up to 25% off', 'placement' => 'home_hero',
            'subtitle' => 'This weekend only. Ends Sunday midnight.',
            'cta_label' => 'Shop the sale', 'cta_link' => ['type' => 'url', 'ref' => $page->url(), 'label' => 'Shop the sale'],
            'theme' => 'rose', 'text_theme' => 'light', 'ribbon' => 'FLASH SALE',
            'campaign_id' => $campaign->id, 'is_active' => false, 'sort_order' => 0,
        ]);

        Coupon::create([
            'code' => 'FLASH15', 'type' => 'percentage', 'value' => 15,
            'eligibility' => 'both', 'campaign_id' => $campaign->id, 'is_active' => false,
        ]);

        foreach (['headphones', 'led_strip'] as $key) {
            $variant = $products[$key]->primaryVariant();
            if ($variant) {
                PromotionalPrice::create([
                    'product_variant_id' => $variant->id, 'label' => 'Flash Sale Price',
                    'price' => $variant->retail_price->percentage(75), 'campaign_id' => $campaign->id, 'is_active' => false,
                ]);
            }
        }

        $service->launch($campaign->fresh());
    }

    /**
     * @param  array<string, ProductCollection>  $collections
     */
    private function clearanceCampaign(CampaignService $service, array $collections): void
    {
        $campaign = Campaign::firstOrCreate(['slug' => 'clearance-corner'], [
            'name' => 'Clearance Corner', 'status' => Campaign::STATUS_DRAFT,
            'badge_text' => 'CLEARANCE', 'accent_color' => '#7c3aed',
        ]);

        if (! $campaign->wasRecentlyCreated) {
            return;
        }

        $page = Page::firstOrCreate(['slug' => 'clearance-corner'], [
            'title' => 'Clearance Corner', 'status' => Page::STATUS_DRAFT,
        ]);
        $campaign->update(['page_id' => $page->id]);

        HomepageSection::create([
            'placement' => $page->slug, 'campaign_id' => $campaign->id,
            'type' => 'product_grid', 'source' => 'manual', 'source_ref' => (string) $collections['clearance-picks']->id,
            'title' => 'Clearance Picks', 'subtitle' => 'While stocks last', 'item_limit' => 12,
            'is_active' => true, 'status' => 'draft', 'sort_order' => 0,
        ]);

        Banner::create([
            'title' => 'Clearance Corner — up to 60% off', 'placement' => 'home_strip',
            'subtitle' => 'Deep discounts, while stocks last.',
            'cta_label' => 'Shop clearance', 'cta_link' => ['type' => 'url', 'ref' => $page->url(), 'label' => 'Shop clearance'],
            'theme' => 'amber', 'text_theme' => 'light', 'ribbon' => 'UP TO 60% OFF', 'layout' => 'grid',
            'campaign_id' => $campaign->id, 'is_active' => false, 'sort_order' => 4,
        ]);

        Coupon::create([
            'code' => 'CLEAR20', 'type' => 'percentage', 'value' => 20,
            'eligibility' => 'both', 'campaign_id' => $campaign->id, 'is_active' => false,
        ]);

        foreach ($collections['clearance-picks']->products as $product) {
            $variant = $product->primaryVariant();
            if ($variant) {
                PromotionalPrice::create([
                    'product_variant_id' => $variant->id, 'label' => 'Clearance Price',
                    'price' => $variant->retail_price->percentage(40), 'campaign_id' => $campaign->id, 'is_active' => false,
                ]);
            }
        }

        $service->launch($campaign->fresh());
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function festiveSeasonCampaign(CampaignService $service, array $products): void
    {
        $campaign = Campaign::firstOrCreate(['slug' => 'festive-season'], [
            'name' => 'Festive Season', 'status' => Campaign::STATUS_DRAFT,
            'badge_text' => 'FESTIVE SEASON', 'accent_color' => '#f5803e',
            'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(21),
        ]);

        if (! $campaign->wasRecentlyCreated) {
            return;
        }

        $page = Page::firstOrCreate(['slug' => 'festive-season'], [
            'title' => 'Festive Season', 'status' => Page::STATUS_DRAFT,
        ]);
        $campaign->update(['page_id' => $page->id]);

        HomepageSection::create([
            'placement' => $page->slug, 'campaign_id' => $campaign->id,
            'type' => 'rich_text', 'title' => 'The Season of Giving',
            'settings' => ['eyebrow' => 'Festive Season', 'body' => 'Gifts, décor and family favourites — arriving soon.', 'align' => 'center', 'theme' => 'accent'],
            'is_active' => true, 'status' => 'draft', 'sort_order' => 0,
        ]);
        HomepageSection::create([
            'placement' => $page->slug, 'campaign_id' => $campaign->id,
            'type' => 'product_rail', 'source' => 'featured',
            'title' => 'Festive Favourites', 'item_limit' => 8,
            'is_active' => true, 'status' => 'draft', 'sort_order' => 1,
        ]);

        Banner::create([
            'title' => 'Festive Season is coming', 'placement' => 'home_hero',
            'subtitle' => 'Gifts, décor and family favourites.',
            'cta_label' => 'Get notified', 'cta_link' => ['type' => 'url', 'ref' => $page->url(), 'label' => 'Get notified'],
            'theme' => 'amber', 'text_theme' => 'light', 'ribbon' => 'COMING SOON',
            'campaign_id' => $campaign->id, 'is_active' => false, 'sort_order' => 3,
        ]);

        Coupon::create([
            'code' => 'FESTIVE10', 'type' => 'percentage', 'value' => 10,
            'eligibility' => 'both', 'campaign_id' => $campaign->id, 'is_active' => false,
        ]);

        $variant = $products['tote_bag']->primaryVariant();
        if ($variant) {
            PromotionalPrice::create([
                'product_variant_id' => $variant->id, 'label' => 'Festive Price',
                'price' => $variant->retail_price->percentage(85), 'campaign_id' => $campaign->id, 'is_active' => false,
            ]);
        }

        // starts_at is in the future — launch() correctly marks this SCHEDULED,
        // not live: members carry the schedule but stay hidden until it starts.
        $service->launch($campaign->fresh());
    }
}
