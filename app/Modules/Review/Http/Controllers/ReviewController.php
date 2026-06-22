<?php

namespace App\Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Review\Http\Requests\ReviewRequest;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function store(ReviewRequest $request, Product $product): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->reviews->canReview($user, $product)) {
            return back()->with('error', 'You can only review products from a delivered order, once each.');
        }

        $this->reviews->submit($user, $product, $request->validated());

        return back()->with('status', 'Thanks! Your review will appear once it has been approved.');
    }
}
