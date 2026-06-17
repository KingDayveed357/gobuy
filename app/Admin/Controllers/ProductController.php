<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
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
            ->sort('latest')
            ->paginate(20);

        return view('admin.products.index', ['products' => $products]);
    }

    public function create(): View
    {
        return view('admin.products.create', ['categoryOptions' => $this->categories->options()]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->catalog->create($request->validated());

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categoryOptions' => $this->categories->options(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->catalog->update($product, $request->validated());

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->catalog->delete($product);

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }
}
