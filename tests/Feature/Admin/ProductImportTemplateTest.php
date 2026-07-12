<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\ProductImportTemplate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Item #8 — the downloadable starter template must (a) download as CSV and
 * (b) import cleanly through the existing pipeline, proving the friendly headers
 * and the richer columns (cost, reorder level, weight, tax) all land.
 */
class ProductImportTemplateTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_the_template_downloads_as_csv_with_headers_and_sample_data(): void
    {
        $response = $this->get(route('admin.inventory.import.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        // Header row (fputcsv quotes fields containing spaces — valid CSV).
        foreach (['SKU', 'Product Name', 'Retail Price', 'Reorder Level', 'Tax Exempt'] as $header) {
            $this->assertStringContainsString($header, $body);
        }
        $this->assertStringContainsString('Indomie Instant Noodles Chicken 70g', $body);
        $this->assertStringContainsString('Star Lager Beer 60cl', $body);
    }

    public function test_the_generated_template_imports_into_a_working_catalogue(): void
    {
        $csv = ProductImportTemplate::csv();
        $file = UploadedFile::fake()->createWithContent('template.csv', $csv);

        $preview = $this->post(route('admin.inventory.import.preview'), ['file' => $file])->assertOk();
        $token = $preview->viewData('token');

        // No errors — every templated row should be classified "create".
        $this->assertSame(0, $preview->viewData('summary')['error']);

        $this->post(route('admin.inventory.import.store'), ['token' => $token])
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertSame(count(ProductImportTemplate::rows()), Product::count());
    }

    public function test_the_import_maps_friendly_headers_and_rich_columns(): void
    {
        $file = UploadedFile::fake()->createWithContent('template.csv', ProductImportTemplate::csv());
        $token = $this->post(route('admin.inventory.import.preview'), ['file' => $file])->viewData('token');
        $this->post(route('admin.inventory.import.store'), ['token' => $token]);

        // "Product Name" → name, "Initial Stock" → stock, "Reorder Level" → threshold.
        $product = Product::firstWhere('name', 'Indomie Instant Noodles Chicken 70g');
        $this->assertNotNull($product);
        $this->assertNotEmpty($product->description);
        $this->assertSame(15000, $product->cost_price_usd); // ₦150 → kobo
        $this->assertSame(70, $product->weight_g);
        $this->assertTrue($product->is_tax_exempt);

        $variant = ProductVariant::firstWhere('sku', 'NOOD-INDO-70');
        $this->assertSame(25000, $variant->retail_price->kobo); // ₦250
        $this->assertSame(1200, $variant->stock);
        $this->assertSame(120, $variant->low_stock_threshold);

        // Categories and brands are auto-created by name.
        $this->assertNotNull(Category::firstWhere('name', 'Noodles'));
        $this->assertNotNull(Brand::firstWhere('name', 'Indomie'));

        // Alcohol is not tax-exempt.
        $this->assertFalse(Product::firstWhere('name', 'Star Lager Beer 60cl')->is_tax_exempt);
    }
}
