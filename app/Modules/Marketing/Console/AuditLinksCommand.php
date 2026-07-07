<?php

namespace App\Modules\Marketing\Console;

use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Console\Command;

/**
 * Reports banners and merchandising sections whose structured link points at a
 * target that no longer resolves (deleted/inactive product, category or brand).
 * Safe to schedule nightly.
 */
class AuditLinksCommand extends Command
{
    protected $signature = 'merchandising:audit-links';

    protected $description = 'List banners/sections whose smart-destination link is broken';

    public function handle(): int
    {
        $broken = 0;

        foreach (Banner::whereNotNull('cta_link')->get() as $banner) {
            if ($banner->hasBrokenLink()) {
                $broken++;
                $this->warn("Banner #{$banner->id} \"{$banner->title}\" → broken link");
            }
        }

        foreach (HomepageSection::whereNotNull('cta_link')->get() as $section) {
            if ($section->hasBrokenLink()) {
                $broken++;
                $this->warn("Section #{$section->id} \"{$section->title}\" → broken link");
            }
        }

        if ($broken === 0) {
            $this->info('All merchandising links resolve. ✓');
        } else {
            $this->error("{$broken} broken merchandising link(s) found.");
        }

        return self::SUCCESS;
    }
}
