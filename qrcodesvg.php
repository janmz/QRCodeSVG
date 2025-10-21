<?php
/*
 * Class to create different and compact Variations of SVG-QRCodes
 * 
 * It is possible to define forground and background color including transparency.
 * It is possible to use three variations:
 *  normal: Square blocks
 *  dotted: Circles replace the blocks
 *  rounded: Each group of blocks is given rounded corners
 * 
 * It is based on the class QRCode by Kazuhiko Arase (https://github.com/kazuhikoarase/qrcode-generator)
 * This original class is included in vendor/vaya/qrcode-php/qrcode.php and will be autoloaded by composer.
 * 
 * Author: Jan Neuhaus, VAYA Consulting
 * 
 * Created: 5.8.2025
 * 
 * Version: 1.4
 * 
 * 1.4 Added an hight overlap to avoid stripes in all resolutions 
 * 1.3 Finalized the class and added the ability to use the class without composer
 * 1.2 Added the different variations and optimized to backgroud into at most one rect
 * 1.1 Transformed into a class
 * 1.0 Initial programming
 * 
 */

// helper-function for debug output
function asdigit($x) {
    if ($x) return "1"; else return "0";
}

class QRCodeSVG extends QRCode {
    /*
     * Function to normalize rgb or rgba Colors
     */
    private function makeShortestColor($c) {
        $c = strtolower(str_replace(' ', '', $c));
        if (strpos($c, 'rgba') === 0) {
            list($r, $g, $b, $a) = explode(',', substr($c, 5));
            $r = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
            $g = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
            $b = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
            $a = str_pad(dechex(intval(floatval($a) * 255)), 2, '0', STR_PAD_LEFT);
            if ($a=="ff") {
                if (substr($r,0,1)==substr($r,1,1)&&substr($g,0,1)==substr($g,1,1) && substr($b,0,1)==substr($b,1,1) ) {
                    return "#".substr($r,0,1).substr($g,0,1).substr($b,0,1);
                } else {
                    return "#".$r.$g.$b;
                }
            } else {
                return "#".$r.$g.$b.$a;
            }
        } elseif (strpos($c, 'rgb') === 0) {
            list($r, $g, $b) = explode(',', substr($c, 4));
            $r = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
            $g = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
            $b = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
            if (substr($r,0,1)==substr($r,1,1)&&substr($g,0,1)==substr($g,1,1) && substr($b,0,1)==substr($b,1,1) ) {
                return '#'.substr($r,0,1).substr($g,0,1).substr($b,0,1);
            } else {
                return '#'.$r.$g.$b;
            }
        } elseif (substr($c,0,1)=='#') {
            switch (strlen($c)) {
                case 4: // #rgb
                    return $c;
                case 7: // #rrggbb
                    if (substr($c,1,1)==substr($c,2,1)&&substr($c,3,1)==substr($c,4,1) && substr($c,5,1)==substr($c,6,1) ) {
                        return '#'.substr($c,1,1).substr($c,3,1).substr($c,5,1);
                    } else {
                        return $c;
                    }
                case 9: // #rrggbbaa
                    $r = hexdec(substr($c,1,2));
                    $g = hexdec(substr($c,3,2));
                    $b = hexdec(substr($c,5,2));
                    $a = hexdec(substr($c,7,2));
                    if ($a=='ff'){
                        if (substr($c,1,1)==substr($c,2,1)&&substr($c,3,1)==substr($c,4,1) && substr($c,5,1)==substr($c,6,1) ) {
                            return '#'.substr($c,1,1).substr($c,3,1).substr($c,5,1);
                        } else {
                            return $c;
                        }
                    } else {
                        return $c;
                    }
                default:
                    return '#e00'; // Rot um eine Warnung vor der falschen Farbe anzuzeigen.
            }
        } else {
                return '#e00'; // Rot um eine Warnung vor der falschen Farbe anzuzeigen.
        }
    }
    /*
     * Override getMinimumQRCode so that an QRCodeSVG is returned
     */
    static function getMinimumQRCode($data, $errorCorrectLevel) {

        $mode = QRUtil::getMode($data);

        $qr = new QRCodeSVG();
        $qr->setErrorCorrectLevel($errorCorrectLevel);
        $qr->addData($data, $mode);

        $qrData = $qr->getData(0);
        $length = $qrData->getLength();

        for ($typeNumber = 1; $typeNumber <= 40; $typeNumber++) {
            if ($length <= QRUtil::getMaxLength($typeNumber, $mode, $errorCorrectLevel) ) {
                $qr->setTypeNumber($typeNumber);
                break;
            }
        }

        $qr->make();

        return $qr;
    }

