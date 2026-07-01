<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\BulkQuantityRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BulkRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $requests = BulkQuantityRequest::with(['product', 'variant'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bulk_requests.index', [
            'requests' => $requests,
            'statuses' => [
                BulkQuantityRequest::STATUS_NEW,
                BulkQuantityRequest::STATUS_CONTACTED,
                BulkQuantityRequest::STATUS_CLOSED,
            ],
        ]);
    }

    public function updateStatus(Request $request, BulkQuantityRequest $bulkQuantityRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $bulkQuantityRequest->update(['status' => $data['status']]);

        return back()->with('status', 'Request updated.');
    }
}
