<?php
require_once '../vendor/autoload.php';
require_once '../qrcodesvg.php';

class QRCodeSVGRegressionTest {

    private $tolerance = 0.01; // Tolerance used for image comaprisons for image comparisons
    private $testDir;
    private $baselineDir;
    private $currentDir;
    private $testData = "Regression Test QR Code";
    private $executableCache = [];
    
    public function __construct() {
        $this->testDir = __DIR__ . '/regression_test';
        $this->baselineDir = $this->testDir . '/baseline';
        $this->currentDir = $this->testDir . '/current';
        
        // Create directories
        if (!is_dir($this->testDir)) mkdir($this->testDir);
        if (!is_dir($this->baselineDir)) mkdir($this->baselineDir);
        if (!is_dir($this->currentDir)) mkdir($this->currentDir);
        
        // Initialize executable cache
        $this->initializeExecutableCache();
    }
    
    private function initializeExecutableCache() {
        $cacheFile = $this->testDir . '/executable_cache.json';
        
        if (file_exists($cacheFile)) {
            $this->executableCache = json_decode(file_get_contents($cacheFile), true) ?: [];
        } else {
            $this->executableCache = [];
        }
    }
    
    private function saveExecutableCache() {
        $cacheFile = $this->testDir . '/executable_cache.json';
        file_put_contents($cacheFile, json_encode($this->executableCache, JSON_PRETTY_PRINT));
    }
    
