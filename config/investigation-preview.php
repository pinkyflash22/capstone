<?php

$localAppData = getenv('LOCALAPPDATA')
    ?: ((getenv('USERPROFILE') ?: '') !== '' ? getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local' : '');
$localSoffice = $localAppData !== ''
    ? $localAppData . DIRECTORY_SEPARATOR . 'PyroSenseTools' . DIRECTORY_SEPARATOR . 'LibreOffice'
        . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'soffice.com'
    : '';
$bundledLibreOfficeAvailable = $localSoffice !== '' && is_file($localSoffice);
$localProgramDirectory = $bundledLibreOfficeAvailable ? dirname($localSoffice) : '';
$localPython = $localProgramDirectory !== '' ? $localProgramDirectory . DIRECTORY_SEPARATOR . 'python.exe' : '';
$listenerProfile = $localAppData !== ''
    ? $localAppData . DIRECTORY_SEPARATOR . 'PyroSenseTools' . DIRECTORY_SEPARATOR . 'LibreOfficePreviewProfile'
    : storage_path('framework/cache/libreoffice-preview-profile');

return [
    // Only a PDF conversion preserves floating logos, tab stops, and pagination.
    'driver' => env('INVESTIGATION_PREVIEW_DRIVER', 'libreoffice'),
    'soffice_binary' => env(
        'INVESTIGATION_PREVIEW_SOFFICE',
        $bundledLibreOfficeAvailable ? $localSoffice : 'libreoffice'
    ),
    'persistent' => env('INVESTIGATION_PREVIEW_PERSISTENT', true),
    'listener_host' => env('INVESTIGATION_PREVIEW_LISTENER_HOST', '127.0.0.1'),
    'listener_port' => (int) env('INVESTIGATION_PREVIEW_LISTENER_PORT', 2002),
    'listener_profile' => env('INVESTIGATION_PREVIEW_LISTENER_PROFILE', $listenerProfile),
    'uno_python_binary' => env(
        'INVESTIGATION_PREVIEW_UNO_PYTHON',
        $localPython !== '' && is_file($localPython) ? $localPython : 'python3'
    ),
];
