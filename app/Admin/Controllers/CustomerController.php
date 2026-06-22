<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = $this->filtered($request)
            ->withCount('orders')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    /**
     * Stream the (filtered) customers as a CSV, chunked for memory safety.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->withCount('orders');

        $columns = ['Name', 'Email', 'Phone', 'Type', 'Orders', 'Joined'];

        return response()->streamDownload(function () use ($query, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $query->chunk(200, function ($customers) use ($out): void {
                foreach ($customers as $customer) {
                    fputcsv($out, [
                        $customer->name,
                        $customer->email,
                        $customer->phone,
                        $customer->customer_type,
                        $customer->orders_count,
                        $customer->created_at?->toDateString(),
                    ]);
                }
            });

            fclose($out);
        }, 'customers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request): Builder
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('customer_type', $request->string('type')))
            ->latest();
    }
}
