<?php
// Fix editorial media image URLs that were incorrectly stored by the seeder
// Run: php artisan tinker --execute="require 'scripts/fix_editorial_urls.php';"
// Or:  php scripts/fix_editorial_urls.php (from project root)

use App\Modules\Marketing\Models\HomepageSection;

$sections = HomepageSection::where('type', 'editorial_media')->get();

foreach ($sections as $section) {
    $settings = $section->settings;
    $url = $settings['image_url'] ?? '';

    if (!$url) {
        echo "Section {$section->id} ({$section->title}): no image URL\n";
        continue;
    }

    // Detect malformed single-slash URL: http:/host/path or https:/host/path
    // This is what asset() generates when seeded without a running server
    if (preg_match('#^https?:/(?!/|$)#', $url)) {
        // Extract path portion after host (e.g. http:/127.0.0.1:8000/theme/img/... => /theme/img/...)
        $fixed = preg_replace('#^https?:/[^/]+#', '', $url);
        $settings['image_url'] = $fixed;
        $section->settings = $settings;
        $section->save();
        echo "Fixed section {$section->id} ({$section->title}): '{$url}' => '{$fixed}'\n";
    } else {
        echo "Section {$section->id} ({$section->title}): URL OK => '{$url}'\n";
    }
}

echo "\nDone.\n";
