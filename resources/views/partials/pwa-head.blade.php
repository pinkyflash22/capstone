@php
    $pwaLogo = $pwaLogo ?? 'icon-192x192.png';
    $pwaThemeColor = $pwaThemeColor ?? '#d14b24';
    $browserLogo = \App\Support\MediaPath::url(config('app.brand_logo_path'));
@endphp

<link rel="icon" type="image/png" href="{{ $browserLogo }}">
<link rel="apple-touch-icon" href="{{ $browserLogo }}">
<meta name="theme-color" content="{{ $pwaThemeColor }}">

@pwaHead($pwaLogo, $pwaThemeColor)
