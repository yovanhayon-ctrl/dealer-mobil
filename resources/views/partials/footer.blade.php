@php($dealer = config('dealer'))

<footer class="site-footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <h5 class="mb-2"><i class="bi bi-car-front-fill text-accent"></i> {{ $dealer['name'] }}</h5>
                @if ($dealer['tagline'])
                    <p class="mb-0">{{ $dealer['tagline'] }}</p>
                @endif
            </div>

            <div class="col-sm-6 col-lg-4">
                <h5 class="fs-6 text-uppercase mb-3">Kontak</h5>
                <ul class="list-unstyled small mb-0">
                    @if ($dealer['address'])
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $dealer['address'] }}</li>
                    @endif
                    @if ($dealer['phone'])
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>{{ $dealer['phone'] }}</li>
                    @endif
                    @if ($dealer['whatsapp'])
                        <li class="mb-2">
                            <i class="bi bi-whatsapp me-2"></i>
                            <a href="https://wa.me/{{ $dealer['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>
                        </li>
                    @endif
                    @if ($dealer['email'])
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2"></i>
                            <a href="mailto:{{ $dealer['email'] }}">{{ $dealer['email'] }}</a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="col-sm-6 col-lg-3">
                <h5 class="fs-6 text-uppercase mb-3">Jam Operasional</h5>
                <p class="small mb-0"><i class="bi bi-clock me-2"></i>{{ $dealer['hours'] ?: '-' }}</p>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <p class="small text-center mb-0">&copy; {{ now()->year }} {{ $dealer['name'] }}. Hak cipta dilindungi.</p>
    </div>
</footer>
