<?php
/**
 * Hoki Container - Core Asset Optimizer
 * Mengompres logo dan favicon yang kegedean (360KB -> 20KB)
 */

$files = [
    'c:/laragon/www/hoki-container/assets/img/logo.png',
    'c:/laragon/www/hoki-container/assets/img/favicon.png'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $sizeBefore = filesize($file);
    $img = imagecreatefrompng($file);
    
    if ($img) {
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        
        // Simpan dengan kompresi tinggi (9)
        imagepng($img, $file, 9);
        imagedestroy($img);
        
        $sizeAfter = filesize($file);
        $saved = round(($sizeBefore - $sizeAfter) / 1024, 2);
        echo "BERHASIL: " . basename($file) . " (Hemat " . $saved . " KB)\n";
    }
}
echo "\nCORE ASSETS SUDAH RINGAN! ⚡";
?>
