<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Services\ShipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $shipments = Shipment::with(['order', 'zone', 'pickupLocation'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.logistics.shipments', [
            'shipments' => $shipments,
            'status' => $status,
        ]);
    }

    public function advance(Shipment $shipment): RedirectResponse
    {
        try {
            $this->shipments->advance($shipment);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Shipment for {$shipment->order->order_number} advanced to {$shipment->status->label()}.");
    }
}
