@php
    $pwaLogo = $pwaLogo ?? 'storage/defaults/pyrosense.png';
    $pwaThemeColor = $pwaThemeColor ?? '#d14b24';
@endphp

<link rel="icon" type="image/png" href="{{ asset($pwaLogo) }}">
<link rel="apple-touch-icon" href="{{ asset($pwaLogo) }}">
<meta name="theme-color" content="{{ $pwaThemeColor }}">

@pwaHead($pwaLogo, $pwaThemeColor)
