<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brands) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $brand = $this->brands->create($data);

        return response()->json([
            'id' => $brand->id,
            'name' => $brand->name,
        ], 201);
    }
}
