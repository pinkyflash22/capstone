@php
    $logoClass = $class ?? '';
    $logoAlt = $alt ?? 'PyroSense logo';
    $logoSrc = \App\Support\MediaPath::url(config('app.brand_logo_path')) ?: asset('logo.png');
@endphp

<img
    src="{{ $logoSrc }}"
    alt="{{ $logoAlt }}"
    class="{{ $logoClass }}"
    style="width:100%;height:100%;object-fit:contain;display:block;"
>
