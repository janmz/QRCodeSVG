<?php

require __DIR__ . '/../vendor/autoload.php';

function buildQRCodeSVG($text, $width, $fg, $bg, $variation, $ecl = QR_ERROR_CORRECT_LEVEL_H) {
	$mode = QRUtil::getMode($text);
	$qr = new QRCodeSVG();
	$qr->setErrorCorrectLevel($ecl);
	$qr->addData($text, $mode);
	$length = $qr->getData(0)->getLength();
	for ($typeNumber = 1; $typeNumber <= 40; $typeNumber++) {
		if ($length <= QRUtil::getMaxLength($typeNumber, $mode, $ecl)) {
			$qr->setTypeNumber($typeNumber);
			break;
		}
	}
	$qr->make();
	$alt = $text . ' (' . $variation . ')';
	return $qr->getSVG($width, $fg, $bg, $alt, $variation);
}

$text = 'Test von QRCode SVG';
$width = 256;
$configs = array(
	array('label' => 'normal',  'fg' => '#1d4ed8a0', 'bg' => '#ffffff00'),
	array('label' => 'dotted',  'fg' => '#dc2626e0', 'bg' => '#00000030'),
	array('label' => 'rounded', 'fg' => '#048659ff', 'bg' => '#ffff00ff'),
);
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>QRCode SVG Varianten – Test</title>
	<style>
		body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; margin: 24px; color: #111; }
		h1 { font-size: 20px; margin: 0 0 4px; }
		p { margin: 0 0 16px; color: #444; }
		.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
		.card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #fff; }
		.card h2 { font-size: 16px; margin: 0 0 8px; }
		.qr { display: flex; align-items: center; justify-content: center; padding: 8px; background: #fafafa; border: 1px solid #f3f4f6; border-radius: 6px; }
		.meta { margin-top: 8px; font-size: 12px; color: #555; }
		.meta code { background: #f3f4f6; padding: 2px 4px; border-radius: 4px; }
		.donate { margin: 20px 0; min-height:250px; padding: 12px 16px; background: #B38CC01C; border: 1px solid #700096; border-radius: 8px; }
		.donate a { color: #700096; font-weight: 600; text-decoration: none; }
		.donate a:hover { text-decoration: underline; }
		.qrcode { float: right;margin:0 0 1em 1em;text-align:center;}
	</style>
</head>
<body>
	<h1>QRCode SVG – Varianten</h1>
	<p>Text: <strong><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></strong></p>
	<div class="donate">
		<div class="qrcode"><?php
			$data = "BCD\n002\n2\nSCT\nBFSWDE33XXX\nCFI Int. Kinderhilfe Deutschland\nDE65370205000008753503\nEUR15.00\nCHAR\n\nSpende für SVGVAYA Donationware\n";
			$qr = QRCodeSVG::getMinimumQRCode($data, QR_ERROR_CORRECT_LEVEL_M); // Für Giro QR Codes verpflichtend
			echo $qr->getSVG(200, "#700096", "#B38CC01C", "Giro QR Code für Spende an CFI","rounded");
			?></br>
			<p>QR Code für Banking-App</p>
		</div>
		<p>Als kleines Dankeschön freuen wir uns über eine Spende an die
		<a href="https://cfi-kinderhilfe.de/jetzt-spenden/?q=SVGVAYA" alt="Verweis auf Spendenseit von CFI" target="_blank" rel="noopener noreferrer">CFI‑Kinderhilfe</a>.
		</p>
		<p>Dazu kann auch der rechts stehende QR-Code mit einer Banking-App als Fotoüberweisung eingelesen werden.</p>
		<p>Wichtig ist nur, dass die Kennung SVGVAYA als im Hinweisfeld bzw. dem Verwendungszweck angegeben wird.</p>
	</div>
	<div class="grid">
		<?php foreach ($configs as $cfg): ?>
			<div class="card">
				<h2>Variante: <?= htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8') ?></h2>
				<div class="qr">
					<?php $code = buildQRCodeSVG($text, $width, $cfg['fg'], $cfg['bg'], $cfg['label']);  echo $code; ?>
				</div>
				<div class="meta">
					Farbe: <code><?= htmlspecialchars($cfg['fg'], ENT_QUOTES, 'UTF-8') ?></code> &nbsp; Hintergrund: <code><?= htmlspecialchars($cfg['bg'], ENT_QUOTES, 'UTF-8') ?></code>
				</div>
				<div class="code">
					Beginn der SVG-Definition: <code><?= htmlspecialchars(mb_substr($code, 0, 512) . '…', ENT_QUOTES, 'UTF-8') ?></code>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</body>
</html>


