@php
    $dealer = config('dealer');

    // Dua kolom tautan; hanya route yang sudah dibuat yang tampil.
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
            'about' => 'Tentang Kami',
            'contact' => 'Kontak',
        ],
    ];
    $phoneHref = $dealer['phone'] ? 'tel:'.preg_replace('/[^0-9+]/', '', $dealer['phone']) : null;
@endphp

<footer class="site-footer mt-5">
    <div class="container pt-5 pb-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <h2 class="h5 mb-2"><i class="bi bi-car-front-fill text-accent"></i> {{ $dealer['name'] }}</h2>
                @if ($dealer['tagline'])
                    <p class="mb-3">{{ $dealer['tagline'] }}</p>
                @endif
                @if ($dealer['whatsapp'])
                    <a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-success btn-sm footer-whatsapp">
                        <i class="bi bi-whatsapp"></i>Chat WhatsApp
                    </a>
                @endif
            </div>

            @foreach ($footerColumns as $title => $links)
                <div class="col-6 col-lg-2">
                    <h2 class="footer-title">{{ $title }}</h2>
                    <ul class="footer-links">
                        @foreach ($links as $route => $label)
                            @continue(! Route::has($route))
                            <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="col-lg-4">
                <h2 class="footer-title">Hubungi Kami</h2>
                <ul class="footer-contact">
                    @if ($dealer['address'])
                        <li><i class="bi bi-geo-alt"></i><span>{{ $dealer['address'] }}</span></li>
                    @endif
                    @if ($dealer['phone'])
                        <li><i class="bi bi-telephone"></i><a href="{{ $phoneHref }}">{{ $dealer['phone'] }}</a></li>
                    @endif
                    @if ($dealer['whatsapp'])
                        <li><i class="bi bi-whatsapp"></i><a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a></li>
                    @endif
                    @if ($dealer['email'])
                        <li><i class="bi bi-envelope"></i><a href="mailto:{{ $dealer['email'] }}">{{ $dealer['email'] }}</a></li>
                    @endif
                    <li>
                        <i class="bi bi-clock"></i>
                        <span><span class="d-block footer-muted">Jam Operasional</span>{{ $dealer['hours'] ?: '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">&copy; {{ now()->year }} {{ $dealer['name'] }}. Hak cipta dilindungi.</p>
            <p class="mb-0">
                {{-- Catatan data contoh hanya di luar production (situs klien tidak menampilkannya). --}}
                @unless (app()->environment('production'))
                    <span class="footer-muted me-3">Data kendaraan bersifat contoh.</span>
                @endunless
                <a href="#top">Kembali ke atas <i class="bi bi-arrow-up"></i></a>
            </p>
        </div>
    </div>
</footer>
