<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Item #4 — the media pipeline. Every uploaded image gets auto-generated,
 * EXIF-corrected, compressed WebP derivatives (square thumb + resized web),
 * and the model serves them with a safe fallback to the original.
 */
class ProductMediaConversionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uploading_an_image_generates_optimised_webp_conversions(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $product->addMedia($this->pngFile())->toMediaCollection(Product::MEDIA_COLLECTION);

        $media = $product->fresh()->getFirstMedia(Product::MEDIA_COLLECTION);
        $this->assertTrue($media->hasGeneratedConversion('thumb'), 'thumb conversion should be generated');
        $this->assertTrue($media->hasGeneratedConversion('web'), 'web conversion should be generated');
        $this->assertStringEndsWith('.webp', $media->getUrl('thumb'));
        $this->assertStringEndsWith('.webp', $media->getUrl('web'));
    }

    public function test_image_url_prefers_the_web_conversion(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        // No media → placeholder.
        $this->assertStringContainsString('placeholder', $product->imageUrl());

        $product->addMedia($this->pngFile())->toMediaCollection(Product::MEDIA_COLLECTION);
        $product = $product->fresh();

        $this->assertStringEndsWith('.webp', $product->imageUrl());
        $this->assertStringEndsWith('.webp', $product->thumbUrl());
    }

    public function test_image_url_falls_back_to_the_original_when_no_conversion_exists(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        // An SVG is not rasterised, so no webp conversion is generated — the
        // original must still be served rather than a broken conversion URL.
        $svg = tempnam(sys_get_temp_dir(), 'gbsvg').'.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');
        $product->addMedia($svg)->toMediaCollection(Product::MEDIA_COLLECTION);

        $this->assertStringEndsWith('.svg', $product->fresh()->imageUrl());
    }

    private function pngFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gbpng').'.png';
        $image = imagecreatetruecolor(600, 400);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
