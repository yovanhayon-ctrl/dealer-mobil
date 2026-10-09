{{-- Bintang rating (hanya tampilan): <x-rating-stars :rating="4" /> --}}
@props(['rating'])

@php($rating = max(0, min(\App\Models\Testimonial::MAX_RATING, (int) $rating)))

<span {{ $attributes->class(['rating-stars text-nowrap']) }} role="img" aria-label="Rating {{ $rating }} dari {{ \App\Models\Testimonial::MAX_RATING }}">
    @for ($i = 1; $i <= \App\Models\Testimonial::MAX_RATING; $i++)
        <i class="bi {{ $i <= $rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
    @endfor
</span>