    /**
     * Generates an SVG representation of the QR code.
     * 
     * This method returns an SVG string that represents the QR code. 
     * It takes into account the width, foreground color, background color, and alt text for the image.
     * 
     * @param int $width The width of the QR code image.
     * @param string $fg The foreground color of the QR code in any HTML format.
     * @param string $bg The background color of the QR code in any HTML format.
     * @param string $alt_text The alt text for the QR code image.
     * @param string $variation The variation, that should be applied to the qrcode
     *                   normal = Square Dots
     *                   rounded = Rounded Dots and Rectangles
     *                   dotted = No Rectangles, all round dots
     * 
     * @return string The SVG representation of the QR code.
     * 
     */
    public function getSVG($width, $fg = "#000000FF", $bg="#ffffff00", $alt_text="", $variation="normal") {

        $height_overlap = 0.005;
        $blocks = $this->getModuleCount();
        $buffer = '<svg role="img"';
        if (trim($alt_text)!="") {
            $buffer .= ' aria-label="'.$alt_text.'"';
        }
        $buffer .= ' width="' . $width . '" height="' . $width . '" viewBox="0 0 ' . $blocks . ' ' . $blocks . '" xmlns="http://www.w3.org/2000/svg">';

        $fg = $this->makeShortestColor($fg);
        $fg_transparent = strlen($fg)==9 && substr($fg,7,2)=="00";
        $bg = $this->makeShortestColor($bg);
        $bg_transparent = strlen($bg)==9 && substr($bg,7,2)=="00";

        $buffer .= '<defs><rect id="p" width="1" height="'.(1+$height_overlap).'"/></defs>';

        if (!$bg_transparent) {
            // Hintergrund immer als komplettes Rechteck zeichnen
            if (strpos(strtolower($variation), 'dotted') !== false || strpos(strtolower($variation), 'rounded') !== false) {
                $buffer .= "<rect id=\"bg\" rx=\"0.5\" x=\"0\" y=\"0\" width=\"$blocks\" height=\"$blocks\" fill=\"$bg\" shape-rendering=\"crispEdges\"></rect>";
            } else {
                $buffer .= "<rect id=\"bg\" x=\"0\" y=\"0\" width=\"$blocks\" height=\"$blocks\" fill=\"$bg\" shape-rendering=\"crispEdges\"></rect>";
            }
        }
        if (!$fg_transparent) {
            $buffer .= "<g fill=\"$fg\" shape-rendering=\"crispEdges\">";
            for ($r = 0; $r < $blocks; $r++) {
                $c_start = 0;
                $last = $this->isDark($r, 0);
                if ($r==$blocks-1) {
                    // In the lowest row, the overlap is not needed
                    $height_overlap = 0;
                }
                for ($c = 1; $c < $blocks; $c++) {
                    // Zuerst nur die "dunklen" Pixel
                    $akt = $this->isDark($r, $c);
                    if (strpos(strtolower($variation), 'dotted') !== false) {
                        // Für Punkte wird nach jedem Pixel ein neuer Block begonnen...
                        if ($last) {
                            $buffer .= '<circle cx="' . ($c - 0.5) . '" cy="' . ($r + 0.5) . '" r="0.5"></circle>';
                            $last = false;
                        }
                        if ($akt) {
                            $buffer .= '<circle cx="' . ($c + 0.5) . '" cy="' . ($r + 0.5) . '" r="0.5"></circle>';
                        }
                    } else {
                        if ($last && !$akt) { // Wechsel von Dunkel nach Hell
                            $this->drawBlock($buffer,$variation,$blocks,$c,$c_start,$r,$fg);
                        } elseif ($akt && !$last) { // Wechsel von Hell nach Dunkel
                            $c_start = $c;
                        }
                        $last = $akt;
                    }
                }
                if ($last) {
                    $this->drawBlock($buffer,$variation,$blocks,$c,$c_start,$r,$fg);
                    $last = $akt;
                }
            }
            $buffer .="</g>";
        }
        $buffer .= "</svg>";
        return $buffer;
    }
    private function drawBlock(&$buffer, $variation, $blocks, $c, $c_start, $r, $fg) {
        $len = $c-$c_start;
        if (strpos(strtolower($variation), 'rounded') !== false) {
            $upleft = true;
            $upright = true;
            if ($r==0 ) {
                $upleft = false;
                $upright = false;
            } else {
                if  (!$this->isDark($r-1,$c_start)) {
                    $upleft =false;
                }
                if  (!$this->isDark($r-1,$c-1)) {
                    $upright =false;
                }
            }
            $downleft = true;
            $downright = true;
            if ($r==$blocks-1 ) {
                $downleft = false;
                $downright = false;
            } else {
                if  (!$this->isDark($r+1,$c_start)) {
                    $downleft =false;
                }
                if  (!$this->isDark($r+1,$c-1)) {
                    $downright =false;
                }
            }
            $x1 = $c_start;
            $x2 = $c;
            $y1 = $r;
            $y2 = $r+1+$height_overlap;
            if (!$upleft && !$upright && !$downleft && !$downright) {
                // 0000 - Keine Kontakte => Linker und Rechter Halbkreis
                // .<#>.
                if ($len==1) {
                    $buffer .= '<circle cx="' . ($x1 + 0.5) . '" cy="' . ($y1 + 0.5) . '" r="0.5" fill="' . $fg . '"/>';
                } else {
                    $buffer .= "<path d=\"M $x1," . ($y1 + 0.5) . 
                               " A 0.5,0.5 0 0,0 ".($x1+0.5).",$y2" . 
                               " L " . ($x2 - 0.5) . ",$y2" .
                               " A 0.5,0.5 0 0,0 $x2," . ($y1 + 0.5) .
                               " A 0.5,0.5 0 0,0 " . ($x2 - 0.5) . ",$y1" .
                               " L ".($x1+0.5).",$y1" .
                               " A 0.5,0.5 0 0,0 $x1," . ($y1 + 0.5) . 
                               " Z\" fill=\"$fg\"/>";
                }
            } elseif ($upleft && $upright && $downleft && $downright) {
                // 1111 - Überall Anschlüsse => Rechteck
                // .###.
                if ($len==1) {
                    $buffer .= '<use href="#p" x="' . ($c_start) . '" y="' . ($r ) . '"/>';
                } else {
                    $buffer .= '<rect x="' . $c_start . '" y="' . $r  . '" width="'.$len.'" height="1" fill="' . $fg . '"/>';
                }
            } elseif (!$upleft && !$upright && !$downleft && $downright) {
                // 0001 - Nur unten rechts => Linker Halbkreis + Viertelkreis unten rechts
                // .<#\. 
                // ...#.
                $buffer .= "<path d=\"M $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " L $x2,$y2" .
                                    " L " . ($x1 + 0.5) . ",$y2" .
                                    " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                                    " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && !$upright && $downleft && !$downright) {
                // 0010 - Nur unten links => Linker Halbkreis + gerade nach rechts
                // ./#>.
                // .#...
                $buffer .= "<path d=\"M " . ($x1 + 0.5) . ",$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L $x1,$y2" .
                                    " L $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && !$upright && $downleft && $downright) {
                // 0011 - Beide unten => Obere Halbkreise
                // ./#\.
                // .#.#.
                $buffer .= "<path d=\"M $x1," . ($y1 + 0.5) .
                           " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                           " L " . ($x2 - 0.5) . ",$y1" .
                           " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                           " L $x2,$y2" .
                           " L $x1,$y2" .
                           " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && $upright && !$downleft && !$downright) {
                // 0100 - Nur oben rechts => Linker Halbkreis + Viertelkreis oben rechts
                // ...#.
                // .<#/.
                $buffer .= "<path d=\"M $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " L $x2,$y1" .
                                    " L $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L " . ($x1 + 0.5) . ",$y2" .
                                    " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                                    " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && $upright && !$downleft && $downright) {
                // 0101 - Rechts beide => Linker Halbkreis
                // ...#.
                // .<##.
                // ...#.
                $buffer .= "<path d=\"M $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " L $x2,$y1" .
                                    " L $x2,$y2" .
                                    " L " . ($x1 + 0.5) . ",$y2" .
                                    " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                                    " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && $upright && $downleft && !$downright) {
                // 0110 - Oben rechts + unten links => Diagonal
                // ...#.
                // ./#/.
                // .#...
                $buffer .= "<path d=\"M " . ($x1 + 0.5) . ",$y1" .
                                    " L $x2,$y1" .
                                    " L $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L $x1,$y2" .
                                    " L $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif (!$upleft && $upright && $downleft && $downright) {
                // 0111 - Alle außer oben links => Viertelkreis oben links
                // ...#.
                // ./##.
                // .#.#.
                $buffer .= "<path d=\"M " . ($x1 + 0.5) . ",$y1" .
                                    " L $x2,$y1" .
                                    " L $x2,$y2" .
                                    " L $x1,$y2" .
                                    " L $x1," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && !$upright && !$downleft && !$downright) {
                // 1000 - Nur oben links => Rechter Halbkreis + Viertelkreis unten links
                // .#...
                // .\#>.
                $buffer .= "<path d=\"M $x1,$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L " . ($x1 + 0.5) . ",$y2" .
                                    " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                                    " L $x1,$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && !$upright && !$downleft && $downright) {
                // 1001 - Oben links + unten rechts => Diagonal
                // .#...
                // .\#\.
                // ...#.
                $buffer .= "<path d=\"M $x1,$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " L $x2,$y2" .
                                    " L " . ($x1 + 0.5) . ",$y2" .
                                    " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                                    " L $x1,$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && !$upright && $downleft && !$downright) {
                // 1010 - Links beide => Rechter Halbkreis
                // .#...
                // .##>.
                // .#...
                $buffer .= "<path d=\"M $x1,$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L $x1,$y2" .
                                    " L $x1,$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && !$upright && $downleft && $downright) {
                // 1011 - Alle außer oben rechts => Viertelkreis oben rechts
                // .#...
                // .##\.
                // .#.#.
                $buffer .= "<path d=\"M $x1,$y1" .
                                    " L " . ($x2 - 0.5) . ",$y1" .
                                    " A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5) .
                                    " L $x2,$y2" .
                                    " L $x1,$y2" .
                                    " L $x1,$y1" .
                                    " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && $upright && !$downleft && !$downright) {
                // 1100 - Beide oben => Untere Halbkreise
                // .#.#.
                // .\#/.
                $buffer .= "<path d=\"M $x1,$y1" .
                           " L $x2,$y1" .
                           " L $x2," . ($y1 + 0.5) .
                           " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                           " L " . ($x1 + 0.5) . ",$y2" .
                           " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                           " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && $upright && !$downleft && $downright) {
                // 1101 - Alle außer unten links => Viertelkreis unten links
                // .#.#.
                // .\##.
                // ...#.
                $buffer .= "<path d=\"M $x1,$y1" .
                           " L $x2,$y1" .
                           " L $x2,$y2" .
                           " L " . ($x1 + 0.5) . ",$y2" .
                           " A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5) .
                           " Z\" fill=\"$fg\"/>";
            } elseif ($upleft && $upright && $downleft && !$downright) {
                // 1110 - Alle außer unten rechts => Viertelkreis unten rechts
                // .#.#.
                // .##/.
                // .#...
                $buffer .= "<path d=\"M $x1,$y1" .
                                    " L $x2,$y1" .
                                    " L $x2," . ($y1 + 0.5) .
                                    " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2" .
                                    " L $x1,$y2" .
                                    " L $x1,$y1" .
                                    " Z\" fill=\"$fg\"/>";
            }
        } else {
            if ($len==1){
                $buffer .= '<use href="#p" x="' . ($c_start) . '" y="' . ($r ) . '"/>';
            } else {
                $buffer .= '<rect x="' . $c_start . '" y="' . $r  . '" width="'.$len.'" height="1"/>';
            }
        }
    }
}