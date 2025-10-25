# QRCodeSVG Tests

Dieses Verzeichnis enthält alle Tests für die QRCodeSVG-Klasse.

## Dateien

- `regression_test.php` - Hauptregressionstest mit visueller PNG-Vergleich
- `test_varianten.php` - Interaktive Testseite für verschiedene QR-Code-Varianten
- `REGRESSION_TEST_README.md` - Detaillierte Dokumentation der Regressionstests
- `regression_test/` - Verzeichnis mit Baseline- und Current-Testbildern

## Ausführung

### Regressionstest

```bash
cd test
php regression_test.php
```

### Baseline-Generierung

```bash
cd test
php regression_test.php --generate-baseline
```

### Interaktive Testseite

```bash
cd test
php -S localhost:8000 test_varianten.php
```
Dann im Browser: http://localhost:8000

## Test Coverage

- **Visual regression tests** with PNG comparison (90 test cases)
- **Multiple QR code variants** (normal, rounded, dotted)
- **Color combinations** with transparency
- **Path vs Elements rendering comparison** (45 comparisons)
- **Cross-platform SVG to PNG conversion**
- **Automatic Path/Elemente validation** during baseline generation and current testing

## GitHub Actions

Die Tests werden automatisch bei jedem Push und Pull Request ausgeführt. Sie sind auch eine Vorbedingung für Releases.

### Workflow-Trigger

- Push auf `main` oder `develop` Branch
- Pull Requests gegen `main` Branch  
- Release-Erstellung

### Getestete PHP-Versionen

- PHP 8.1
- PHP 8.2  
- PHP 8.3

### System-Abhängigkeiten

- GD Extension
- ImageMagick Extension
- Inkscape (für SVG zu PNG Konvertierung)
- ImageMagick CLI Tools
- librsvg2-bin (rsvg-convert)

## Test-Ergebnisse

Bei fehlgeschlagenen Tests werden die aktuellen Testbilder als Artefakte hochgeladen, um die Unterschiede zu analysieren.
