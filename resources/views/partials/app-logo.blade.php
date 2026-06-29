@php
    $logoClass = $class ?? '';
    $logoAlt = $alt ?? 'PyroSense logo';
@endphp

<img
    src="{{ \App\Support\MediaPath::url(config('app.brand_logo_path')) }}"
    alt="{{ $logoAlt }}"
    class="{{ $logoClass }}"
    style="width:100%;height:100%;object-fit:contain;display:block;"
>
