<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ProductCsvImporter;
use App\Modules\Inventory\Services\ProductImageImporter;
use App\Modules\Inventory\Services\ProductImportTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryImportController extends Controller
{
    public function __construct(
        private readonly ProductCsvImporter $importer,
        private readonly ProductImageImporter $images,
    ) {}

    public function create(): View
    {
        return view('admin.inventory.import', [
            'report' => null,
            'token' => null,
            'summary' => null,
        ]);
    }

    /**
     * Download the starter CSV — the exact columns the importer reads, pre-filled
     * with a realistic Nigerian retail catalogue. Opens directly in Excel.
     */
    public function template(): StreamedResponse
    {
        $filename = 'gobuy-product-import-template.csv';

        return response()->streamDownload(function (): void {
            echo ProductImportTemplate::csv();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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

    // ── Bulk image import (ZIP matched to products by SKU) ──────────────────────

    public function imagesCreate(): View
    {
        return view('admin.inventory.import-images', ['report' => null, 'token' => null, 'summary' => null]);
    }

    public function imagesPreview(Request $request): View
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100 MB archive
        ]);

        $token = Str::uuid().'.zip';
        $request->file('file')->storeAs('imports', $token, 'local');

        $report = $this->images->analyze(Storage::disk('local')->path("imports/{$token}"));

        return view('admin.inventory.import-images', [
            'report' => $report,
            'token' => $token,
            'summary' => [
                'match' => count(array_filter($report, fn ($r) => $r['status'] === 'match')),
                'skip' => count(array_filter($report, fn ($r) => $r['status'] === 'skip')),
            ],
        ]);
    }

    public function imagesStore(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $token = basename($request->string('token')->toString()); // guard path traversal
        $path = "imports/{$token}";

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('admin.inventory.import.images')
                ->with('error', 'That upload has expired — please upload the archive again.');
        }

        $result = $this->images->import(Storage::disk('local')->path($path), $request->user('admin'));

        Storage::disk('local')->delete($path);

        return redirect()->route('admin.inventory.index')->with(
            'status',
            "Images attached: {$result['attached']} image(s) across {$result['products']} product(s), {$result['skipped']} skipped.",
        );
    }
}
