<?php
// textimage.php

// BE sure to install this module :
// sudo apt install php8.3-gd
// sudo systemctl restart apache2

// ---------------------
// 1. GET Parameters and Defaults
// ---------------------

$text       = $_GET['text'] ?? 'Hello World';
$fontName   = $_GET['font'] ?? 'DejaVuSans.ttf';
$fontSize   = intval($_GET['size'] ?? 24);
$textColor  = $_GET['color'] ?? 'ffffff';
$bgColor    = $_GET['bg'] ?? '000000';
$align      = strtolower($_GET['align'] ?? 'left');
$width      = intval($_GET['width'] ?? 600);
$height     = intval($_GET['height'] ?? 200);
$bold       = intval($_GET['bold'] ?? 0) === 1;
$lineWrap   = intval($_GET['line'] ?? 1) === 1;
$shadow     = intval($_GET['shadow'] ?? 0) === 1;
$antialias  = intval($_GET['antialias'] ?? 1) === 1;

// ---------------------
// 2. Locate Font
// ---------------------

$fontFile = __DIR__ . "/fonts/" . basename($fontName);
if (!file_exists($fontFile)) {
    // Try system font as fallback
    $fontFile = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf";
    if (!file_exists($fontFile)) {
        die("Font file not found.");
    }
}

// ---------------------
// 3. Create Image Canvas
// ---------------------

$image = imagecreatetruecolor($width, $height);

// Enable alpha for transparency if requested
$transparent = ($bgColor === 'transparent');
if ($transparent) {
    imagesavealpha($image, true);
    $bg = imagecolorallocatealpha($image, 0, 0, 0, 127); // Fully transparent
    imagefill($image, 0, 0, $bg);
} else {
    [$r, $g, $b] = sscanf($bgColor, "%02x%02x%02x");
    $bg = imagecolorallocate($image, $r, $g, $b);
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);
}

// ---------------------
// 4. Prepare Text Color and Lines
// ---------------------

[$r, $g, $b] = sscanf($textColor, "%02x%02x%02x");
$color = imagecolorallocate($image, $r, $g, $b);

$lines = $lineWrap ? explode("\n", $text) : [$text];

// ---------------------
// 5. Calculate Text Block Height
// ---------------------

$totalHeight = 0;
$lineMetrics = [];

foreach ($lines as $line) {
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $line);
    $lineHeight = $bbox[1] - $bbox[7];
    $lineMetrics[] = ['text' => $line, 'bbox' => $bbox, 'height' => $lineHeight];
    $totalHeight += $lineHeight;
}

$y = ($height - $totalHeight) / 2;

// ---------------------
// 6. Draw Text Line by Line
// ---------------------

foreach ($lineMetrics as $lineData) {
    $line = $lineData['text'];
    $bbox = $lineData['bbox'];
    $lineHeight = $lineData['height'];
    $textWidth = $bbox[2] - $bbox[0];

    // Determine X based on alignment
    if ($align === 'left') {
        $x = 10;
    } elseif ($align === 'right') {
        $x = $width - $textWidth - 10;
    } else { // center
        $x = ($width - $textWidth) / 2;
    }

    $y += $lineHeight;

    // Optional: draw shadow
    if ($shadow) {
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 60);
        imagettftext($image, $fontSize, 0, $x + 1, $y + 1, $shadowColor, $fontFile, $line);
    }

    // Optional: draw bold by layering
    if ($bold) {
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $line);
        imagettftext($image, $fontSize, 0, $x + 1, $y, $color, $fontFile, $line);
    } else {
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $line);
    }
}

// ---------------------
// 7. Output PNG
// ---------------------

header('Content-Type: image/png');
if ($transparent) {
    imagepng($image, null, 0, PNG_NO_FILTER);
} else {
    imagepng($image);
}
imagedestroy($image);
