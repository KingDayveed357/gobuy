<?php

namespace App\Modules\Inventory\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\CatalogService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bulk product import from a CSV file. Each row is a simple product keyed by
 * its variant SKU: an unknown SKU creates a product (with default variant), a
 * known SKU updates that variant's pricing and stock. Stock changes made here
 * are audited through {@see InventoryService}.
 *
 * Expected header columns (case-insensitive, order-independent):
 *   sku, name, category, brand, retail_price, sale_price, wholesale_price, stock, status
 */
class ProductCsvImporter
{
    /** @var list<string> */
    public const COLUMNS = ['sku', 'name', 'category', 'brand', 'retail_price', 'sale_price', 'wholesale_price', 'stock', 'status'];

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Read the CSV into header-mapped rows.
     *
     * @return list<array{line: int, data: array<string, string>}>
     */
    public function parse(string $absolutePath): array
    {
        $rows = [];
        $handle = fopen($absolutePath, 'r');

        if ($handle === false) {
            return [];
        }

        $header = null;
        $line = 0;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(fn ($h) => Str::of($h)->trim()->lower()->snake()->value(), $cells);

                continue;
            }

            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // skip blank lines
            }

            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
            }

            $rows[] = ['line' => $line, 'data' => $data];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Validate parsed rows and classify each as create / update / error.
     *
     * @param  list<array{line: int, data: array<string, string>}>  $rows
     * @return list<array{line: int, sku: string, name: string, action: string, errors: list<string>, data: array<string, string>}>
     */
    public function analyze(array $rows): array
    {
        $report = [];
        $seenSkus = [];

        foreach ($rows as $row) {
            $data = $row['data'];
            $sku = $data['sku'] ?? '';
            $errors = [];

            if ($sku === '') {
                $errors[] = 'Missing SKU.';
            } elseif (isset($seenSkus[mb_strtolower($sku)])) {
                $errors[] = 'Duplicate SKU within this file.';
            }
            $seenSkus[mb_strtolower($sku)] = true;

            $existing = $sku !== '' ? ProductVariant::firstWhere('sku', $sku) : null;
            $action = $existing ? 'update' : 'create';

            if ($action === 'create' && ($data['name'] ?? '') === '') {
                $errors[] = 'New products need a name.';
            }

            $retail = $data['retail_price'] ?? '';
            if ($action === 'create' && $retail === '') {
                $errors[] = 'New products need a retail_price.';
            }
            if ($retail !== '' && ! is_numeric($retail)) {
                $errors[] = 'retail_price must be a number.';
            }

            if (($data['stock'] ?? '') !== '' && ! ctype_digit(ltrim($data['stock'], '+'))) {
                $errors[] = 'stock must be a whole number.';
            }

            $status = $data['status'] ?? '';
            if ($status !== '' && ! in_array($status, ['draft', 'active', 'archived'], true)) {
                $errors[] = 'status must be draft, active or archived.';
            }

            $report[] = [
                'line' => $row['line'],
                'sku' => $sku,
                'name' => $data['name'] ?? '',
                'action' => $errors === [] ? $action : 'error',
                'errors' => $errors,
                'data' => $data,
            ];
        }

        return $report;
    }

    /**
     * Commit the valid rows. Returns counts of created/updated/skipped.
     *
     * @param  list<array{line: int, data: array<string, string>}>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(array $rows, ?Admin $admin = null): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->analyze($rows) as $entry) {
            if ($entry['action'] === 'error') {
                $skipped++;

                continue;
            }

            $data = $entry['data'];

            DB::transaction(function () use ($data, $entry, $admin, &$created, &$updated): void {
                if ($entry['action'] === 'update') {
                    $this->updateVariant($data, $admin);
                    $updated++;
                } else {
                    $this->createProduct($data, $admin);
                    $created++;
                }
            });
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, string>  $data
     */
    private function createProduct(array $data, ?Admin $admin): void
    {
        $product = $this->catalog->create([
            'category_id' => $this->resolveCategoryId($data['category'] ?? ''),
            'brand_id' => $this->resolveBrandId($data['brand'] ?? ''),
            'name' => $data['name'],
            'condition' => 'new',
            'sku' => $data['sku'],
            'retail_price' => (float) $data['retail_price'],
            'sale_price' => $this->numericOrNull($data['sale_price'] ?? ''),
            'wholesale_price' => $this->numericOrNull($data['wholesale_price'] ?? ''),
            'stock' => (int) ($data['stock'] ?? 0),
            'status' => $data['status'] ?: 'active',
        ]);

        // Re-record the opening stock as an audited adjustment.
        if (($data['stock'] ?? '') !== '') {
            $this->inventory->adjust($product->primaryVariant(), 0, 'CSV import (opening stock)', $admin);
        }
    }

    /**
     * @param  array<string, string>  $data
     */
    private function updateVariant(array $data, ?Admin $admin): void
    {
        $variant = ProductVariant::firstWhere('sku', $data['sku']);

        if (! $variant) {
            return;
        }

        $attributes = [];
        if (($data['retail_price'] ?? '') !== '') {
            $attributes['retail_price'] = Money::fromNaira($data['retail_price']);
        }
        if (($data['sale_price'] ?? '') !== '') {
            $attributes['sale_price'] = Money::fromNaira($data['sale_price']);
        }
        if (($data['wholesale_price'] ?? '') !== '') {
            $attributes['wholesale_price'] = Money::fromNaira($data['wholesale_price']);
        }
        if ($attributes !== []) {
            $variant->update($attributes);
        }

        if (($data['stock'] ?? '') !== '') {
            $this->inventory->setStock($variant, (int) $data['stock'], 'CSV import', $admin);
        }
    }

    private function resolveCategoryId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true],
        )->id;
    }

    private function resolveBrandId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return Brand::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true],
        )->id;
    }

    private function numericOrNull(string $value): ?float
    {
        return $value === '' ? null : (float) $value;
    }
}
