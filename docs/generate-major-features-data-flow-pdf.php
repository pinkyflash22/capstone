<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require dirname(__DIR__).'/vendor/autoload.php';

$source = __DIR__.'/PyroSense-Major-Features-Data-Flow.html';
$destination = __DIR__.'/PyroSense-Major-Features-Data-Flow.pdf';

$options = new Options;
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml((string) file_get_contents($source), 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvas->page_text(515, 806, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 7, [0.58, 0.64, 0.72]);

file_put_contents($destination, $dompdf->output());

echo $destination.PHP_EOL;
echo 'Pages: '.$canvas->get_page_count().PHP_EOL;
echo 'Bytes: '.filesize($destination).PHP_EOL;
