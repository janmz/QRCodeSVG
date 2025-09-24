# QRCode SVG (PHP)

PHP library to generate QR Codes as compact, stylable SVGs. It extends the MIT-licensed QRCode implementation by Kazuhiko Arase and adds an SVG renderer with multiple visual variants (normal, rounded, dotted) and color support including alpha transparency.

## Features

- Normal, rounded and dotted QR styles
- Foreground/background colors with alpha (#rrggbbaa, rgba(), etc.)
- Clean, compact SVG output (no rasterization)
- High error correction by default (Level H)
- Simple API and demo page

## Quick demo

Start a local PHP server and open the test page:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000/test_varianten.php` to see the variants for the text "Test von QRCode SVG" in different colors.

## Installation

Using composer and run in your project folder:

```bash
    composer require janmz/qrcode-svg-php
```

This will also install the dependency vaya/qrcode-php and set up autoloading.

In the code just use:

``` php
require __DIR__ . '/vendor/autoload.php';
```

If you use this repository directly, the provided `composer.json` registers a classmap for the two PHP files.

## Usage

```php
# class loaded via autoloader!

$qr = new QRCodeSVG();
$qr->setErrorCorrectLevel(QR_ERROR_CORRECT_LEVEL_H);
$qr->addData('Hello SVG');
$qr->setTypeNumber(4); // or compute automatically
$qr->make();

$svg = $qr->getSVG(
    256,
    '#000000ff',
    '#ffffff00',
    'QR Code for Hello SVG',
    'rounded'
);

echo $svg;
```

See `test_varianten.php` for an auto-sizing example that computes the minimal type number for the input length.

## Variants

- normal: classic square modules and rectangles
- rounded: rounded connections and corners
- dotted: all modules as dots (no rectangles)

Pass one of the above as the `variation` argument to `getSVG()`.

## Colors

Foreground (`$fg`) and background (`$bg`) accept:

- `#rgb`, `#rrggbb`, `#rrggbbaa`
- `rgb(r,g,b)`
- `rgba(r,g,b,a)`

The renderer normalizes colors and keeps them short where possible. Alpha is supported; a fully transparent background is the default.

## API

`QRCodeSVG::getSVG(int $width, string $fg="#000000FF", string $bg="#ffffff00", string $alt_text="", string $variation="normal"): string`

- width: target width/height of the SVG canvas
- fg: foreground color
- bg: background color
- alt_text: used as `aria-label`
- variation: `normal`, `rounded`, or `dotted`

Other methods come from the base `QRCode` class: `addData`, `setTypeNumber`, `setErrorCorrectLevel`, `make`, etc.

## Development

- Demo page: `test_varianten.php`
- Core classes: `qrcode.php` (base), `qrcodesvg.php` (SVG renderer)

Run a local server with `php -S localhost:8000` and open the demo page.

## License

MIT. See `LICENSE`.

## Credits

- Original QR code generator: Kazuhiko Arase (<https://github.com/kazuhikoarase/qrcode-generator>)
- SVG extensions and variations: Jan Neuhaus, VAYA Consulting

## Support / Donate

If this project is useful to you, please consider a small donation to support children in need via CFI-Kinderhilfe:

Use the following link: <https://cfi-kinderhilfe.de/jetzt-spenden/?q=SVGVAYA>

Thank you!