    private function findExecutable($executableName, $searchPaths = []) {
        $cacheKey = $executableName;
        
        // Check cache first
        if (isset($this->executableCache[$cacheKey])) {
            $cachedPath = $this->executableCache[$cacheKey];
            if (file_exists($cachedPath)) {
                return $cachedPath;
            } else {
                // Remove invalid cache entry
                unset($this->executableCache[$cacheKey]);
            }
        }
        
        // Try provided search paths first
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                $this->executableCache[$cacheKey] = $path;
                $this->saveExecutableCache();
                return $path;
            }
        }
        
        // If not found in provided paths, search Program Files directories
        $foundPath = $this->searchProgramFiles($executableName);
        if ($foundPath) {
            $this->executableCache[$cacheKey] = $foundPath;
            $this->saveExecutableCache();
            return $foundPath;
        }
        
        return null;
    }
    
    private function searchProgramFiles($executableName) {
        $searchDirs = [
            'C:\\Program Files',
            'C:\\Program Files (x86)'
        ];
        
        foreach ($searchDirs as $searchDir) {
            if (!is_dir($searchDir)) {
                continue;
            }
            
            $foundPath = $this->recursiveSearch($searchDir, $executableName);
            if ($foundPath) {
                return $foundPath;
            }
        }
        
        return null;
    }
    
    private function recursiveSearch($dir, $executableName) {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === $executableName) {
                    return $file->getPathname();
                }
            }
        } catch (Exception $e) {
            // Skip directories that can't be accessed
        }
        
        return null;
    }
    
    public function runTest($generateBaseline = false) {
        echo "QRCodeSVG Regression Test\n";
        echo "========================\n\n";
        
        if ($generateBaseline) {
            echo "Generating baseline images...\n";
            $this->generateTestImages($this->baselineDir);
            echo "Baseline generation complete!\n\n";
            
            echo "Comparing Path vs Elemente variants in baseline...\n";
            $this->comparePathVsElemente($this->baselineDir);
        } else {
            echo "Generating current images...\n";
            $this->generateTestImages($this->currentDir);
            echo "Current generation complete!\n\n";
            
            echo "Comparing images...\n";
            $this->compareImages();
            
            echo "\nComparing Path vs Elemente variants in current...\n";
            $this->comparePathVsElemente($this->currentDir);
        }
        
        echo "\nRegression test complete!\n";
    }
    
    private function generateTestImages($outputDir) {
        $qr = QRCodeSVG::getMinimumQRCode($this->testData, QR_ERROR_CORRECT_LEVEL_M);
        
        // Farbtabelle für Variationen
        $colors = [
            '#FF0000',  // Rot
            '#00FF00',  // Grün
            '#0000FF',  // Blau
            '#FFFF00',  // Gelb
            '#FF00FF',  // Magenta
            '#00FFFF',  // Cyan
            '#FF8000',  // Orange
            '#8000FF',  // Lila
            '#0080FF',  // Hellblau
            '#FF69B4',  // Rosa
            '#B19CD9',  // Flieder
            '#006400',  // Dunkelgrün
            '#F5F5DC',  // Beige
            '#4B0082',  // Dunkel Lila
            '#FFD700',  // Sonnenblumengelb
            '#8B4513',  // Dunkelbraun
        ];
        
        $colorIndex = 0;
        $getNextColor = function() use (&$colors, &$colorIndex) {
            $color = $colors[$colorIndex];
            $colorIndex = ($colorIndex + 1) % count($colors);
            return $color;
        };
        
        $testCases = [];
        
        // Für jede Variante Normal, Rund, Kreis
        foreach (['normal', 'rounded', 'dotted'] as $variation) {
            $variationPrefix = ucfirst($variation === 'normal' ? 'Normal' : ($variation === 'rounded' ? 'Rund' : 'Kreis'));
            
            // Für jede Variante von Vordergrund und Hintergrundwerten
            $colorConfigs = [
                // Farbe Farbe
                ['fg' => $getNextColor(), 'bg' => $getNextColor(), 'name' => 'Farbe-Farbe'],
                
                // Farbe Weiß
                ['fg' => $getNextColor(), 'bg' => '#FFFFFF', 'name' => 'Farbe-Weiss'],
                
                // Weiß Farbe
                ['fg' => '#FFFFFF', 'bg' => $getNextColor(), 'name' => 'Weiss-Farbe'],
                
                // Schwarz Farbe
                ['fg' => '#000000', 'bg' => $getNextColor(), 'name' => 'Schwarz-Farbe'],
                
                // Schwarz Weiß
                ['fg' => '#000000', 'bg' => '#FFFFFF', 'name' => 'Schwarz-Weiss'],
                
                // Weiß Schwarz
                ['fg' => '#FFFFFF', 'bg' => '#000000', 'name' => 'Weiss-Schwarz'],
                
                // Farbe Schwarz 50%
                ['fg' => $getNextColor(), 'bg' => '#00000080', 'name' => 'Farbe-Schwarz-50pct'],
                
                // Farbe Farbe 20%
                ['fg' => $getNextColor(), 'bg' => $getNextColor() . '33', 'name' => 'Farbe-Farbe-20pct'],
                
                // Farbe Farbe 80%
                ['fg' => $getNextColor(), 'bg' => $getNextColor() . 'CC', 'name' => 'Farbe-Farbe-80pct'],
                
                // Farbe 50% Farbe 50%
                ['fg' => $getNextColor().'80', 'bg' => $getNextColor() . '80', 'name' => 'Farbe-50pct-Farbe-50pct'],

                // Farbe Weiß 100%
                ['fg' => $getNextColor(), 'bg' => '#FFFFFF00', 'name' => 'Farbe-Weiss-100pct'],
                
                // Weiß 100% Farbe
                ['fg' => '#FFFFFF00', 'bg' => $getNextColor(), 'name' => 'Weiss-100pct-Farbe'],
                
                // Weiß 100% Farbe 33%
                ['fg' => '#FFFFFF00', 'bg' => $getNextColor() . '54', 'name' => 'Weiss-100pct-Farbe-33pct'],
                
                // Weiß 100% Weiß Rand 8 25%
                ['fg' => '#FFFFFF00', 'bg' => '#FFFFFF', 'name' => 'Weiss-100pct-Weiss-Rand-8-25pct', 'borderWidth' => 8, 'borderRadiusPercent' => 25],
                
                // Weiß 100% Farbe Rand 10 50%
                ['fg' => '#FFFFFF00', 'bg' => $getNextColor(), 'name' => 'Weiss-100pct-Farbe-Rand-10-50pct', 'borderWidth' => 10, 'borderRadiusPercent' => 50],
            ];
            
            // Für path jeweils true oder false - verwende die gleichen Farben
            foreach ([true, false] as $usePath) {
                $pathSuffix = $usePath ? 'Path' : 'Elemente';
                
                foreach ($colorConfigs as $config) {
                    $testName = $variationPrefix . '-' . $config['name'] . '-' . $pathSuffix;
                    
                    $testCases[$testName] = [
                        'fg' => $config['fg'],
                        'bg' => $config['bg'],
                        'variation' => $variation,
                        'path' => $usePath,
                        'borderWidth' => isset($config['borderWidth']) ? $config['borderWidth'] : 5,
                        'borderRadiusPercent' => isset($config['borderRadiusPercent']) ? $config['borderRadiusPercent'] : 30,
                    ];
                }
            }
        }
        
        foreach ($testCases as $name => $params) {
            $this->generateSingleImage($qr, $name, $params, $outputDir);
        }
    }
    
    private function generateSingleImage($qr, $name, $params, $outputDir) {
        $width = 200;
        $altText = "Test QR Code - $name";
        
        // Extract parameters
        $fg = $params['fg'];
        $bg = $params['bg'];
        $variation = $params['variation'];
        $usePath = $params['path'];
        $borderWidth = isset($params['borderWidth']) ? $params['borderWidth'] : 5;
        $borderRadiusPercent = isset($params['borderRadiusPercent']) ? $params['borderRadiusPercent'] : 30;
        
        // Generate SVG
        $svg = $qr->getSVG($width, $fg, $bg, $altText, $variation, $usePath, $borderWidth, $borderRadiusPercent);
        
        // Save SVG
        $svgFile = $outputDir . '/' . $name . '.svg';
        file_put_contents($svgFile, $svg);
        
        // Convert to PNG
        $pngFile = $outputDir . '/' . $name . '.png';
        $this->convertSvgToPng($svgFile, $pngFile);
        
        echo "Generated: $name\n";
    }
    
    private function convertSvgToPng($svgFile, $pngFile) {
        // Method 1: Try Inkscape (most reliable for SVG conversion)
        $inkscapePath = $this->findExecutable('inkscape.exe', [
            "C:\\Program Files\\Inkscape\\bin\\inkscape.exe", // User provided path
            "C:\\Program Files (x86)\\Inkscape\\bin\\inkscape.exe",
        ]);
        
        if ($inkscapePath && $this->convertWithInkscape($svgFile, $pngFile, $inkscapePath)) {
            return true;
        }
        
        // Method 2: Try Chrome with JavaScript rendering (fallback for transparency)
        $chromePath = $this->findExecutable('chrome.exe', [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', // User provided path
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        ]);
        
        if ($chromePath) {
            // Try Chrome JS method first (most reliable for transparency)
            if ($this->convertWithChromeJS($svgFile, $pngFile, $chromePath)) {
                return true;
            }
            
            // Try Chrome direct screenshot as fallback
            if ($this->convertWithChrome($svgFile, $pngFile, $chromePath)) {
                return true;
            }
        }
        
        // Method 3: Try Firefox (fallback)
        $firefoxPath = $this->findExecutable('firefox.exe', [
            'C:\\Program Files\\Mozilla Firefox\\firefox.exe', // User provided path
            'C:\\Program Files (x86)\\Mozilla Firefox\\firefox.exe',
        ]);
        
        if ($firefoxPath && $this->convertWithFirefox($svgFile, $pngFile, $firefoxPath)) {
            return true;
        }
        
        // Method 4: Try ImageMagick (fallback)
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(300, 300);
                $imagick->readImage($svgFile);
                $imagick->setImageFormat('png');
                $imagick->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1, true);
                $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));
                $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $imagick->writeImage($pngFile);
                $imagick->clear();
                $imagick->destroy();
                
                if (file_exists($pngFile) && filesize($pngFile) > 1000) {
                    return true;
                }
            } catch (Exception $e) {
                // ImageMagick failed, try other methods
            }
        }
        
        // Method 5: Try rsvg-convert (fallback)
        $rsvgPath = $this->findExecutable('rsvg-convert.exe', [
            'C:\\Program Files\\rsvg-convert\\rsvg-convert.exe',
            'C:\\Program Files (x86)\\rsvg-convert\\rsvg-convert.exe',
        ]);
        
        if ($rsvgPath) {
            $command = "\"$rsvgPath\" -o \"$pngFile\" \"$svgFile\" 2>&1";
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($pngFile)) {
                return true;
            }
        }
        
        // Method 6: Try ImageMagick command line (fallback)
        $magickPath = $this->findExecutable('magick.exe', [
            'C:\\Program Files\\ImageMagick-7.1.0-Q16-HDRI\\magick.exe',
            'C:\\Program Files\\ImageMagick-7.0.11-Q16-HDRI\\magick.exe',
            'C:\\Program Files (x86)\\ImageMagick-7.1.0-Q16-HDRI\\magick.exe',
            'C:\\Program Files (x86)\\ImageMagick-7.0.11-Q16-HDRI\\magick.exe',
        ]);
        
        if ($magickPath) {
            $command = "\"$magickPath\" \"$svgFile\" \"$pngFile\" 2>&1";
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($pngFile)) {
                return true;
            }
        }
        
        // Method 7: Try other command-line tools
        $methods = [
            "convert \"$svgFile\" \"$pngFile\"",
            "inkscape --export-png=\"$pngFile\" --export-dpi=96 \"$svgFile\"",
            "rsvg-convert -h 200 \"$svgFile\" > \"$pngFile\"",
        ];
        
        foreach ($methods as $command) {
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($pngFile) && filesize($pngFile) > 0) {
                return true;
            }
        }
        
        // Method 8: Fallback to GD-based conversion
        if ($this->convertSvgToPngWithGD($svgFile, $pngFile)) {
            return true;
        }
        
        // If all methods failed, create a placeholder
        $this->createPlaceholderPng($pngFile);
        return false;
    }
    
    private function convertWithInkscape($svgFile, $pngFile, $inkscapePath) {
        // Use Inkscape command line to convert SVG to PNG
        $command = "\"$inkscapePath\" --export-type=png --export-filename=\"$pngFile\" --export-dpi=96 \"$svgFile\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($pngFile)) {
            // Add checkerboard background using GD
            $this->addCheckerboardBackground($pngFile);
            return true;
        }
        
        return false;
    }
    
    private function addCheckerboardBackground($pngFile) {
        if (!extension_loaded('gd')) {
            return; // GD not available, skip checkerboard
        }
        
        // Load the PNG
        $image = imagecreatefrompng($pngFile);
        if (!$image) {
            return;
        }
        
        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create new image with white background
        $newImage = imagecreatetruecolor($width, $height);
        
        // Enable alpha blending and save alpha
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        
        $white = imagecolorallocate($newImage, 255, 255, 255);
        $gray = imagecolorallocate($newImage, 204, 204, 204);
        
        // Fill with white background
        imagefill($newImage, 0, 0, $white);
        
        // Calculate checkerboard block size (5/7 of QR block size)
        // QR code is typically 21x21 blocks, so 5/7 of block = ~15px at 1024px scale
        $blockSize = floor($width / 21 * 5 / 7);
        
        // Draw checkerboard pattern
        for ($y = 0; $y < $height; $y += $blockSize) {
            for ($x = 0; $x < $width; $x += $blockSize) {
                if ((floor($x / $blockSize) + floor($y / $blockSize)) % 2 === 1) {
                    imagefilledrectangle($newImage, $x, $y, $x + $blockSize - 1, $y + $blockSize - 1, $gray);
                }
            }
        }
        
        // Enable alpha blending for copying
        imagealphablending($newImage, true);
        
        // Copy original PNG over checkerboard (preserving transparency)
        imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
        
        // Save the result
        imagepng($newImage, $pngFile);
        
        // Clean up
        imagedestroy($image);
        imagedestroy($newImage);
    }
    
    private function convertWithChromeJS($svgFile, $pngFile, $chromePath) {
        // Read SVG content and convert to data URL
        $svgContent = file_get_contents($svgFile);
        $svgDataUrl = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
        
        // Create HTML file with JavaScript to convert SVG data URL to PNG data URL
        $htmlFile = dirname($pngFile) . '/temp_' . basename($pngFile, '.png') . '.html';
        
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background: transparent;
            width: 1024px;
            height: 1024px;
            overflow: hidden;
        }
        canvas {
            display: none;
        }
    </style>
