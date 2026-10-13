@extends('layouts.admin')

@section('title', 'Rating Saya')

@use('App\Models\MechanicRating')
@use('App\Models\Testimonial')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Rating Saya']]])
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <p class="small text-muted mb-1">Rata-rata rating</p>
                    <p class="display-6 fw-bold mb-1">{{ MechanicRating::formatAverage($average !== null ? (float) $average : null) }}</p>
                    <p class="small text-muted mb-3">
                        dari {{ $ratings->total() }} rating, {{ $completedJobs }} servis selesai
                    </p>

                    @php($totalRatings = max(1, $ratings->total()))
                    @for ($star = Testimonial::MAX_RATING; $star >= Testimonial::MIN_RATING; $star--)
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <span class="text-nowrap rating-share-label">{{ $star }} <i class="bi bi-star-fill text-warning" aria-hidden="true"></i></span>
                            <span class="flex-grow-1 rating-share" aria-hidden="true">
                                <span style="width: {{ round(($distribution[$star] ?? 0) / $totalRatings * 100) }}%"></span>
                            </span>
                            <span class="text-muted text-end rating-share-count">{{ $distribution[$star] ?? 0 }}</span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                @if ($ratings->isEmpty())
                    <x-empty-state icon="bi-star" title="Belum ada rating"
                                   message="Customer bisa memberi rating setelah servis yang Anda kerjakan selesai." />
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($ratings as $rating)
                            <div class="list-group-item p-3">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <x-rating-stars :rating="$rating->rating" class="text-warning" />
                                    <span class="fw-semibold small">{{ Testimonial::publicName($rating->user->name) }}</span>
                                    <span class="small text-muted ms-auto">{{ $rating->created_at->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="small text-muted">{{ $rating->booking->service->name }}, {{ $rating->booking->plate_number }}</div>
                                @if ($rating->comment)
                                    <p class="mb-0 mt-2">“{{ $rating->comment }}”</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @include('admin.partials.pagination', ['paginator' => $ratings])
                @endif
            </div>
        </div>
    </div>
@endsection
