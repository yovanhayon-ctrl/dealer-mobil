{{--
    Kerangka halaman Kebijakan Privasi & Syarat Ketentuan: judul, tanggal berlaku, daftar isi, isi bernomor, kontak.
    Halaman anak mengisi @section('legal_title'), $sections (id => judul) untuk daftar isi, dan @section('legal_body').
--}}
@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <article class="legal-page mx-auto">
            @include('partials.public-breadcrumb', ['items' => [['label' => $pageTitle]]])

            <header class="mb-4">
                <h1 class="h2 mb-2">{{ $pageTitle }}</h1>
                <p class="text-muted mb-0">Berlaku sejak {{ $effectiveDate }}</p>
            </header>

            <nav class="legal-toc mb-5" aria-labelledby="legal-toc-title">
                <h2 id="legal-toc-title" class="legal-toc-title">Daftar isi</h2>
                <ol class="mb-0">
                    @foreach ($sections as $id => $title)
                        <li><a href="#{{ $id }}">{{ $title }}</a></li>
                    @endforeach
                </ol>
            </nav>

            <div class="legal-body">
                @yield('legal_body')
            </div>

            <section class="legal-contact mt-5" aria-labelledby="legal-contact-title">
                <h2 id="legal-contact-title" class="h5 mb-2">Ada pertanyaan?</h2>
                <p class="mb-2">Hubungi {{ $dealer['name'] }}:</p>
                <ul class="list-unstyled mb-0">
                    @if ($dealer['email'])
                        <li>Email: <a href="mailto:{{ $dealer['email'] }}">{{ $dealer['email'] }}</a></li>
                    @endif
                    @if ($whatsappUrl)
                        <li>WhatsApp: <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Chat dengan tim kami</a></li>
                    @endif
                    @if ($dealer['address'])
                        <li>Alamat: {{ $dealer['address'] }}</li>
                    @endif
                    <li><a href="{{ route('contact') }}">Halaman Kontak</a></li>
                </ul>
            </section>
        </article>
    </div>
@endsection
