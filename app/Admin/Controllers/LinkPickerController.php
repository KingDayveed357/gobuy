<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search-as-you-type backend for the admin Link Picker. Returns candidate
 * destinations (products/categories/brands) so marketers pick a target by name
 * instead of typing a URL.
 */
class LinkPickerController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $q = $request->string('q')->toString();

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = match ($type) {
            'product' => Product::active()->where('name', 'like', "%{$q}%")->with('category:id,name')->limit(15)->get()
                ->map(fn (Product $p) => ['value' => $p->id, 'label' => $p->name, 'sublabel' => $p->category?->name]),
            'category' => Category::active()->where('name', 'like', "%{$q}%")->limit(15)->get()
                ->map(fn (Category $c) => ['value' => $c->id, 'label' => $c->name, 'sublabel' => null]),
            'brand' => Brand::where('is_active', true)->where('name', 'like', "%{$q}%")->limit(15)->get()
                ->map(fn (Brand $b) => ['value' => $b->id, 'label' => $b->name, 'sublabel' => null]),
            default => collect(),
        };

        return response()->json($rows->values());
    }
}
