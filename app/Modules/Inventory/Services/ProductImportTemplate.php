<?php

namespace App\Modules\Inventory\Services;

/**
 * Builds the downloadable bulk-import starter file: the exact column headers the
 * {@see ProductCsvImporter} understands, pre-filled with a realistic Nigerian
 * retail catalogue so an admin can import a working store in one upload, or edit
 * the rows to match their own stock.
 *
 * CSV (not native .xlsx) keeps this dependency-free and opens cleanly in Excel /
 * Google Sheets; the importer reads the file straight back.
 */
class ProductImportTemplate
{
    /**
     * Friendly column headers, in order. Each maps to an importer key (directly
     * or via {@see ProductCsvImporter::HEADER_ALIASES}).
     *
     * @var list<string>
     */
    public const HEADERS = [
        'SKU',
        'Product Name',
        'Brand',
        'Category',
        'Description',
        'Cost Price',
        'Retail Price',
        'Wholesale Price',
        'Reorder Level',
        'Initial Stock',
        'Weight',
        'Length',
        'Width',
        'Height',
        'Tax Exempt',
        'Status',
    ];

    /**
     * The starter catalogue. Each row is positional to {@see HEADERS}.
     *
     * Prices are Naira; cost < wholesale < retail. Weight is grams, dimensions
     * millimetres. Staple foods are VAT-exempt; alcohol/soft drinks are not.
     *
     * @return list<list<string|int>>
     */
    public static function rows(): array
    {
        // [sku, name, brand, category, description, cost, retail, wholesale, reorder, stock, weight, L, W, H, taxExempt, status]
        return [
            // ── Beer ──────────────────────────────────────────────────────────
            ['BEER-STAR-60', 'Star Lager Beer 60cl', 'Star', 'Beer', 'Nigeria’s classic lager, 60cl bottle.', 480, 700, 620, 24, 240, 640, 70, 70, 250, 'No', 'active'],
            ['BEER-GULD-60', 'Gulder Lager Beer 60cl', 'Gulder', 'Beer', 'Full-bodied premium lager, 60cl.', 500, 750, 660, 24, 180, 640, 70, 70, 250, 'No', 'active'],
            ['BEER-TROP-60', 'Trophy Lager 60cl', 'Trophy', 'Beer', 'Smooth South-West favourite lager.', 430, 650, 570, 24, 300, 640, 70, 70, 250, 'No', 'active'],
            ['BEER-GUIN-60', 'Guinness Foreign Extra Stout 60cl', 'Guinness', 'Beer', 'Iconic rich stout, 60cl.', 620, 950, 850, 12, 144, 660, 72, 72, 255, 'No', 'active'],
            ['BEER-HEIN-33', 'Heineken Lager 33cl', 'Heineken', 'Beer', 'Imported premium lager, 33cl bottle.', 640, 1000, 900, 12, 120, 360, 60, 60, 210, 'No', 'active'],
            ['BEER-LEGE-60', 'Legend Extra Stout 60cl', 'Legend', 'Beer', 'Bold Nigerian stout, 60cl.', 520, 800, 700, 24, 96, 640, 70, 70, 250, 'No', 'active'],

            // ── Wine & Spirits ────────────────────────────────────────────────
            ['WINE-4TH-75', '4th Street Sweet Red Wine 75cl', '4th Street', 'Wine', 'Easy-drinking sweet red wine.', 2200, 3200, 2900, 6, 60, 1200, 80, 80, 320, 'No', 'active'],
            ['WINE-EVA-75', 'Eva Cream Sweet Wine 75cl', 'Eva', 'Wine', 'Popular Nigerian cream wine.', 1900, 2800, 2500, 6, 72, 1200, 80, 80, 320, 'No', 'active'],
            ['SPIR-CHEL-75', 'Chelsea London Dry Gin 75cl', 'Chelsea', 'Spirits', 'Locally distilled dry gin, 75cl.', 2600, 3800, 3400, 6, 48, 1150, 85, 85, 300, 'No', 'active'],
            ['SPIR-SEAM-20', 'Seaman’s Aromatic Schnapps 20cl', 'Seaman’s', 'Spirits', 'Traditional schnapps, 20cl flask.', 900, 1400, 1250, 12, 90, 350, 55, 40, 180, 'No', 'active'],
            ['SPIR-BAIL-75', 'Baileys Irish Cream 75cl', 'Baileys', 'Spirits', 'Imported cream liqueur, 75cl.', 9500, 13500, 12500, 4, 24, 1300, 90, 90, 300, 'No', 'active'],

            // ── Malt drinks ───────────────────────────────────────────────────
            ['MALT-MALT-33', 'Maltina Malt Drink 33cl', 'Maltina', 'Malt Drinks', 'Non-alcoholic malt drink, 33cl can.', 260, 400, 350, 24, 360, 350, 60, 60, 115, 'No', 'active'],
            ['MALT-AMST-33', 'Amstel Malta 33cl', 'Amstel Malta', 'Malt Drinks', 'Premium non-alcoholic malt, 33cl.', 270, 420, 370, 24, 240, 350, 60, 60, 115, 'No', 'active'],
            ['MALT-GUIN-33', 'Malta Guinness 33cl', 'Malta Guinness', 'Malt Drinks', 'Rich malt drink, 33cl can.', 280, 430, 380, 24, 216, 350, 60, 60, 115, 'No', 'active'],

            // ── Soft drinks ───────────────────────────────────────────────────
            ['SOFT-COKE-35', 'Coca-Cola 35cl', 'Coca-Cola', 'Soft Drinks', 'Classic Coke, 35cl PET bottle.', 180, 300, 260, 48, 480, 380, 62, 62, 200, 'No', 'active'],
            ['SOFT-FANT-35', 'Fanta Orange 35cl', 'Fanta', 'Soft Drinks', 'Sparkling orange drink, 35cl.', 180, 300, 260, 48, 420, 380, 62, 62, 200, 'No', 'active'],
            ['SOFT-SPRT-35', 'Sprite Lemon-Lime 35cl', 'Sprite', 'Soft Drinks', 'Clear lemon-lime soda, 35cl.', 180, 300, 260, 48, 360, 380, 62, 62, 200, 'No', 'active'],
            ['SOFT-BIGI-35', 'Bigi Cola 35cl', 'Bigi', 'Soft Drinks', 'Affordable cola, 35cl PET.', 130, 220, 190, 48, 600, 380, 62, 62, 200, 'No', 'active'],

            // ── Bottled water ─────────────────────────────────────────────────
            ['WATR-EVA-75', 'Eva Table Water 75cl', 'Eva', 'Bottled Water', 'Purified table water, 75cl.', 120, 200, 170, 48, 720, 780, 65, 65, 240, 'Yes', 'active'],
            ['WATR-AQUA-50', 'Aquafina Table Water 50cl', 'Aquafina', 'Bottled Water', 'Purified water, 50cl bottle.', 90, 150, 130, 48, 960, 520, 60, 60, 210, 'Yes', 'active'],

            // ── Juice ─────────────────────────────────────────────────────────
            ['JUIC-CHIV-1L', 'Chivita 100% Juice Orange 1L', 'Chivita', 'Juice', '100% orange juice, 1 litre pack.', 900, 1400, 1250, 12, 180, 1050, 90, 60, 210, 'No', 'active'],
            ['JUIC-HOLL-1L', 'Hollandia Yoghurt Drink 1L', 'Hollandia', 'Juice', 'Creamy drinking yoghurt, 1 litre.', 950, 1500, 1350, 12, 150, 1050, 90, 60, 210, 'No', 'active'],
            ['JUIC-5AL-35', '5 Alive Pulpy Orange 35cl', '5 Alive', 'Juice', 'Pulpy fruit juice, 35cl.', 260, 400, 350, 24, 240, 360, 60, 60, 190, 'No', 'active'],

            // ── Biscuits & snacks ─────────────────────────────────────────────
            ['BISC-CABN-40', 'Cabin Biscuits 40g', 'Cabin', 'Biscuits', 'Crunchy cabin biscuits, 40g.', 60, 100, 85, 60, 600, 40, 90, 60, 15, 'Yes', 'active'],
            ['BISC-DIGE-250', 'McVitie’s Digestive 250g', 'McVitie’s', 'Biscuits', 'Wheat digestive biscuits, 250g.', 750, 1150, 1000, 12, 120, 250, 180, 70, 45, 'Yes', 'active'],
            ['SNAK-GALA-50', 'Gala Sausage Roll 50g', 'Gala', 'Snacks', 'On-the-go beef sausage roll.', 130, 200, 175, 60, 480, 50, 130, 40, 30, 'Yes', 'active'],
            ['SNAK-PRIN-165', 'Pringles Original 165g', 'Pringles', 'Snacks', 'Stacked potato crisps, 165g can.', 1400, 2100, 1900, 12, 96, 165, 78, 78, 250, 'No', 'active'],

            // ── Noodles ───────────────────────────────────────────────────────
            ['NOOD-INDO-70', 'Indomie Instant Noodles Chicken 70g', 'Indomie', 'Noodles', 'Instant noodles, chicken flavour, 70g.', 150, 250, 220, 120, 1200, 70, 110, 90, 30, 'Yes', 'active'],
            ['NOOD-GOLD-70', 'Golden Penny Noodles 70g', 'Golden Penny', 'Noodles', 'Instant noodles, onion chicken, 70g.', 140, 240, 210, 120, 900, 70, 110, 90, 30, 'Yes', 'active'],

            // ── Dairy ─────────────────────────────────────────────────────────
            ['DAIR-PEAK-tin', 'Peak Evaporated Milk 170g Tin', 'Peak', 'Dairy', 'Evaporated milk, 170g tin.', 320, 500, 440, 48, 480, 170, 55, 55, 90, 'Yes', 'active'],
            ['DAIR-3CRW-tin', 'Three Crowns Evaporated Milk 160g', 'Three Crowns', 'Dairy', 'Evaporated filled milk, 160g tin.', 300, 470, 410, 48, 360, 160, 54, 54, 88, 'Yes', 'active'],
            ['DAIR-PEAK-400', 'Peak Powdered Milk 400g Tin', 'Peak', 'Dairy', 'Instant full-cream milk powder, 400g.', 2400, 3500, 3200, 12, 120, 400, 100, 100, 130, 'Yes', 'active'],
        ];
    }

    /**
     * Render the template as CSV text (with a header row).
     */
    public static function csv(): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADERS);
        foreach (self::rows() as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
