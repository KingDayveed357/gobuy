<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Item #4 — the drag-and-drop uploader stages images asynchronously and the
 * product form attaches them on save by token.
 */
class ProductUploaderTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_uploading_stages_an_image_and_returns_a_token(): void
    {
        Storage::fake('local');

        $response = $this->postJson(route('admin.products.media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertOk();

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        Storage::disk('local')->assertExists("tmp-uploads/{$token}");
    }

    public function test_the_upload_endpoint_rejects_non_images(): void
    {
        $this->postJson(route('admin.products.media.upload'), [
            'file' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_a_staged_upload_can_be_discarded(): void
    {
        Storage::fake('local');

        $token = $this->postJson(route('admin.products.media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ])->json('token');

        $this->deleteJson(route('admin.products.media.delete'), ['token' => $token])->assertOk();

        Storage::disk('local')->assertMissing("tmp-uploads/{$token}");
    }

    public function test_creating_a_product_with_staged_tokens_attaches_them_to_the_gallery(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $category = Category::factory()->create();

        $token = $this->postJson(route('admin.products.media.upload'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ])->json('token');

        $this->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Uploaded Product',
            'condition' => 'new',
            'sku' => 'UP-1',
            'retail_price' => 5000,
            'stock' => 10,
            'status' => 'active',
            'uploaded_tokens' => [$token],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::firstWhere('name', 'Uploaded Product');
        $this->assertSame(1, $product->getMedia(Product::MEDIA_COLLECTION)->count());

        // The staged temp file is consumed (moved into the media library).
        Storage::disk('local')->assertMissing("tmp-uploads/{$token}");
    }
}
