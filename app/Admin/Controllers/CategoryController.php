<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
            'options' => $this->categories->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateData($request);

        $category = $this->categories->create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
            ], 201);
        }

        return back()->with('status', "Category “{$category->name}” created.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateData($request, $category);

        $this->categories->update($category, $data);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Remove sub-categories before deleting this category.');
        }

        if ($category->products()->exists()) {
            return back()->with('error', 'This category still has products. Reassign them first.');
        }

        $this->categories->delete($category);

        return back()->with('status', 'Category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
                // A category cannot be its own parent.
                $category ? Rule::notIn([$category->id]) : 'nullable',
            ],
            'is_active' => ['boolean'],
        ]);
    }
}