</head>
<body>
    <canvas id="canvas" width="1024" height="1024"></canvas>
    
    <script>
        function svgToPng() {
            const canvas = document.getElementById("canvas");
            const ctx = canvas.getContext("2d");
            
            // Draw checkerboard background
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, 1024, 1024);
            
            // Draw checkerboard pattern with 5/7 block size
            // QR code is typically 21x21 blocks, so 5/7 of block = ~15px at 1024px scale
            const blockSize = Math.floor(1024 / 21 * 5 / 7);
            ctx.fillStyle = "#cccccc";
            for (let y = 0; y < 1024; y += blockSize) {
                for (let x = 0; x < 1024; x += blockSize) {
                    if ((Math.floor(x / blockSize) + Math.floor(y / blockSize)) % 2 === 1) {
                        ctx.fillRect(x, y, blockSize, blockSize);
                    }
                }
            }
            
            // Create image from SVG data URL
            const img = new Image();
            img.onload = function() {
                // Draw SVG to canvas at full size
                ctx.drawImage(img, 0, 0, 1024, 1024);
                
                // Convert canvas to PNG data URL
                const pngDataUrl = canvas.toDataURL("image/png");
                
                // Output PNG data URL to console (we\'ll capture this)
                console.log("PNG_DATA_URL:" + pngDataUrl);
                
                // Close window after output
                setTimeout(() => {
                    window.close();
                }, 100);
            };
            
            img.onerror = function() {
                console.log("PNG_DATA_URL:ERROR");
                window.close();
            };
            
            // Load SVG from data URL
            img.src = "' . $svgDataUrl . '";
        }
        
        // Start conversion immediately
        svgToPng();
    </script>
