<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ProductCsvImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryImportController extends Controller
{
    public function __construct(private readonly ProductCsvImporter $importer) {}

    public function create(): View
    {
        return view('admin.inventory.import', [
            'report' => null,
            'token' => null,
            'summary' => null,
        ]);
    }

    public function preview(Request $request): View
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        // Persist the upload so the confirmed import doesn't need a re-upload.
        $token = Str::uuid().'.csv';
        $request->file('file')->storeAs('imports', $token, 'local');

        $rows = $this->importer->parse(Storage::disk('local')->path("imports/{$token}"));
        $report = $this->importer->analyze($rows);

        return view('admin.inventory.import', [
            'report' => $report,
            'token' => $token,
            'summary' => $this->summarise($report),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $token = basename($request->string('token')->toString()); // guard path traversal
        $path = "imports/{$token}";

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.inventory.import.create')
                ->with('error', 'That import has expired — please upload the file again.');
        }

        $rows = $this->importer->parse(Storage::disk('local')->path($path));
        $result = $this->importer->import($rows, $request->user('admin'));

        Storage::disk('local')->delete($path);

        return redirect()->route('admin.inventory.index')->with(
            'status',
            "Import complete: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped.",
        );
    }

    /**
     * @param  list<array{action: string}>  $report
     * @return array{create: int, update: int, error: int}
     */
    private function summarise(array $report): array
    {
        return [
            'create' => count(array_filter($report, fn ($r) => $r['action'] === 'create')),
            'update' => count(array_filter($report, fn ($r) => $r['action'] === 'update')),
            'error' => count(array_filter($report, fn ($r) => $r['action'] === 'error')),
        ];
    }
}
