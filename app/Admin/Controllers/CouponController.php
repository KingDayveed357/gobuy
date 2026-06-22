<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Http\Requests\StoreCouponRequest;
use App\Modules\Pricing\Http\Requests\UpdateCouponRequest;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Services\PricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function index(Request $request): View
    {
        $coupons = Coupon::query()
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return view('admin.coupons.index', ['coupons' => $coupons]);
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $this->pricing->createCoupon($request->validated());

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->pricing->updateCoupon($coupon, $request->validated());

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->pricing->deleteCoupon($coupon);

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon deleted successfully.');
    }
}
