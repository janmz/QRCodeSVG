# QRCode SVG (PHP)

PHP library to generate QR Codes as compact, stylable SVGs. It extends the MIT-licensed QRCode implementation by Kazuhiko Arase and adds an SVG renderer with multiple visual variants (normal, rounded, dotted) and color support including alpha transparency.

## Features

- Normal, rounded and dotted QR styles
- Foreground/background colors with alpha (#rrggbbaa, rgba(), etc.)
- Clean, size-optimized/compact SVG output (no rasterization)
- High error correction by default (Level H)
- Simple API and demo page

## Quick demo

Start a local PHP server and open the test page:

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000/test/test_varianten.php` to see the variants for the text "Test von QRCode SVG" in different colors.

## Installation

Using composer and run in your project folder:

```bash
    composer require github.com/janmz/qrcodesvg:dev-main
```

This will include the dependency qrcode-generator/qrcode.php which is updated via a githup workflow to avoid distributing the complete package.

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
- Core class: `qrcodesvg.php` (SVG renderer)
- Base class: `qrcode.php` is only importet and should not be changed. To avoid copying the complete repository of the base class, which also includes implementations for other languages/environments a trick is used. The current file is updated via a github workflow and included into a special folder in this repository. If You want to develop in a fork, make shure also to use the github workflow or build a small script for Your own.

Run a local server with `php -S localhost:8000` and open the demo page `https://localhost/test/test_varianten.php`.

## Testing

The project includes comprehensive regression tests to ensure visual consistency across different PHP versions and environments.

### Running Tests

```bash
cd test
php regression_test.php
```

### Test Coverage

- **Visual regression tests** with PNG comparison (90 test cases)
- **Multiple QR code variants** (normal, rounded, dotted)
- **Color combinations** with transparency
- **Path vs Elements rendering comparison** (45 comparisons)
- **Cross-platform SVG to PNG conversion**
- **Automatic Path/Elemente validation** during baseline generation and current testing

### Automated Testing

Tests run automatically on:
- Every push to `main` or `develop` branches
- Pull requests against `main`
- Release creation (as prerequisite)

See `test/README.md` for detailed testing documentation.

## Roadmap

Open features list:
- merging the qrcode with a logo in the midde (can be done via HTML/CSS right now)
- gradients in the background
- gradients in the boxes
- gradients over the foreground
  
## License

MIT. See `LICENSE`.

## Credits

- Original QR code generator: Kazuhiko Arase (<https://github.com/kazuhikoarase/qrcode-generator>)

## Support / Donate

If this project is useful to you, please consider a small donation to support children in need via CFI-Kinderhilfe:

Use the following link: <https://cfi-kinderhilfe.de/jetzt-spenden/?q=SVGVAYA>

Thank you!
