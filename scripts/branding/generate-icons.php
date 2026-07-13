<?php

/**
 * Generates the Quintessential Mart favicon / PWA raster icons from the vector
 * mark, drawn natively with GD (Imagick is not installed) and 4× supersampled
 * for smooth edges. Run: php scripts/branding/generate-icons.php
 */

const BLUE = [0x38, 0x74, 0xff]; // #3874ff — the live brand primary
const INK = [0x12, 0x16, 0x2e]; // #12162E
const SS = 4;                     // supersample factor

$favDir = __DIR__.'/../../public/theme/img/favicons';
$brandDir = __DIR__.'/../../public/branding/favicons';
@mkdir($favDir, 0775, true);
@mkdir($brandDir, 0775, true);

/** Allocate a colour on an image. */
function col($im, array $rgb, int $a = 0)
{
    return imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], $a);
}

/** Filled rounded rectangle. */
function roundedRect($im, float $x, float $y, float $w, float $h, float $r, $color): void
{
    imagefilledrectangle($im, (int) ($x + $r), (int) $y, (int) ($x + $w - $r), (int) ($y + $h), $color);
    imagefilledrectangle($im, (int) $x, (int) ($y + $r), (int) ($x + $w), (int) ($y + $h - $r), $color);
    $d = (int) ($r * 2);
    imagefilledellipse($im, (int) ($x + $r), (int) ($y + $r), $d, $d, $color);
    imagefilledellipse($im, (int) ($x + $w - $r), (int) ($y + $r), $d, $d, $color);
    imagefilledellipse($im, (int) ($x + $r), (int) ($y + $h - $r), $d, $d, $color);
    imagefilledellipse($im, (int) ($x + $w - $r), (int) ($y + $h - $r), $d, $d, $color);
}

/** Thick round-capped line as a brush swept along the segment. */
function brushLine($im, float $x1, float $y1, float $x2, float $y2, float $radius, $color): void
{
    $steps = (int) max(1, hypot($x2 - $x1, $y2 - $y1));
    $d = (int) ($radius * 2);
    for ($i = 0; $i <= $steps; $i++) {
        $t = $i / $steps;
        imagefilledellipse($im, (int) ($x1 + ($x2 - $x1) * $t), (int) ($y1 + ($y2 - $y1) * $t), $d, $d, $color);
    }
}

/**
 * Render one icon. $style: 'tile' (rounded blue), 'square' (full blue),
 * 'maskable' (full blue, mark shrunk into the safe zone).
 */
function renderIcon(int $size, string $style): \GdImage
{
    $C = $size * SS;
    $im = imagecreatetruecolor($C, $C);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);

    $blue = col($im, BLUE);
    $white = col($im, [255, 255, 255]);

    if ($style === 'tile') {
        roundedRect($im, 0, 0, $C, $C, $C * 0.234, $blue);
    } else {
        imagefilledrectangle($im, 0, 0, $C, $C, $blue);
    }

    // Normalised mark geometry (from favicon.svg, /64), optionally shrunk for maskable.
    $shrink = $style === 'maskable' ? 0.78 : 1.0;
    $map = fn (float $v) => (0.5 + ($v - 0.5) * $shrink) * $C;

    $cx = $map(28 / 64);
    $cy = $map(29 / 64);
    $rOuter = (14 / 64 + 7 / 128) * $C * $shrink;
    $rInner = (14 / 64 - 7 / 128) * $C * $shrink;

    imagefilledellipse($im, (int) $cx, (int) $cy, (int) ($rOuter * 2), (int) ($rOuter * 2), $white);
    imagefilledellipse($im, (int) $cx, (int) $cy, (int) ($rInner * 2), (int) ($rInner * 2), $blue);
    brushLine($im, $map(35 / 64), $map(36 / 64), $map(50 / 64), $map(51 / 64), (7 / 128) * $C * $shrink, $white);

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, $C, $C);
    imagedestroy($im);

    return $out;
}

function save(\GdImage $im, string $path): void
{
    imagepng($im, $path);
    imagedestroy($im);
    echo 'wrote '.basename($path)."\n";
}

// ── Favicons (rounded tile) ──────────────────────────────────────────────────
foreach ([16, 32, 48] as $s) {
    $im = renderIcon($s, 'tile');
    imagepng($im, "{$favDir}/favicon-{$s}x{$s}.png");
    imagepng($im, "{$brandDir}/favicon-{$s}.png");
    imagedestroy($im);
    echo "wrote favicon-{$s}\n";
}

// ── Apple touch (full square, iOS applies its own mask) ──────────────────────
save(renderIcon(180, 'square'), "{$favDir}/apple-touch-icon.png");

// ── Android / PWA "any" (rounded tile) ───────────────────────────────────────
save(renderIcon(192, 'tile'), "{$favDir}/android-chrome-192x192.png");
save(renderIcon(512, 'tile'), "{$favDir}/android-chrome-512x512.png");

// ── Maskable (full bleed, padded mark) ───────────────────────────────────────
save(renderIcon(192, 'maskable'), "{$favDir}/maskable-192x192.png");
save(renderIcon(512, 'maskable'), "{$favDir}/maskable-512x512.png");

// ── Windows tile ─────────────────────────────────────────────────────────────
save(renderIcon(150, 'square'), "{$favDir}/mstile-150x150.png");

// ── favicon.ico (16 + 32 PNG payloads) ───────────────────────────────────────
$icoPngs = [];
foreach ([16, 32] as $s) {
    ob_start();
    imagepng(renderIcon($s, 'tile'));
    $icoPngs[$s] = ob_get_clean();
}
$count = count($icoPngs);
$ico = pack('vvv', 0, 1, $count);
$offset = 6 + 16 * $count;
$dir = '';
$data = '';
foreach ($icoPngs as $s => $png) {
    $dir .= pack('CCCCvvVV', $s === 256 ? 0 : $s, $s === 256 ? 0 : $s, 0, 0, 1, 32, strlen($png), $offset);
    $offset += strlen($png);
    $data .= $png;
}
file_put_contents("{$favDir}/favicon.ico", $ico.$dir.$data);
echo "wrote favicon.ico\n";

echo "\nDone.\n";
