<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require dirname(__DIR__).'/vendor/autoload.php';

$source = __DIR__.'/PyroSense-Prediction-Risk-Mitigation-Methodology.html';
$destination = __DIR__.'/PyroSense-Prediction-Risk-Mitigation-Methodology.pdf';

$options = new Options;
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml((string) file_get_contents($source), 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($destination, $dompdf->output());

echo $destination.PHP_EOL;
echo 'Pages: '.$dompdf->getCanvas()->get_page_count().PHP_EOL;
echo 'Bytes: '.filesize($destination).PHP_EOL;
