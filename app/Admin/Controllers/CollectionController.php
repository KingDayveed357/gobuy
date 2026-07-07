<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('admin.collections.index', [
            'collections' => ProductCollection::withCount('products')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductCollection::create($this->validated($request));

        return back()->with('status', 'Collection created.');
    }

    public function update(Request $request, ProductCollection $collection): RedirectResponse
    {
        $collection->update($this->validated($request));

        return back()->with('status', 'Collection updated.');
    }

    public function destroy(ProductCollection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('admin.collections.index')->with('status', 'Collection removed.');
    }

    public function show(ProductCollection $collection): View
    {
        return view('admin.collections.show', [
            'collection' => $collection->load(['products.media']),
            // Active products not already in the collection, for the add picker.
            'available' => Product::active()
                ->whereNotIn('id', $collection->products->pluck('id'))
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name']),
        ]);
    }

    public function attach(Request $request, ProductCollection $collection): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $collection->products()->syncWithoutDetaching([
            $data['product_id'] => ['sort_order' => (int) $collection->products()->max('sort_order') + 1],
        ]);
        HomepageMerchandiser::forget();

        return back()->with('status', 'Product added to collection.');
    }

    public function detach(ProductCollection $collection, Product $product): RedirectResponse
    {
        $collection->products()->detach($product->id);
        HomepageMerchandiser::forget();

        return back()->with('status', 'Product removed.');
    }

    public function reorder(Request $request, ProductCollection $collection): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        foreach (array_values($data['product_ids']) as $index => $productId) {
            $collection->products()->updateExistingPivot($productId, ['sort_order' => $index]);
        }
        HomepageMerchandiser::forget();

        return back()->with('status', 'Order saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
