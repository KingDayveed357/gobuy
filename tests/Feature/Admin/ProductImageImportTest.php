<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ProductImageImporter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;
use ZipArchive;

/**
 * Item #9 — bulk product images from a ZIP, matched to products by SKU in the
 * filename (SKU.jpg, plus SKU-N.jpg for extra images).
 */
class ProductImageImportTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_analyze_matches_images_to_products_by_sku(): void
    {
        Product::factory()->sku('BEER-STAR-60')->create();

        $zip = $this->makeZip([
            'BEER-STAR-60.png' => $this->pngBytes(),   // matches
            'BEER-STAR-60-2.png' => $this->pngBytes(),  // extra image, same product
            'UNKNOWN-SKU.png' => $this->pngBytes(),     // no product → skip
            'readme.txt' => 'not an image',             // not an image → skip
        ]);

        $report = app(ProductImageImporter::class)->analyze($zip);
        $byFile = collect($report)->keyBy('file');

        $this->assertSame('match', $byFile['BEER-STAR-60.png']['status']);
        $this->assertSame('match', $byFile['BEER-STAR-60-2.png']['status']);
        $this->assertSame('skip', $byFile['UNKNOWN-SKU.png']['status']);
        $this->assertSame('skip', $byFile['readme.txt']['status']);
    }

    public function test_import_attaches_images_to_the_matched_product(): void
    {
        Storage::fake('public');
        $product = Product::factory()->sku('WATR-EVA-75')->create();

        // Two distinct images (different sizes → different bytes).
        $zip = $this->makeZip([
            'WATR-EVA-75.png' => $this->pngBytes(4),
            'WATR-EVA-75_2.png' => $this->pngBytes(8),
        ]);

        $counts = app(ProductImageImporter::class)->import($zip);

        $this->assertSame(2, $counts['attached']);
        $this->assertSame(1, $counts['products']);
        $this->assertSame(2, $product->fresh()->getMedia(Product::MEDIA_COLLECTION)->count());
    }

    public function test_files_that_are_not_real_images_are_skipped(): void
    {
        Storage::fake('public');
        Product::factory()->sku('FAKE-1')->create();

        // Correct extension, but the bytes are not an image.
        $zip = $this->makeZip(['FAKE-1.png' => 'this is plain text, not a PNG']);

        $counts = app(ProductImageImporter::class)->import($zip);

        $this->assertSame(0, $counts['attached']);
        $this->assertSame(1, $counts['skipped']);
    }

    public function test_duplicate_images_are_detected_and_skipped(): void
    {
        Storage::fake('public');
        $product = Product::factory()->sku('DUP-1')->create();

        // Two files with identical bytes for the same product.
        $png = $this->pngBytes();
        $zip = $this->makeZip(['DUP-1.png' => $png, 'DUP-1-2.png' => $png]);

        $counts = app(ProductImageImporter::class)->import($zip);

        $this->assertSame(1, $counts['attached']);
        $this->assertSame(1, $counts['skipped']); // the identical second image
        $this->assertSame(1, $product->fresh()->getMedia(Product::MEDIA_COLLECTION)->count());
    }

    public function test_the_http_preview_then_confirm_flow_attaches_images(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin('Super Admin');
        $product = Product::factory()->sku('SNAK-GALA-50')->create();

        $zipPath = $this->makeZip(['SNAK-GALA-50.png' => $this->pngBytes()]);
        $upload = new UploadedFile($zipPath, 'images.zip', 'application/zip', null, true);

        $preview = $this->post(route('admin.inventory.import.images.preview'), ['file' => $upload])
            ->assertOk()
            ->assertSee('to attach');

        $token = $preview->viewData('token');

        $this->post(route('admin.inventory.import.images.store'), ['token' => $token])
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertSame(1, $product->fresh()->getMedia(Product::MEDIA_COLLECTION)->count());
    }

    /**
     * Write a ZIP to a temp file and return its path.
     *
     * @param  array<string, string>  $files  filename => contents
     */
    private function makeZip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gbziptest').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }

    private function pngBytes(int $size = 4): string
    {
        $image = imagecreatetruecolor($size, $size);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
