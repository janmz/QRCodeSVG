<?php
/*
Composer installation (run in your project):

    composer require janmz/qrcode-svg-php

This will also install the dependency vaya/qrcode-php and set up autoloading.
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


