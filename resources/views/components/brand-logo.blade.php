@props(['size' => 36])

{{-- Logo JAF: versi kecil (bulat, latar transparan) dari public/images/LOGO.png. Nama dealer selalu tertulis di sebelahnya, jadi alt kosong. --}}
<img src="{{ \App\Support\Asset::url('images/logo-128.webp') }}" alt="" width="{{ $size }}" height="{{ $size }}" {{ $attributes->class('brand-logo') }}>
