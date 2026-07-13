@php
    $browserLogo = \App\Support\MediaPath::url(config('app.brand_logo_path'));
@endphp

<link rel="icon" type="image/png" href="{{ $browserLogo }}">
<link rel="apple-touch-icon" href="{{ $browserLogo }}">
