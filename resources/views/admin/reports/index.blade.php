@extends('layouts.admin')

@use('App\Models\Car')
@use('App\Models\PurchaseRequest')
@use('App\Reports\AdminReport')
@use('App\Reports\ReportPeriod')

@section('title', 'Laporan')

@php
    $period = $report->period;
    $purchases = $report->purchaseSummary();
    $testDrives = $report->testDriveSummary();
    $byBrand = $report->salesByBrand();
    $byCategory = $report->salesByCategory();
    $topCars = $report->topCars();
    $lowStock = $report->lowStockCars();
    $rupiah = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $percentLabel = fn (float $value) => number_format($value, 1, ',', '.').'%';
@endphp

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Laporan']]])
@endsection

@section('content')
    {{-- Judul khusus saat dicetak. --}}
    <div class="d-none d-print-block mb-3">
        <h2 class="h4 mb-1">Laporan {{ config('dealer.name') }}</h2>
        <div>Periode: {{ $period->label() }}</div>
        <div class="small text-muted">Dicetak {{ now()->translatedFormat('j F Y H:i') }} WIB</div>
    </div>

    <div class="card mb-3 d-print-none">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                @foreach (ReportPeriod::PRESETS as $value => $label)
                    <a href="{{ route('admin.reports.index', ['periode' => $value]) }}"
                       @class(['btn btn-sm', 'btn-primary' => $period->preset === $value, 'btn-outline-primary' => $period->preset !== $value])>
                        {{ $label }}
                    </a>
                @endforeach

                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-print>
                        <i class="bi bi-printer"></i>Cetak
                    </button>
                    <a href="{{ route('admin.reports.export', $period->query()) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-filetype-csv"></i>Export CSV
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end" novalidate>
                <input type="hidden" name="periode" value="{{ ReportPeriod::CUSTOM }}">
                <div class="col-6 col-md-3">
                    <label for="report_dari" class="form-label small mb-1">Dari</label>
                    <input type="date" id="report_dari" name="dari" value="{{ old('dari', $period->from->toDateString()) }}"
                           @class(['form-control form-control-sm', 'is-invalid' => $errors->has('dari')])>
                </div>
                <div class="col-6 col-md-3">
                    <label for="report_sampai" class="form-label small mb-1">Sampai</label>
                    <input type="date" id="report_sampai" name="sampai" value="{{ old('sampai', $period->to->toDateString()) }}"
                           @class(['form-control form-control-sm', 'is-invalid' => $errors->has('sampai')])>
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                </div>
                @if ($errors->any())
                    <div class="col-12">
                        @foreach ($errors->all() as $message)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @endforeach
                        <div class="small text-muted">Menampilkan laporan bulan ini.</div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3 d-print-none">
        <h2 class="h5 mb-0">Periode: {{ $period->label() }}</h2>
        <span class="small text-muted">
            Terjual = pengajuan Disetujui/Selesai · pengajuan menurut tanggal pengajuan · test drive menurut tanggal jadwal (WIB)
        </span>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col"><x-stat-card label="Total Pengajuan" :value="$purchases['total']" icon="bi-file-earmark-text" color="navy" /></div>
        <div class="col"><x-stat-card label="Unit Terjual" :value="$purchases['units_sold']" icon="bi-bag-check" color="green" /></div>
        <div class="col"><x-stat-card label="Nilai Penjualan" :value="$rupiah($purchases['sales_value'])" icon="bi-cash-stack" color="red" /></div>
        <div class="col"><x-stat-card label="Test Drive Selesai" :value="$percentLabel($testDrives['completion_rate'])" icon="bi-calendar-check" color="blue" /></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100 report-card">
                <div class="card-header bg-transparent border-0 pt-3 px-3"><h3 class="h6 mb-0">Pengajuan per Status</h3></div>
                @if ($purchases['total'] === 0)
                    <x-empty-state icon="bi-file-earmark-text" title="Belum ada pengajuan" message="Tidak ada pengajuan pada periode ini." />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 admin-table">
                            <tbody>
                                @foreach ($purchases['statuses'] as $status => $count)
                                    @php($share = AdminReport::percent($count, $purchases['total']))
                                    <tr>
                                        <td style="width: 140px"><x-status-badge :status="$status" /></td>
                                        <td class="text-end" style="width: 60px">{{ $count }}</td>
                                        <td>@include('admin.reports._bar', ['value' => $share])</td>
                                        <td class="text-end small text-muted" style="width: 70px">{{ $percentLabel($share) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Total</td>
                                    <td class="text-end">{{ $purchases['total'] }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100 report-card">
                <div class="card-header bg-transparent border-0 pt-3 px-3"><h3 class="h6 mb-0">Metode Pembayaran</h3></div>
                <div class="card-body pt-2">
                    @if ($purchases['total'] === 0)
                        <p class="text-muted small mb-0">Tidak ada pengajuan pada periode ini.</p>
                    @else
                        @foreach ([PurchaseRequest::PAYMENT_CASH => $purchases['cash'], PurchaseRequest::PAYMENT_CREDIT => $purchases['credit']] as $method => $count)
                            @php($share = AdminReport::percent($count, $purchases['total']))
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ PurchaseRequest::PAYMENT_METHOD_LABELS[$method] }}</span>
                                <span>{{ $count }} · {{ $percentLabel($share) }}</span>
                            </div>
                            <div class="mb-3">@include('admin.reports._bar', ['value' => $share])</div>
                        @endforeach
                    @endif

                    <hr>
                    <dl class="row small mb-0">
                        <dt class="col-7 text-muted fw-normal">Unit terjual</dt>
                        <dd class="col-5 text-end">{{ $purchases['units_sold'] }}</dd>
                        <dt class="col-7 text-muted fw-normal mb-0">Nilai penjualan</dt>
                        <dd class="col-5 text-end fw-semibold mb-0"><x-price :amount="$purchases['sales_value']" /></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach (['Penjualan per Merek' => ['Merek', $byBrand], 'Penjualan per Kategori' => ['Kategori', $byCategory]] as $title => [$column, $rows])
            <div class="col-lg-6">
                <div class="card h-100 report-card">
                    <div class="card-header bg-transparent border-0 pt-3 px-3"><h3 class="h6 mb-0">{{ $title }}</h3></div>
                    @if ($rows->isEmpty())
                        <x-empty-state icon="bi-bar-chart" title="Belum ada penjualan" message="Tidak ada unit terjual pada periode ini." />
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 admin-table">
                                <thead>
                                    <tr>
                                        <th>{{ $column }}</th>
                                        <th class="text-center">Unit</th>
                                        <th class="text-end">Nilai</th>
                                        <th style="width: 30%">Porsi nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        @php($share = AdminReport::percent($row->value, $purchases['sales_value']))
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td class="text-center">{{ $row->units }}</td>
                                            <td class="text-end"><x-price :amount="$row->value" /></td>
                                            <td>
                                                @include('admin.reports._bar', ['value' => $share])
                                                <div class="small text-muted">{{ $percentLabel($share) }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 report-card">
                <div class="card-header bg-transparent border-0 pt-3 px-3"><h3 class="h6 mb-0">5 Mobil Terlaris</h3></div>
                @if ($topCars->isEmpty())
                    <x-empty-state icon="bi-trophy" title="Belum ada penjualan" message="Tidak ada unit terjual pada periode ini." />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px">#</th>
                                    <th>Mobil</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topCars as $car)
                                    <tr>
                                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                        <td>{{ $car->name }}</td>
                                        <td class="text-center">{{ $car->units }}</td>
                                        <td class="text-end"><x-price :amount="$car->value" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 report-card">
                <div class="card-header bg-transparent border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0">Test Drive per Status</h3>
                    <span class="small text-muted">Selesai: {{ $percentLabel($testDrives['completion_rate']) }}</span>
                </div>
                @if ($testDrives['total'] === 0)
                    <x-empty-state icon="bi-calendar-x" title="Belum ada test drive" message="Tidak ada test drive terjadwal pada periode ini." />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 admin-table">
                            <tbody>
                                @foreach ($testDrives['statuses'] as $status => $count)
                                    @php($share = AdminReport::percent($count, $testDrives['total']))
                                    <tr>
                                        <td style="width: 140px"><x-status-badge :status="$status" /></td>
                                        <td class="text-end" style="width: 60px">{{ $count }}</td>
                                        <td>@include('admin.reports._bar', ['value' => $share])</td>
                                        <td class="text-end small text-muted" style="width: 70px">{{ $percentLabel($share) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Total</td>
                                    <td class="text-end">{{ $testDrives['total'] }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card report-card">
        <div class="card-header bg-transparent border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0">Stok Menipis</h3>
            <span class="small text-muted">Mobil aktif dengan stok ≤ {{ Car::LOW_STOCK_THRESHOLD }} (saat ini, tidak bergantung periode)</span>
        </div>
        @if ($lowStock->isEmpty())
            <x-empty-state icon="bi-box-seam" title="Stok aman" message="Tidak ada mobil aktif dengan stok menipis." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Mobil</th>
                            <th>Kondisi</th>
                            <th class="text-center">Stok</th>
                            <th class="text-end d-print-none">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lowStock as $car)
                            <tr>
                                <td>{{ $car->brand->name }} {{ $car->name }} {{ $car->year }}</td>
                                <td>{{ $car->condition_label }}</td>
                                <td class="text-center">
                                    @if ($car->inStock())
                                        {{ $car->stock }}
                                    @else
                                        <x-status-badge status="out_of_stock" />
                                    @endif
                                </td>
                                <td class="text-end d-print-none">
                                    <a href="{{ route('admin.cars.edit', $car) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i>Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
