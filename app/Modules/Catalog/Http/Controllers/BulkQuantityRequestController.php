<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\BulkQuantityRequest;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class BulkQuantityRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        BulkQuantityRequest::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'status' => BulkQuantityRequest::STATUS_NEW,
        ]);

        $product = Product::find($data['product_id']);

        Notification::send(
            Admin::withAbility('manage_customers'),
            new AdminAlertNotification(
                'Bulk quantity request',
                "{$data['name']} wants {$data['quantity']}× {$product?->name}. Follow up to close the sale.",
                'important',
                route('admin.bulk-requests.index'),
                'fa-boxes-stacked',
            ),
        );

        return back()->with('status', 'Thanks — our team will contact you shortly about your bulk order.');
    }
}
