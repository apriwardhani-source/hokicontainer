<?php
/**
 * Hoki Container - Smart Image Compressor
 * Target: 100KB–300KB dari file besar (5MB–10MB)
 */

$dir = 'c:/laragon/www/hoki-container/uploads/';
if (!is_dir($dir)) die("Folder uploads tidak ditemukan!");

ini_set('memory_limit', '1024M');
set_time_limit(0);

$files = glob($dir . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);
echo "Ditemukan " . count($files) . " file...\n\n";

foreach ($files as $file) {
    $info = getimagesize($file);
    if (!$info) continue;

    $mime = $info['mime'];
    $sizeBefore = filesize($file);

    if ($sizeBefore < 300 * 1024) {
        echo "Lewati: " . basename($file) . " (Sudah kecil)\n";
        continue;
    }

    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($file); break;
        case 'image/png':  $img = imagecreatefrompng($file); break;
        case 'image/webp': $img = imagecreatefromwebp($file); break;
        default: continue 2;
    }

    if (!$img) continue;

    $w = imagesx($img);
    $h = imagesy($img);

    // Resize maksimal lebar 1600px (cukup tajam web)
    if ($w > 1600) {
        $newW = 1600;
        $newH = intval(($h / $w) * $newW);
        $tmp = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        $img = $tmp;
    }

    // Simpan sebagai WebP kualitas adaptif
    $quality = 70;
    do {
        imagewebp($img, $file, $quality);
        clearstatcache();
        $sizeAfter = filesize($file);
        $quality -= 5;
    } while ($sizeAfter > 300 * 1024 && $quality >= 40);

    imagedestroy($img);

    $saved = $sizeBefore - $sizeAfter;
    echo "OK: " . basename($file) .
         " → " . round($sizeAfter / 1024) . " KB (hemat " .
         round($saved / 1024) . " KB)\n";
}

echo "\nSEMUA FOTO SUDAH SUPER RINGAN ⚡\n";
