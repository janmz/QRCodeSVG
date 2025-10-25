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
 * Version: 1.5
 * 
 * 1.5 Added Version exposure and alternative renderer as full path, added regression testing
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

    const VERSION = "1.5";
    
    /*
     * Function to normalize rgb or rgba Colors
     */
    private function convertColorToSVG($color, $useOpacity = false) {
        // Convert color to 6-digit hex and extract alpha for opacity
        $color = $this->makeShortestColor($color);
        
        if (strlen($color) == 9) {
            // 8-digit hex with alpha
            $hex = substr($color, 0, 7); // Remove alpha
            $alpha = hexdec(substr($color, 7, 2)) / 255; // Convert to 0-1
            
            // Special case: completely transparent (alpha = 0)
            if ($alpha == 0) {
                return ['color' => '#000000', 'opacity' => 0];
            }
            
            if ($useOpacity && $alpha < 1) {
                return ['color' => $hex, 'opacity' => $alpha];
            } else {
                return ['color' => $hex, 'opacity' => null];
            }
        } else {
            return ['color' => $color, 'opacity' => null];
        }
    }

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

    /*
     * Function to get the version of the class
     */
    public function getVersion() {
        return self::VERSION;
    }
    /*
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
    public function getSVG($width, $fg = "#000000FF", $bg="#ffffff00", $alt_text="", $variation="normal", $usePathRendering = true, $borderWidth = 5, $borderRadiusPercent = 30) {

        $height_overlap = 0.005;
        $blocks = $this->getModuleCount();

        $fg = $this->makeShortestColor($fg);
        $fg_transparent = strlen($fg)==9 && substr($fg,7,2)=="00";
        $bg = $this->makeShortestColor($bg);
        $bg_transparent = strlen($bg)==9 && substr($bg,7,2)=="00";

 
        // Handle transparent foreground cases
        $draw_background_only = $fg_transparent && !$bg_transparent;
        
        
 
        // Calculate SVG dimensions and viewBox
        $svgWidth = $width;
        $svgHeight = $width;
        $viewBoxWidth = $blocks;
        $viewBoxHeight = $blocks;
        
        // If we have a transparent foreground with border, adjust dimensions
        if ($draw_background_only) {
            $viewBoxWidth = $blocks + 2 * $borderWidth;
            $viewBoxHeight = $blocks + 2 * $borderWidth;
        }

        $buffer = '<svg role="img"';
        if (trim($alt_text)!="") {
            $buffer .= ' aria-label="'.trim($alt_text).'"';
        }
        $buffer .= ' width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $viewBoxWidth . ' ' . $viewBoxHeight . '" xmlns="http://www.w3.org/2000/svg">';
        $buffer .= '<metadata>Created with QRCodeSVG v' . self::VERSION . ' by Jan Neuhaus, VAYA Consulting</metadata>';

        if ($fg_transparent && $bg_transparent) {
            $buffer .= '<rect x="0" y="0" width="' . $blocks . '" height="' . $blocks . '" fill="#000" style="opacity:0;"/>';
        } else {
            // Route to appropriate renderer
            if ($usePathRendering) {
                $buffer .= $this->renderWithPath($blocks, $fg, $bg, $fg_transparent, $bg_transparent, $variation, $height_overlap, $borderWidth, $borderRadiusPercent, $width, $alt_text, $draw_background_only);
            } else {
                $buffer .= $this->renderWithElements($blocks, $fg, $bg, $fg_transparent, $bg_transparent, $variation, $height_overlap, $width, $alt_text, $draw_background_only, $borderWidth, $borderRadiusPercent);
            }
        }
        $buffer .= "</svg>";
        return $buffer;
    }

    private function renderWithElements($blocks, $fg, $bg, $fg_transparent, $bg_transparent, $variation, $height_overlap, $width, $alt_text, $draw_background_only = false, $borderWidth = 5, $borderRadiusPercent = 30) {
        $buffer = '';
        
        // Only add defs if we're not using transparent foreground (which uses paths instead of rect elements)
        if (!$fg_transparent || $bg_transparent || !$draw_background_only) {
            $buffer .= '<defs><rect id="p" width="1" height="'.(1+$height_overlap).'"/></defs>';
        }

        // Calculate offset for QR code elements when we have a border
        $offsetX = 0;
        $offsetY = 0;
        if ($draw_background_only) {
            $offsetX = $borderWidth;
            $offsetY = $borderWidth;
        }

        // Create background path data for transparent foreground case
        $bgPathData = '';
        if ($draw_background_only) {
            $radius = $borderWidth * $borderRadiusPercent / 100;
            $x1 = 0;
            $y1 = 0;
            $x2 = $blocks + 2 * $borderWidth;
            $y2 = $blocks + 2 * $borderWidth;
            
            // Create rounded rectangle path for background
            $bgPathData .= "M " . ($x1 + $radius) . "," . $y1;
            $bgPathData .= " L " . ($x2 - $radius) . "," . $y1;
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . $x2 . "," . ($y1 + $radius);
            $bgPathData .= " L " . $x2 . "," . ($y2 - $radius);
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . ($x2 - $radius) . "," . $y2;
            $bgPathData .= " L " . ($x1 + $radius) . "," . $y2;
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . $x1 . "," . ($y2 - $radius);
            $bgPathData .= " L " . $x1 . "," . ($y1 + $radius);
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . ($x1 + $radius) . "," . $y1;
            $bgPathData .= " Z ";
        }

        if (!$bg_transparent && !$draw_background_only) {
            // Hintergrund als komplettes Rechteck zeichnen (nur wenn nicht background-only)
            $bg_color_raw = $bg;
            $bg_color_info = $this->convertColorToSVG($bg_color_raw, true);
            $styleAttr = $bg_color_info['opacity'] !== null ? ' style="opacity: ' . $bg_color_info['opacity'] . ';"' : '';
            if (strpos(strtolower($variation), 'dotted')  || strpos(strtolower($variation), 'rounded') ) {
                $buffer .= "<rect id=\"bg\" rx=\"0.5\" x=\"$offsetX\" y=\"$offsetY\" width=\"$blocks\" height=\"$blocks\" fill=\"" . $bg_color_info['color'] . "\" shape-rendering=\"crispEdges\"" . $styleAttr . "></rect>";
            } else {
                $buffer .= "<rect id=\"bg\" x=\"$offsetX\" y=\"$offsetY\" width=\"$blocks\" height=\"$blocks\" fill=\"" . $bg_color_info['color'] . "\" shape-rendering=\"crispEdges\"" . $styleAttr . "></rect>";
            }
        }
        if (!$fg_transparent || $draw_background_only) {
            if ($draw_background_only) {
                // For transparent foreground: create outer box with holes for QR code elements (same as Path variant)
                $bg_color_raw = $bg;
                $bg_color_info = $this->convertColorToSVG($bg_color_raw, true);
                
                // Create outer box path
                $outerBoxPath = $bgPathData;
                
                // Add QR code elements as holes (subtract from outer box)
                $holePathData = '';
                for ($r = 0; $r < $blocks; $r++) {
                    $c_start = 0;
                    $last = $this->isDark($r, 0);
                    if ($r==$blocks-1) {
                        $height_overlap = 0;
                    }
                    for ($c = 1; $c < $blocks; $c++) {
                        $akt = $this->isDark($r, $c);
                        if (strpos(strtolower($variation), 'dotted') !== false) {
                            if ($last) {
                                $holePathData .= $this->circleToPath($c - 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                                $last = false;
                            }
                            if ($akt) {
                                $holePathData .= $this->circleToPath($c + 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                            }
                        } else {
                            if ($last && !$akt) {
                                $holePathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                            } elseif ($akt && !$last) {
                                $c_start = $c;
                            }
                            $last = $akt;
                        }
                    }
                    if ($last) {
                        $holePathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                    }
                }
                
                // Combine outer box with holes using fill-rule="evenodd"
                $combinedPathData = $outerBoxPath . $holePathData;
                $styleAttr = $bg_color_info['opacity'] !== null ? ' style="opacity: ' . $bg_color_info['opacity'] . ';"' : '';
                $buffer .= '<path d="' . $combinedPathData . '" fill="' . $bg_color_info['color'] . '" fill-rule="evenodd" shape-rendering="crispEdges"' . $styleAttr . '/>';
            } else {
                // Normal case: draw foreground elements
                $fill_color_raw = $draw_background_only ? $bg : $fg;
                $fill_color_info = $this->convertColorToSVG($fill_color_raw, true);
                $styleAttr = $fill_color_info['opacity'] !== null ? ' style="opacity: ' . $fill_color_info['opacity'] . ';"' : '';
                $buffer .= "<g fill=\"" . $fill_color_info['color'] . "\" shape-rendering=\"crispEdges\"" . $styleAttr . ">";
                for ($r = 0; $r < $blocks; $r++) {
                    $c_start = 0;
                    $last = $this->isDark($r, 0);
                    if ($r==$blocks-1) {
                        $height_overlap = 0;
                    }
                    for ($c = 1; $c < $blocks; $c++) {
                        $akt = $this->isDark($r, $c);
                        if (strpos(strtolower($variation), 'dotted') !== false) {
                            if ($last) {
                                $buffer .= '<circle cx="' . ($c - 0.5 + $offsetX) . '" cy="' . ($r + 0.5 + $offsetY) . '" r="0.5"></circle>';
                                $last = false;
                            }
                            if ($akt) {
                                $buffer .= '<circle cx="' . ($c + 0.5 + $offsetX) . '" cy="' . ($r + 0.5 + $offsetY) . '" r="0.5"></circle>';
                            }
                        } else {
                            if ($last && !$akt) {
                                $this->drawBlock($buffer,$variation,$blocks,$c,$c_start,$r,$fg,$height_overlap,$offsetX,$offsetY);
                            } elseif ($akt && !$last) {
                                $c_start = $c;
                            }
                            $last = $akt;
                        }
                    }
                    if ($last) {
                        $this->drawBlock($buffer,$variation,$blocks,$c,$c_start,$r,$fg,$height_overlap,$offsetX,$offsetY);
                    }
                }
                $buffer .="</g>";
            }
        }
        return $buffer;
    }

    private function renderWithPath($blocks, $fg, $bg, $fg_transparent, $bg_transparent, $variation, $height_overlap, $borderWidth, $borderRadiusPercent, $width, $alt_text, $draw_background_only = false) {
        $buffer = '';
        $bgPathData = '';
        $fgPathData = '';
        
        // Add rounded border rectangle if foreground was originally transparent
        if ($draw_background_only) {
            $radius = $borderWidth * $borderRadiusPercent / 100;
            $x1 = 0;
            $y1 = 0;
            $x2 = $blocks + 2 * $borderWidth;
            $y2 = $blocks + 2 * $borderWidth;
            
            // Create rounded rectangle path for background
            $bgPathData .= "M " . ($x1 + $radius) . "," . $y1;
            $bgPathData .= " L " . ($x2 - $radius) . "," . $y1;
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . $x2 . "," . ($y1 + $radius);
            $bgPathData .= " L " . $x2 . "," . ($y2 - $radius);
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . ($x2 - $radius) . "," . $y2;
            $bgPathData .= " L " . ($x1 + $radius) . "," . $y2;
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . $x1 . "," . ($y2 - $radius);
            $bgPathData .= " L " . $x1 . "," . ($y1 + $radius);
            $bgPathData .= " A " . $radius . "," . $radius . " 0 0,1 " . ($x1 + $radius) . "," . $y1;
            $bgPathData .= " Z ";
        }

        // Add background rectangle if not transparent or if we need to draw background
        if (!$bg_transparent && !$draw_background_only) {
            $bgPathData .= "M 0,0 L " . $blocks . ",0 L " . $blocks . "," . $blocks . " L 0," . $blocks . " Z ";
        }

        // Add foreground elements
        if (!$fg_transparent || $draw_background_only) {
            $fill_color = $draw_background_only ? $bg : $fg;
            
            // Calculate offset for QR code elements when we have a border
            $offsetX = 0;
            $offsetY = 0;
            if ($draw_background_only) {
                $offsetX = $borderWidth;
                $offsetY = $borderWidth;
            }
            
            for ($r = 0; $r < $blocks; $r++) {
                $c_start = 0;
                $last = $this->isDark($r, 0);
                if ($r==$blocks-1) {
                    $height_overlap = 0;
                }
                for ($c = 1; $c < $blocks; $c++) {
                    $akt = $this->isDark($r, $c);
                    if (strpos(strtolower($variation), 'dotted') !== false) {
                        if ($last) {
                            $fgPathData .= $this->circleToPath($c - 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                            $last = false;
                        }
                        if ($akt) {
                            $fgPathData .= $this->circleToPath($c + 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                        }
                    } else {
                        if ($last && !$akt) {
                            $fgPathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                        } elseif ($akt && !$last) {
                            $c_start = $c;
                        }
                        $last = $akt;
                    }
                }
                if ($last) {
                    $fgPathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                }
            }
        }

        // For transparent foreground with border, create outer box and cut holes for QR elements
        if ($draw_background_only) {
            $bg_color_raw = $bg;
            $bg_color_info = $this->convertColorToSVG($bg_color_raw, true);
            
            // Create outer box path
            $outerBoxPath = $bgPathData;
            
            // Add QR code elements as holes (subtract from outer box)
            $holePathData = '';
            for ($r = 0; $r < $blocks; $r++) {
                $c_start = 0;
                $last = $this->isDark($r, 0);
                if ($r==$blocks-1) {
                    $height_overlap = 0;
                }
                for ($c = 1; $c < $blocks; $c++) {
                    $akt = $this->isDark($r, $c);
                    if (strpos(strtolower($variation), 'dotted') !== false) {
                        if ($last) {
                            $holePathData .= $this->circleToPath($c - 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                            $last = false;
                        }
                        if ($akt) {
                            $holePathData .= $this->circleToPath($c + 0.5 + $offsetX, $r + 0.5 + $offsetY, 0.5);
                        }
                    } else {
                        if ($last && !$akt) {
                            $holePathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                        } elseif ($akt && !$last) {
                            $c_start = $c;
                        }
                        $last = $akt;
                    }
                }
                if ($last) {
                    $holePathData .= $this->blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX, $offsetY);
                }
            }
            
            // Combine outer box with holes using fill-rule="evenodd"
            $combinedPathData = $outerBoxPath . $holePathData;
            $styleAttr = $bg_color_info['opacity'] !== null ? ' style="opacity: ' . $bg_color_info['opacity'] . ';"' : '';
            $buffer .= '<path d="' . $combinedPathData . '" fill="' . $bg_color_info['color'] . '" fill-rule="evenodd" shape-rendering="crispEdges"' . $styleAttr . '/>';
        } else {
            // Output background path
            if ($bgPathData !== '') {
                $bg_color_raw = $bg;
                $bg_color_info = $this->convertColorToSVG($bg_color_raw, true);
                $styleAttr = $bg_color_info['opacity'] !== null ? ' style="opacity: ' . $bg_color_info['opacity'] . ';"' : '';
                $buffer .= '<path d="' . $bgPathData . '" fill="' . $bg_color_info['color'] . '" shape-rendering="crispEdges"' . $styleAttr . '/>';
            }

            // Output foreground path
            if ($fgPathData !== '') {
                $fg_color_raw = $draw_background_only ? ($original_bg_color ?: $bg) : $fg;
                $fg_color_info = $this->convertColorToSVG($fg_color_raw, true);
                $styleAttr = $fg_color_info['opacity'] !== null ? ' style="opacity: ' . $fg_color_info['opacity'] . ';"' : '';
                $buffer .= '<path d="' . $fgPathData . '" fill="' . $fg_color_info['color'] . '" shape-rendering="crispEdges"' . $styleAttr . '/>';
            }
        }

        return $buffer;
    }

    private function circleToPath($cx, $cy, $r) {
        return "M " . ($cx - $r) . "," . $cy . 
               " A " . $r . "," . $r . " 0 0,1 " . ($cx + $r) . "," . $cy .
               " A " . $r . "," . $r . " 0 0,1 " . ($cx - $r) . "," . $cy . " Z ";
    }

    private function blockToPath($variation, $blocks, $c, $c_start, $r, $offsetX = 0, $offsetY = 0) {
        $len = $c - $c_start;
        $x1 = $c_start + $offsetX;
        $x2 = $c + $offsetX;
        $y1 = $r + $offsetY;
        $y2 = $r + 1 + $offsetY;

        if (strpos(strtolower($variation), 'rounded') !== false) {
            // Handle rounded corners logic
            $upleft = true;
            $upright = true;
            if ($r == 0) {
                $upleft = false;
                $upright = false;
            } else {
                $dark = $this->isDark($r-1, $c_start);
                if (!$dark) $upleft = false;
                
                $dark = $this->isDark($r-1, $c-1);
                if (!$dark) $upright = false;
            }

            $downleft = true;
            $downright = true;
            if ($r == $blocks-1) {
                $downleft = false;
                $downright = false;
            } else {
                $dark = $this->isDark($r+1, $c_start);
                if (!$dark) $downleft = false;
                
                $dark = $this->isDark($r+1, $c-1);
                if (!$dark) $downright = false;
            }

            return $this->roundedBlockToPath($x1, $y1, $x2, $y2, $upleft, $upright, $downleft, $downright);
        } else {
            // Simple rectangle
            return "M $x1,$y1 L $x2,$y1 L $x2,$y2 L $x1,$y2 Z ";
        }
    }

    private function roundedBlockToPath($x1, $y1, $x2, $y2, $upleft, $upright, $downleft, $downright) {
        if (!$upleft && !$upright && !$downleft && !$downright) {
            // No connections - full rounded rectangle
            return "M $x1," . ($y1 + 0.5) . 
                   " A 0.5,0.5 0 0,0 " . ($x1+0.5) . ",$y2" . 
                   " L " . ($x2 - 0.5) . ",$y2" .
                   " A 0.5,0.5 0 0,0 $x2," . ($y1 + 0.5) .
                   " A 0.5,0.5 0 0,0 " . ($x2 - 0.5) . ",$y1" .
                   " L ".($x1+0.5).",$y1" .
                   " A 0.5,0.5 0 0,0 $x1," . ($y1 + 0.5) . " Z ";
        } elseif ($upleft && $upright && $downleft && $downright) {
            // All connections - simple rectangle
            return "M $x1,$y1 L $x2,$y1 L $x2,$y2 L $x1,$y2 Z ";
        } else {
            // Complex rounded rectangle with selective corners
            $path = "M $x1," . ($y1 + 0.5);
            
            // Top edge
            if (!$upleft) {
                $path .= " A 0.5,0.5 0 0,1 " . ($x1 + 0.5) . ",$y1";
            } else {
                $path .= " L $x1,$y1";
            }
            
            if (!$upright) {
                $path .= " L " . ($x2 - 0.5) . ",$y1 A 0.5,0.5 0 0,1 $x2," . ($y1 + 0.5);
            } else {
                $path .= " L $x2,$y1 L $x2," . ($y1 + 0.5);
            }
            
            // Right edge
            if (!$downright) {
                $path .= " A 0.5,0.5 0 0,1 " . ($x2 - 0.5) . ",$y2";
            } else {
                $path .= " L $x2,$y2";
            }
            
            if (!$downleft) {
                $path .= " L " . ($x1 + 0.5) . ",$y2 A 0.5,0.5 0 0,1 $x1," . ($y1 + 0.5);
            } else {
                $path .= " L $x1,$y2 L $x1," . ($y1 + 0.5);
            }
            
            $path .= " Z ";
            return $path;
        }
    }

    private function drawBlock(&$buffer, $variation, $blocks, $c, $c_start, $r, $fg, $height_overlap = 0.005, $offsetX = 0, $offsetY = 0) {
        $len = $c-$c_start;
        if (strpos(strtolower($variation), 'rounded') !== false) {
            // Use the same logic as blockToPath for consistency
            $upleft = true;
            $upright = true;
            if ($r == 0) {
                $upleft = false;
                $upright = false;
            } else {
                $dark = $this->isDark($r-1, $c_start);
                if (!$dark) $upleft = false;
                
                $dark = $this->isDark($r-1, $c-1);
                if (!$dark) $upright = false;
            }

            $downleft = true;
            $downright = true;
            if ($r == $blocks-1) {
                $downleft = false;
                $downright = false;
            } else {
                $dark = $this->isDark($r+1, $c_start);
                if (!$dark) $downleft = false;
                
                $dark = $this->isDark($r+1, $c-1);
                if (!$dark) $downright = false;
            }

            $x1 = $c_start + $offsetX;
            $x2 = $c + $offsetX;
            $y1 = $r + $offsetY;
            $y2 = $r+1+$height_overlap + $offsetY;
            
            // Use the same roundedBlockToPath logic
            $pathData = $this->roundedBlockToPath($x1, $y1, $x2, $y2, $upleft, $upright, $downleft, $downright);
            $buffer .= '<path d="' . $pathData . '" fill="' . $fg . '"/>';
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
                $buffer .= '<use href="#p" x="' . ($c_start + $offsetX) . '" y="' . ($r + $offsetY) . '"/>';
            } else {
                $buffer .= '<rect x="' . ($c_start + $offsetX) . '" y="' . ($r + $offsetY) . '" width="'.$len.'" height="1"/>';
            }
        }
    }
}