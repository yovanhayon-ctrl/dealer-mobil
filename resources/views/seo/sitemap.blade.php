{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url>
        <loc>{{ $url }}</loc>
    </url>
@endforeach
@foreach ($cars as $car)
    <url>
        <loc>{{ route('cars.show', $car) }}</loc>
        @if ($car->updated_at)
            <lastmod>{{ $car->updated_at->toAtomString() }}</lastmod>
        @endif
    </url>
@endforeach
@foreach ($promos as $promo)
    <url>
        <loc>{{ route('promos.show', $promo) }}</loc>
        @if ($promo->updated_at)
            <lastmod>{{ $promo->updated_at->toAtomString() }}</lastmod>
        @endif
    </url>
@endforeach
</urlset>
