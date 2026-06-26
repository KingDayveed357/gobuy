<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        $name = trim((string) $data['name']);

        if ($this->findByName($name)) {
            throw ValidationException::withMessages([
                'name' => ['A brand with this name already exists.'],
            ]);
        }

        return Brand::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function findByName(string $name): ?Brand
    {
        return Brand::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $i = 1;

        while (Brand::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
