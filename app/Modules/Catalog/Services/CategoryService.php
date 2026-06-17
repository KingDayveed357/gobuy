<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $data['slug'] = $this->uniqueSlug($data['name']);

        return Category::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->update($data);

        return $category;
    }

    /**
     * Flattened, depth-aware list for hierarchical <select> options.
     *
     * @return Collection<int, array{id: int, name: string, depth: int}>
     */
    public function options(): Collection
    {
        $byParent = Category::orderBy('sort_order')->orderBy('name')->get()
            ->groupBy(fn (Category $c) => $c->parent_id ?? 0);

        $out = collect();
        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $out): void {
            foreach ($byParent->get($parentId, collect()) as $category) {
                $out->push(['id' => $category->id, 'name' => $category->name, 'depth' => $depth]);
                $walk($category->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
