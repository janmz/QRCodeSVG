<?php
/*

Composer installation (run in your project):

    composer require github.com/janmz/qrcodesvg:dev-main

This will include the dependency qrcode-generator/qrcode.php which is updated via a githup workflow to avoid distributing the complete package.

*/

require __DIR__ . '/vendor/autoload.php';

// Minimal example: generate an SVG QR code

$text = 'Hello from QRCodeSVG via Composer';

// Build a minimal QR first using the base API
$qr = QRCodeSVG::getMinimumQRCode($text, QR_ERROR_CORRECT_LEVEL_H);

// Render as SVG (width, foreground, background, alt text, variation)
$svg = $qr->getSVG(256, '#000', '#ffffff00', 'QR Code example', 'rounded');

header('Content-Type: image/svg+xml');
echo $svg;