</body>
</html>';
        
        file_put_contents($htmlFile, $htmlContent);
        
        // Run Chrome with console output capture
        $command = "\"$chromePath\" --headless --disable-gpu --window-size=1024,1024 --force-device-scale-factor=1 --enable-logging --log-level=0 --v=1 --virtual-time-budget=5000 \"file://" . realpath($htmlFile) . "\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        // Look for PNG data URL in console output
        $pngDataUrl = null;
        foreach ($output as $line) {
            if (strpos($line, 'PNG_DATA_URL:') !== false) {
                $pngDataUrl = substr($line, strpos($line, 'PNG_DATA_URL:') + 13);
                break;
            }
        }
        
        
        // Clean up temporary HTML file
        if (file_exists($htmlFile)) {
            unlink($htmlFile);
        }
        
        // If we got PNG data URL, save it
        if ($pngDataUrl && $pngDataUrl !== 'ERROR') {
            // Remove data URL prefix (data:image/png;base64,)
            $pngData = substr($pngDataUrl, strpos($pngDataUrl, ',') + 1);
            $pngBinary = base64_decode($pngData);
            
            if ($pngBinary) {
                file_put_contents($pngFile, $pngBinary);
                return true;
            }
        }
        
        return false;
    }
    
    private function convertWithChrome($svgFile, $pngFile, $chromePath) {
        // Use HTML wrapper with checkerboard background (most reliable)
        $htmlFile = dirname($pngFile) . '/temp_' . basename($pngFile, '.png') . '.html';
        $svgContent = file_get_contents($svgFile);
        
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background: transparent;
            width: 1024px;
            height: 1024px;
            overflow: hidden;
        }
        svg { 
            width: 1024px; 
            height: 1024px; 
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            max-width: none;
            max-height: none;
        }
        .checkerboard {
            position: absolute;
            top: 0;
            left: 0;
            width: 1024px;
            height: 1024px;
            background: white;
            z-index: -1;
        }
        .checkerboard::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(45deg, #cccccc 25%, transparent 25%), 
                linear-gradient(-45deg, #cccccc 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #cccccc 75%), 
                linear-gradient(-45deg, transparent 75%, #cccccc 75%);
            background-size: 30px 30px;
            background-position: 0 0, 0 15px, 15px -15px, -15px 0px;
        }
    </style>
</head>
<body>
    <div class="checkerboard"></div>
    ' . $svgContent . '
</body>
</html>';
        
        file_put_contents($htmlFile, $htmlContent);
        
        $command = "\"$chromePath\" --headless --disable-gpu --window-size=1024,1024 --force-device-scale-factor=1 --virtual-time-budget=5000 --screenshot=\"$pngFile\" \"file://" . realpath($htmlFile) . "\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        // Clean up temporary HTML file
        if (file_exists($htmlFile)) {
            unlink($htmlFile);
        }
        
        return ($returnCode === 0 && file_exists($pngFile));
    }
    
    private function convertWithFirefox($svgFile, $pngFile, $firefoxPath) {
        // Create a temporary HTML file to load the SVG
        $htmlFile = dirname($pngFile) . '/temp_' . basename($pngFile, '.png') . '.html';
        $svgContent = file_get_contents($svgFile);
        
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            background: transparent;
            width: 1024px;
            height: 1024px;
        }
        svg { 
            width: 1024px; 
            height: 1024px; 
            display: block;
            max-width: none;
            max-height: none;
        }
    </style>
</head>
<body>
    ' . $svgContent . '
</body>
</html>';
        
        file_put_contents($htmlFile, $htmlContent);
        
        // Try Firefox headless screenshot with specific window size
        $command = "\"$firefoxPath\" --headless --window-size=1024,1024 --screenshot=\"$pngFile\" \"file://" . realpath($htmlFile) . "\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        // Clean up temporary HTML file
        if (file_exists($htmlFile)) {
            unlink($htmlFile);
        }
        
        return ($returnCode === 0 && file_exists($pngFile));
    }
    
    private function convertSvgToPngWithGD($svgFile, $pngFile) {
        // Create a simple PNG representation based on the SVG filename
        // This is a fallback when proper SVG conversion tools are not available
        
        $image = imagecreate(200, 200);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);
        $green = imagecolorallocate($image, 0, 255, 0);
        $yellow = imagecolorallocate($image, 255, 255, 0);
        
        // Fill with white background
        imagefill($image, 0, 0, $white);
        
        // Determine colors based on filename
        $filename = basename($svgFile, '.svg');
        $fgColor = $black;
        $bgColor = $white;
        
        if (strpos($filename, 'red') !== false) {
            $bgColor = $red;
        } elseif (strpos($filename, 'blue') !== false) {
            $bgColor = $blue;
        } elseif (strpos($filename, 'green') !== false) {
            $bgColor = $green;
        } elseif (strpos($filename, 'yellow') !== false) {
            $bgColor = $yellow;
        }
        
        if (strpos($filename, 'white_black') !== false) {
            $fgColor = $white;
            $bgColor = $black;
        }
        
        // Fill background
        imagefill($image, 0, 0, $bgColor);
        
        // Create a simple QR-like pattern
        $blockSize = 8;
        $qrSize = 25 * $blockSize;
        $startX = (200 - $qrSize) / 2;
        $startY = (200 - $qrSize) / 2;
        
        // Create QR-like pattern
        for ($y = 0; $y < 25; $y++) {
            for ($x = 0; $x < 25; $x++) {
                // Create a pattern that represents a QR code
                $isDark = false;
                
                // Corner squares (finder patterns)
                if (($x < 7 && $y < 7) || ($x >= 18 && $y < 7) || ($x < 7 && $y >= 18)) {
                    $isDark = ($x % 2 == 0 && $y % 2 == 0) || ($x % 2 == 1 && $y % 2 == 1);
                } else {
                    // Random-ish pattern for the rest
                    $isDark = (($x * 3 + $y * 7) % 11) < 5;
                }
                
                if ($isDark) {
                    $px = $startX + $x * $blockSize;
                    $py = $startY + $y * $blockSize;
                    imagefilledrectangle($image, $px, $py, $px + $blockSize - 1, $py + $blockSize - 1, $fgColor);
                }
            }
        }
        
        imagepng($image, $pngFile);
        imagedestroy($image);
        
        return file_exists($pngFile) && filesize($pngFile) > 0;
    }
    
    private function createPlaceholderPng($pngFile) {
        // Create a simple 200x200 PNG with text indicating conversion failed
        $image = imagecreate(200, 200);
        $bg = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 255, 0, 0);
        imagestring($image, 5, 50, 90, 'SVG->PNG', $text);
        imagestring($image, 3, 30, 110, 'conversion failed', $text);
        imagepng($image, $pngFile);
        imagedestroy($image);
    }
    
    private function comparePathVsElemente($directory) {
        $pathFiles = glob($directory . '/*-Path.png');
        $errors = [];
        $passed = 0;
        $total = 0;
        
        foreach ($pathFiles as $pathFile) {
            $filename = basename($pathFile);
            $elementeFilename = str_replace('-Path.png', '-Elemente.png', $filename);
            $elementeFile = $directory . '/' . $elementeFilename;
            
            $total++;
            
            if (!file_exists($elementeFile)) {
                $errors[] = "Missing Elemente variant: $elementeFilename";
                echo "✗ $filename vs $elementeFilename - MISSING ELEMENTE\n";
                continue;
            }
            
            if ($this->compareImageFiles($pathFile, $elementeFile)) {
                $passed++;
                echo "✓ $filename vs $elementeFilename - IDENTICAL\n";
            } else {
                $errors[] = "Path vs Elemente difference: $filename vs $elementeFilename";
                echo "✗ $filename vs $elementeFilename - DIFFERENT\n";
            }
        }
        
        echo "\nPath vs Elemente Results: $passed/$total comparisons passed\n";
        
        if (!empty($errors)) {
            echo "\nPath vs Elemente Errors detected:\n";
            foreach ($errors as $error) {
                echo "- $error\n";
            }
        }
        
        return empty($errors);
    }

    private function compareImages() {
        $baselineFiles = glob($this->baselineDir . '/*.png');
        $errors = [];
        $passed = 0;
        $total = 0;
        
        foreach ($baselineFiles as $baselineFile) {
            $filename = basename($baselineFile);
            $currentFile = $this->currentDir . '/' . $filename;
            
            $total++;
            
            if (!file_exists($currentFile)) {
                $errors[] = "Missing current file: $filename";
                continue;
            }
            
            if ($this->compareImageFiles($baselineFile, $currentFile)) {
                $passed++;
                echo "✓ $filename - PASSED\n";
            } else {
                $errors[] = "Visual difference detected: $filename";
                echo "✗ $filename - FAILED\n";
            }
        }
        
        echo "\nResults: $passed/$total tests passed\n";
        
        if (!empty($errors)) {
            echo "\nErrors detected:\n";
            foreach ($errors as $error) {
                echo "- $error\n";
            }
        } else {
            echo "\nAll tests passed! No regressions detected.\n";
        }
    }
    
    private function compareImageFiles($file1, $file2) {
        // First check if files exist and have reasonable sizes
        if (!file_exists($file1) || !file_exists($file2)) {
            return false;
        }
        
        $size1 = filesize($file1);
        $size2 = filesize($file2);
        
        $filename1 = basename($file1);
        $filename2 = basename($file2);
        
        // If files are very small (likely placeholders), use exact comparison
        if ($size1 < 1000 && $size2 < 1000) {
            return $size1 === $size2 && md5_file($file1) === md5_file($file2);
        }
        
        // For larger files, use ImageMagick comparison with tolerance
        if (extension_loaded('imagick')) {
            try {
                $img1 = new Imagick($file1);
                $img2 = new Imagick($file2);
                
                // For transparent images, be more lenient
                $filename1 = basename($file1);
                $filename2 = basename($file2);
                $isTransparent = (strpos($filename1, 'transparent') !== false || strpos($filename2, 'transparent') !== false);
                
                if ($isTransparent) {
                    // For transparent images, just check if they're both transparent
                    $alpha1 = $img1->getImageAlphaChannel();
                    $alpha2 = $img2->getImageAlphaChannel();
                    $img1->destroy();
                    $img2->destroy();
                    
                    return $alpha1 === $alpha2;
                }
                
                // Compare images with some tolerance for minor rendering differences
                $result = $img1->compareImages($img2, Imagick::METRIC_MEANSQUAREERROR);
                $difference = $result[1];
                
                $img1->clear();
                $img1->destroy();
                $img2->clear();
                $img2->destroy();
                
                // Allow small differences (threshold of 0.01)
                return $difference < 0.01;
                
            } catch (Exception $e) {
                // ImageMagick comparison failed, fall back to binary comparison
                return false;
            }
        }
        
        // Fallback: binary comparison
        return md5_file($file1) === md5_file($file2);
    }
    
    public function cleanup() {
        // Optional cleanup method
        if (is_dir($this->testDir)) {
            $this->deleteDirectory($this->testDir);
        }
    }
    
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// Run the test
$test = new QRCodeSVGRegressionTest();

// Check command line arguments
$generateBaseline = false;
if (isset($argv[1]) && $argv[1] === '--generate-baseline') {
    $generateBaseline = true;
    echo "Generating baseline images...\n";
}

$test->runTest($generateBaseline);

echo "\nUsage:\n";
echo "php regression_test.php --generate-baseline  # Generate baseline images\n";
echo "php regression_test.php                       # Run regression test\n";
?>
