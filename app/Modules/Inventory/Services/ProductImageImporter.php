<?php

namespace App\Modules\Inventory\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Bulk-attach product images from a ZIP archive, matched to products by the
 * variant SKU in each file's name.
 *
 * Why SKU-in-filename over the alternatives: the SKU is unique per variant, so
 * `BEER-STAR-60.jpg` matches exactly one product — no ambiguity, unlike matching
 * on product name. A single ZIP upload needs no folder-sync or manifest file.
 * Multiple images per product are supported with a numeric suffix:
 * `SKU.jpg`, `SKU-1.jpg`, `SKU_2.png` all attach to the same product.
 */
class ProductImageImporter
{
    /** @var list<string> */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const MAX_FILE_BYTES = 8 * 1024 * 1024; // 8 MB per image

    /**
     * Classify every archive entry without touching the media library.
     *
     * @return list<array{file: string, sku: string, product: ?string, status: string, reason: string}>
     */
    public function analyze(string $zipPath): array
    {
        return $this->process($zipPath, null, true)['report'];
    }

    /**
     * Attach the matched images. Returns counts.
     *
     * @return array{attached: int, skipped: int, products: int}
     */
    public function import(string $zipPath, ?Admin $admin = null): array
    {
        return $this->process($zipPath, $admin, false)['counts'];
    }

    /**
     * @return array{report: list<array<string, mixed>>, counts: array{attached: int, skipped: int, products: int}}
     */
    private function process(string $zipPath, ?Admin $admin, bool $dryRun): array
    {
        $report = [];
        $attached = 0;
        $skipped = 0;
        $touched = [];

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return ['report' => [], 'counts' => ['attached' => 0, 'skipped' => 0, 'products' => 0]];
        }

        // Pass 1: read entries, derive candidate SKUs.
        $entries = [];
        $skus = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $base = basename($name);

            // Skip directories and archive junk (__MACOSX/, .DS_Store, dotfiles).
            if ($base === '' || str_ends_with($name, '/') || str_starts_with($base, '.') || str_contains($name, '__MACOSX')) {
                continue;
            }

            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
                $report[] = ['file' => $base, 'sku' => '', 'product' => null, 'status' => 'skip', 'reason' => 'Not an image file.'];
                $skipped++;

                continue;
            }

            // Derive the SKU: filename without extension, minus a trailing -N / _N.
            $stem = pathinfo($base, PATHINFO_FILENAME);
            $sku = (string) preg_replace('/[-_]\d+$/', '', $stem);

            $entries[] = ['index' => $i, 'file' => $base, 'ext' => $ext, 'sku' => $sku, 'stem' => $stem];
            $skus[] = $sku;
            $skus[] = $stem; // also allow an exact SKU that itself ends in -N
        }

        // One query resolves every referenced SKU (no per-file N+1).
        $variants = ProductVariant::query()
            ->whereIn('sku', array_values(array_unique($skus)))
            ->with('product')
            ->get()
            ->keyBy(fn (ProductVariant $v) => mb_strtolower($v->sku));

        // Pass 2: match and (optionally) attach.
        foreach ($entries as $entry) {
            $variant = $variants->get(mb_strtolower($entry['sku'])) ?? $variants->get(mb_strtolower($entry['stem']));
            $product = $variant?->product;

            if (! $product) {
                $report[] = ['file' => $entry['file'], 'sku' => $entry['sku'], 'product' => null, 'status' => 'skip', 'reason' => "No product with SKU {$entry['sku']}."];
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                $result = $this->attach($zip, $entry, $product);
                if ($result !== true) {
                    $report[] = ['file' => $entry['file'], 'sku' => $entry['sku'], 'product' => $product->name, 'status' => 'skip', 'reason' => $result];
                    $skipped++;

                    continue;
                }
                $touched[$product->id] = true;
            } else {
                $touched[$product->id] = true;
            }

            $report[] = ['file' => $entry['file'], 'sku' => $entry['sku'], 'product' => $product->name, 'status' => 'match', 'reason' => ''];
            $attached++;
        }

        $zip->close();

        return ['report' => $report, 'counts' => ['attached' => $attached, 'skipped' => $skipped, 'products' => count($touched)]];
    }

    /**
     * Extract one entry to a temp file, validate it is a real image, and attach
     * it to the product's gallery. Returns true, or a reason string on skip.
     *
     * @param  array{index: int, file: string, ext: string, sku: string, stem: string}  $entry
     */
    private function attach(ZipArchive $zip, array $entry, Product $product): true|string
    {
        $contents = $zip->getFromIndex($entry['index'], self::MAX_FILE_BYTES + 1);
        if ($contents === false || $contents === '') {
            return 'Could not read the file.';
        }
        if (strlen($contents) > self::MAX_FILE_BYTES) {
            return 'Image is larger than 8 MB.';
        }
        // Confirm the bytes really are an image (not just a matching extension).
        if (@getimagesizefromstring($contents) === false) {
            return 'File is not a valid image.';
        }

        // Duplicate detection: skip if this product already has the identical
        // image (by content hash), so re-running an import doesn't pile up copies.
        $hash = sha1($contents);
        $alreadyAttached = $product->media()
            ->where('collection_name', Product::MEDIA_COLLECTION)
            ->get()
            ->contains(fn ($m) => $m->getCustomProperty('sha1') === $hash);
        if ($alreadyAttached) {
            return 'Duplicate — this image is already on the product.';
        }

        $safeName = Str::slug($entry['stem']).'-'.Str::random(6).'.'.$entry['ext'];
        $tmp = tempnam(sys_get_temp_dir(), 'gbimg');
        file_put_contents($tmp, $contents);

        $product->addMedia($tmp)
            ->usingFileName($safeName)
            ->usingName($entry['sku'])
            ->withCustomProperties(['sha1' => $hash])
            ->toMediaCollection(Product::MEDIA_COLLECTION);

        return true;
    }
}
