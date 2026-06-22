<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\BankTransferProof;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferReconciliationController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(): View
    {
        $proofs = BankTransferProof::with(['order', 'media'])
            ->where('status', BankTransferProof::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.payments.transfers', ['proofs' => $proofs]);
    }

    public function approve(BankTransferProof $proof): RedirectResponse
    {
        $proof->update([
            'status' => BankTransferProof::STATUS_APPROVED,
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $this->payments->confirmManualPayment($proof->order);

        return back()->with('status', "Transfer for {$proof->order->order_number} confirmed.");
    }

    public function reject(Request $request, BankTransferProof $proof): RedirectResponse
    {
        $proof->update([
            'status' => BankTransferProof::STATUS_REJECTED,
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'note' => $request->string('note')->toString() ?: null,
        ]);

        return back()->with('status', "Transfer for {$proof->order->order_number} rejected.");
    }
}
