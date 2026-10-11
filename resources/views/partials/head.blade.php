<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">
<title>@hasSection('title')@yield('title') | @endif{{ config('dealer.name') }}</title>
<meta name="description" content="@yield('meta_description', config('dealer.tagline') ?: config('dealer.name').': jual mobil baru dan bekas, promo, dan simulasi kredit.')">
@php
    // Admin, akun, login/daftar, dan bandingkan tidak perlu muncul di mesin pencari.
    $noindex = ($noindex ?? false) || request()->is('admin', 'admin/*', 'akun', 'akun/*', 'bandingkan');
@endphp
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    {{-- Canonical tanpa query string: katalog yang difilter/diurutkan tidak dianggap halaman ganda. --}}
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('dealer.name') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@hasSection('title')@yield('title') | @endif{{ config('dealer.name') }}">
    <meta property="og:description" content="@yield('meta_description', config('dealer.tagline') ?: config('dealer.name').': jual mobil baru dan bekas, promo, dan simulasi kredit.')">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- Gambar pratinjau saat tautan dibagikan; halaman tanpa foto memakai gambar bawaan 1200×630. --}}
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta name="twitter:card" content="summary_large_image">
@endif
@stack('meta')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="{{ \App\Support\Asset::url('css/app.css') }}" rel="stylesheet">
@stack('styles')
