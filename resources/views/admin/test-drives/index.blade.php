@extends('layouts.admin')

@section('title', 'Test Drive')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Test Drive']]])
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.test-drives.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
                    <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama/email customer, mobil…">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_status" class="form-label small mb-1">Status</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\TestDrive::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_dari" class="form-label small mb-1">Jadwal dari</label>
                    <input type="date" id="filter_dari" name="dari" value="{{ $filters['dari'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_sampai" class="form-label small mb-1">Jadwal sampai</label>
                    <input type="date" id="filter_sampai" name="sampai" value="{{ $filters['sampai'] }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
                    <select id="filter_urut" name="urut" class="form-select form-select-sm">
                        <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru masuk</option>
                        <option value="jadwal" @selected($filters['urut'] === 'jadwal')>Jadwal terdekat</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                    @if ($hasFilters || $filters['urut'] !== 'terbaru')
                        <a href="{{ route('admin.test-drives.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($testDrives->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Test drive tidak ditemukan" message="Tidak ada test drive yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-calendar-x" title="Belum ada test drive" message="Booking test drive dari customer akan muncul di sini." />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Jadwal</th>
                            <th>Customer</th>
                            <th>Mobil</th>
                            <th>Status</th>
                            <th>Masuk</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($testDrives as $testDrive)
                            <tr>
                                <td class="text-nowrap">
                                    <div class="fw-semibold">{{ $testDrive->preferred_date->translatedFormat('d M Y') }}</div>
                                    <div class="small text-muted">{{ $testDrive->timeLabel() }} WIB</div>
                                </td>
                                <td>
                                    <div>{{ $testDrive->user->name }}</div>
                                    <div class="small text-muted">{{ $testDrive->user->email }}</div>
                                </td>
                                <td>{{ $testDrive->car->brand->name }} {{ $testDrive->car->name }} {{ $testDrive->car->year }}</td>
                                <td><x-status-badge :status="$testDrive->status" /></td>
                                <td class="text-nowrap small">{{ $testDrive->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.test-drives.show', $testDrive) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $testDrives])
        @endif
    </div>
@endsection
