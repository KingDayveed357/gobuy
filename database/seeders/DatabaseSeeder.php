<?php

namespace Database\Seeders;

use App\Admin\Database\Seeders\AdminAccessSeeder;
use App\Models\User;
use App\Modules\Catalog\Database\Seeders\CatalogSeeder;
use App\Modules\Logistics\Database\Seeders\LogisticsSeeder;
use App\Modules\Marketing\Database\Seeders\MerchandisingDemoSeeder;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin domain: roles, permissions and the super-admin account.
        $this->call(AdminAccessSeeder::class);

        User::firstOrCreate(
            ['email' => 'retail@example.com'],
            ['name' => 'Retail User', 'password' => bcrypt('password')],
        );

        User::firstOrCreate(
            ['email' => 'wholesale@example.com'],
            ['name' => 'Wholesale User', 'password' => bcrypt('password'), 'customer_type' => User::TYPE_WHOLESALE, 'wholesale_status' => User::WHOLESALE_APPROVED],
        );

        $this->call(CatalogSeeder::class);
        $this->call(LogisticsSeeder::class);

        Banner::firstOrCreate(['title' => 'Industrial safety, delivered fast'], [
            'subtitle' => 'Helmets, boots and gloves — sourced direct, priced right.',
            'cta_label' => 'Shop safety gear', 'link_url' => '/products', 'placement' => 'home_hero',
            'layout' => 'hero', 'theme' => 'indigo', 'text_theme' => 'light', 'sort_order' => 1,
        ]);
        Banner::firstOrCreate(['title' => 'Wholesale pricing for your business'], [
            'subtitle' => 'Apply for a wholesale account and unlock tiered prices.',
            'cta_label' => 'Become a wholesaler', 'link_url' => '/account/wholesale', 'placement' => 'home_hero',
            'layout' => 'split', 'theme' => 'emerald', 'text_theme' => 'light', 'sort_order' => 2,
        ]);

        // Default homepage composition — editable by marketing in the Merchandising builder.
        $sections = [
            ['type' => 'category_grid', 'title' => 'Shop by category', 'item_limit' => 12, 'cta_label' => 'View all', 'cta_url' => '/products', 'sort_order' => 1],
            ['type' => 'banner_row', 'source_ref' => 'home_hero', 'item_limit' => 6, 'sort_order' => 2],
            ['type' => 'product_rail', 'source' => 'featured', 'title' => 'Top deals today', 'item_limit' => 8, 'cta_label' => 'Explore more', 'cta_url' => '/products', 'sort_order' => 3],
            ['type' => 'product_rail', 'source' => 'best_sellers', 'title' => 'Best sellers', 'item_limit' => 8, 'cta_label' => 'Explore more', 'cta_url' => '/products', 'sort_order' => 4],
            ['type' => 'product_grid', 'source' => 'latest', 'title' => 'New arrivals', 'item_limit' => 12, 'cta_label' => 'Explore more', 'cta_url' => '/products', 'sort_order' => 5],
        ];
        foreach ($sections as $section) {
            HomepageSection::firstOrCreate(
                ['placement' => 'home', 'sort_order' => $section['sort_order']],
                $section + ['placement' => 'home', 'is_active' => true],
            );
        }

        // Premium demo content: banners, campaigns, coupons, flash/clearance
        // sales, curated collections and editorial blocks — see M0–M7 merchandising.
        $this->call(MerchandisingDemoSeeder::class);
    }
}
