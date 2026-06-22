<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\ProductQuery;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): View
    {
        $products = ProductQuery::make()
            ->search($request->string('q')->toString())
            ->inCategory($request->string('category')->toString())
            ->withStatus($request->string('status')->toString())
            ->sort('latest')
            ->paginate(20);

        $statusCounts = [
            'all' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'draft' => Product::where('status', 'draft')->count(),
            'archived' => Product::where('status', 'archived')->count(),
        ];

        return view('admin.products.index', [
            'products' => $products,
            'categoryOptions' => $this->categories->options(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categoryOptions' => $this->categories->options(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->catalog->create($request->validated());

        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categoryOptions' => $this->categories->options(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->catalog->update($product, $request->validated());

        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->catalog->delete($product);

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    /**
     * Remove any de-selected gallery images, then append newly uploaded ones.
     */
    private function syncImages(StoreProductRequest $request, Product $product): void
    {
        foreach ((array) $request->input('remove_media', []) as $mediaId) {
            $product->media()
                ->where('collection_name', Product::MEDIA_COLLECTION)
                ->whereKey($mediaId)
                ->each(fn ($media) => $media->delete());
        }

        foreach ((array) $request->file('images', []) as $image) {
            $product->addMedia($image)->toMediaCollection(Product::MEDIA_COLLECTION);
        }
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (! empty($ids)) {
            $products = Product::whereIn('id', $ids)->get();
            foreach ($products as $product) {
                $this->catalog->delete($product);
            }
        }

        return redirect()->route('admin.products.index')->with('status', 'Selected products deleted.');
    }
}
