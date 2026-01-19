<?php
// Create placeholder icons using SVG
function createIcon($size, $filename) {
    $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="#3b82f6"/>';
    $svg .= '<text x="50%" y="50%" font-family="Arial" font-size="' . ($size * 0.4) . '" fill="white" text-anchor="middle" dy=".3em">S</text>';
    $svg .= '</svg>';
    
    $encoded = base64_encode($svg);
    $dataUri = 'data:image/svg+xml;base64,' . $encoded;
    
    // Convert to PNG using imagick if available, otherwise save as SVG
    if (extension_loaded('imagick')) {
        $image = new Imagick();
        $image->readImageBlob($svg);
        $image->setImageFormat('png');
        $image->writeImage($filename);
        echo "Created PNG: $filename\n";
    } else {
        // Save as SVG
        file_put_contents(str_replace('.png', '.svg', $filename), $svg);
        echo "Created SVG: " . str_replace('.png', '.svg', $filename) . "\n";
    }
}

// Create all required sizes
$sizes = [16, 32, 72, 96, 128, 144, 152, 180, 192, 384, 512];
foreach ($sizes as $size) {
    createIcon($size, __DIR__ . "/icons/icon-{$size}x{$size}.png");
}

// Create favicon.ico
createIcon(32, __DIR__ . "/favicon.ico");
createIcon(72, __DIR__ . "/icons/badge-72x72.png");

echo "Icon creation complete!\n";
echo "Note: Install ImageMagick extension for PNG support\n";
?>
