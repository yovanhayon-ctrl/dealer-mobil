{{--
    Pita bantuan penutup halaman (selebar layar, menyambung ke footer): @include('partials.help-band', ['title' => '…'])
    Satu tombol utama: WhatsApp, atau Kontak bila nomor WhatsApp kosong; nomor telepon sebagai tautan.
--}}
@php
    $dealer = config('dealer');
    $bandPhoneHref = $dealer['phone'] ? 'tel:'.preg_replace('/[^0-9+]/', '', $dealer['phone']) : null;
@endphp

<section class="home-cta-band" aria-labelledby="cta-title">
    <div class="container d-lg-flex align-items-center justify-content-between gap-4">
        <div class="mb-4 mb-lg-0">
            <h2 id="cta-title" class="h3 text-white mb-2">{{ $title ?? 'Butuh bantuan memilih mobil?' }}</h2>
            <p class="mb-0">
                @if ($dealer['hours'])
                    Tim kami siap membantu {{ $dealer['hours'] }}.
                @else
                    Tim kami siap membantu lewat WhatsApp atau telepon.
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3 gap-lg-4 flex-shrink-0">
            @if ($dealer['whatsapp'])
                <a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-accent btn-lg">
                    <i class="bi bi-whatsapp"></i>Chat WhatsApp
                </a>
            @elseif (Route::has('contact'))
                <a href="{{ route('contact') }}" class="btn btn-accent btn-lg">Hubungi Kami</a>
            @endif
            @if ($bandPhoneHref)
                <a href="{{ $bandPhoneHref }}" class="home-cta-phone"><i class="bi bi-telephone me-2"></i>{{ $dealer['phone'] }}</a>
            @endif
        </div>
    </div>
</section>
