@php
    $dealer = config('dealer');

    // Kolom tautan; hanya route yang sudah dibuat yang tampil.
    $footerColumns = [
        'Jelajahi' => [
            'home' => 'Beranda',
            'cars.index' => 'Mobil',
            'promos.index' => 'Promo',
            'compare.index' => 'Bandingkan Mobil',
        ],
        'Layanan' => [
            'test-drives.create' => 'Test Drive',
            'services.index' => 'Servis',
            'credit.index' => 'Simulasi Kredit',
        ],
        'Perusahaan' => [
            'about' => 'Tentang Kami',
            'contact' => 'Kontak',
        ],
    ];
    $phoneHref = $dealer['phone'] ? 'tel:'.preg_replace('/[^0-9+]/', '', $dealer['phone']) : null;
@endphp

<footer class="site-footer mt-5">
    <div class="container pt-5 pb-4">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-3">
                <a href="{{ route('home') }}" class="footer-brand d-inline-flex align-items-center gap-2">
                    <x-brand-logo :size="44" /> {{ $dealer['name'] }}
                </a>
                <p class="footer-tagline">{{ $dealer['tagline'] ?: 'Nissan Heritage & Performance' }}</p>
                @if ($dealer['address'])
                    <p class="footer-address mb-0">{{ $dealer['address'] }}</p>
                @endif
            </div>

            @foreach ($footerColumns as $title => $links)
                <div class="col-6 col-md-3 col-lg-2">
                    <h2 class="footer-title">{{ $title }}</h2>
                    <ul class="footer-links">
                        @foreach ($links as $route => $label)
                            @continue(! Route::has($route))
                            <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="col-6 col-md-3 col-lg-3">
                <h2 class="footer-title">Hubungi</h2>
                <ul class="footer-links">
                    @if ($dealer['phone'])
                        <li><a href="{{ $phoneHref }}">{{ $dealer['phone'] }}</a></li>
                    @endif
                    @if ($dealer['whatsapp'])
                        <li><a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener">Chat WhatsApp</a></li>
                    @endif
                    @if ($dealer['email'])
                        <li><a href="mailto:{{ $dealer['email'] }}" class="text-break">{{ $dealer['email'] }}</a></li>
                    @endif
                    <li>
                        <span class="d-block footer-muted">Jam Operasional</span>
                        {{ $dealer['hours'] ?: '-' }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">&copy; {{ now()->year }} {{ $dealer['name'] }}. Hak cipta dilindungi.</p>
            <nav class="footer-legal" aria-label="Informasi hukum">
                <a href="{{ route('privacy') }}" class="footer-bottom-link">Kebijakan Privasi</a>
                <a href="{{ route('terms') }}" class="footer-bottom-link">Syarat &amp; Ketentuan</a>
                <a href="#top" class="footer-bottom-link">Kembali ke atas</a>
            </nav>
        </div>
    </div>
</footer>
