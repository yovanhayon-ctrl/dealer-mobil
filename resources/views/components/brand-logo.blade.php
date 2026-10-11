@props(['size' => 36])

{{-- Logo JAF: versi kecil dari public/images/LOGO.png. Nama dealer selalu tertulis di sebelahnya, jadi alt kosong. --}}
<img src="{{ asset('images/logo-128.webp') }}" alt="" width="{{ $size }}" height="{{ $size }}" {{ $attributes->class('brand-logo') }}>
