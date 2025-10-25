# QRCodeSVG Regression Tests

Dieses Verzeichnis enthält Regression-Tests für die QRCodeSVG-Klasse, um sicherzustellen, dass Änderungen keine unerwarteten visuellen oder strukturellen Unterschiede verursachen.

## Verfügbare Tests

### 1. Vollständiger Regression-Test (`regression_test.php`)

Dieser Test konvertiert SVGs zu PNGs und vergleicht diese pixelgenau. Erfordert externe Tools wie ImageMagick, Inkscape oder rsvg-convert.

**Verwendung:**

``` bash
# Baseline-Bilder generieren (beim ersten Mal oder nach bewussten Änderungen)
php regression_test.php --generate-baseline

# Regression-Test ausführen
php regression_test.php
```

### 2. Einfacher Regression-Test (`regression_test_simple.php`)

Dieser Test vergleicht SVG-Dateien direkt ohne externe Tools. Schneller und einfacher zu verwenden.

**Verwendung:**

```bash
# Baseline-SVGs generieren
php regression_test_simple.php --generate-baseline

# Regression-Test ausführen
php regression_test_simple.php
```

## Getestete Varianten

Die Tests decken folgende Szenarien ab:

### Grundfunktionalität

- Normale schwarze QR-Codes auf weißem Hintergrund
- Invertierte Farben (weiß auf schwarz)
- Verschiedene Größen (100px, 200px, 400px)

### Variationen

- `normal`: Quadratische Blöcke
- `rounded`: Abgerundete Ecken
- `dotted`: Kreisförmige Punkte

### Transparente Vordergründe

- Transparenter Vordergrund mit farbigem Hintergrund
- Automatische Farbinversion
- Rounded Border bei Path-Rendering

### Path-Rendering

- Alle Variationen mit Path-Rendering
- Konsolidierung in einzelne `<path>`-Elemente
- Custom Border-Einstellungen

### Farbkombinationen

- Verschiedene Farbkombinationen
- RGBA-Farben mit Transparenz

## Test-Ergebnisse

### Erfolgreiche Tests

``` pwsh
✓ normal_black_white - PASSED
✓ transparent_fg_red_bg_path - PASSED
✓ path_rounded - PASSED
```

### Fehlgeschlagene Tests

``` pwsh
✗ normal_black_white - FAILED (Different SVG structure)
✗ transparent_fg_red_bg - FAILED (Content differences)
```

## Interpretierung der Ergebnisse

### Strukturelle Unterschiede

- **Different SVG structure**: Die SVG-Struktur hat sich geändert (z.B. neue Elemente, andere Elementanzahl)
- **Different file sizes**: Dateigrößen unterscheiden sich
- **Content differences**: Inhaltliche Unterschiede in den SVG-Daten

### Wann Baseline neu generieren?

Generieren Sie neue Baseline-Bilder/SVGs wenn:

- Bewusste Änderungen an der QRCodeSVG-Klasse vorgenommen wurden
- Neue Features hinzugefügt wurden
- Die Ausgabe absichtlich geändert wurde

### Wann Tests fehlschlagen?

Tests schlagen fehl wenn:

- Unerwartete Änderungen in der SVG-Generierung auftreten
- Bugs in der Implementierung vorhanden sind
- Externe Abhängigkeiten sich geändert haben

## Troubleshooting

### "SVG->PNG conversion failed"

- Installieren Sie ImageMagick, Inkscape oder rsvg-convert
- Firefox ist automatisch verfügbar unter `C:\Program Files\Mozilla Firefox\firefox.exe`
- Verwenden Sie den einfachen Test (`regression_test_simple.php`) als Alternative

### "Missing current file"

- Stellen Sie sicher, dass alle Test-Varianten erfolgreich generiert wurden
- Überprüfen Sie die Berechtigungen für das Test-Verzeichnis

### Viele fehlgeschlagene Tests

- Generieren Sie neue Baseline-Bilder nach bewussten Änderungen
- Überprüfen Sie, ob unbeabsichtigte Änderungen vorgenommen wurden

## Automatisierung

Die Tests können in CI/CD-Pipelines integriert werden:

```bash
# In CI-Pipeline
php regression_test_simple.php
if [ $? -ne 0 ]; then
    echo "Regression tests failed!"
    exit 1
fi
```

## Wartung

- Regelmäßige Ausführung der Tests nach Code-Änderungen
- Aktualisierung der Baseline-Bilder bei bewussten Änderungen
- Erweiterung der Test-Cases bei neuen Features
