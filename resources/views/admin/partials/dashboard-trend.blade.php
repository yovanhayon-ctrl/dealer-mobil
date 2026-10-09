{{--
    Grafik tren 6 bulan (Chart.js, digambar admin.js dari atribut data-chart; tanpa script inline sesuai CSP).
    Tabel di bawah setiap grafik = data yang sama, tetap terbaca bila JavaScript/CDN gagal dimuat.
--}}
@php($charts = \App\Reports\DashboardTrend::chartData($trend))

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.js" integrity="sha384-G436+Z2nlA8+PNoeRvWdxKbvOf8E/y+lYxqht2iBwNHTQDV5CJr3+AGVj8fGZi5t" crossorigin="anonymous"></script>
@endpush

<section class="mb-4" aria-labelledby="trend-title">
    <h3 id="trend-title" class="h6 text-muted text-uppercase small fw-semibold mb-3">
        Tren {{ \App\Reports\DashboardTrend::MONTHS }} Bulan Terakhir
        <span class="text-lowercase fw-normal">({{ $trend[0]['label'] }} – {{ $trend[array_key_last($trend)]['label'] }})</span>
    </h3>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pt-3 px-3">
                    <h4 class="h6 mb-0">Aktivitas Customer per Bulan</h4>
                    <p class="small text-muted mb-0">Jumlah yang masuk (tanggal dibuat).</p>
                </div>
                <div class="card-body pt-2">
                    <div class="dashboard-chart">
                        <canvas data-chart="activity" data-chart-data='@json($charts['activity'])'
                                role="img" aria-label="Grafik garis aktivitas customer per bulan, rinciannya ada di tabel di bawah."></canvas>
                    </div>
                    <details class="mt-2 small">
                        <summary class="text-muted">Lihat angka</summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Bulan</th>
                                        <th scope="col" class="text-end">Pengajuan</th>
                                        <th scope="col" class="text-end">Test drive</th>
                                        <th scope="col" class="text-end">Servis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trend as $month)
                                        <tr>
                                            <th scope="row" class="fw-normal">{{ $month['label'] }}</th>
                                            <td class="text-end">{{ $month['purchases'] }}</td>
                                            <td class="text-end">{{ $month['test_drives'] }}</td>
                                            <td class="text-end">{{ $month['service_bookings'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pt-3 px-3">
                    <h4 class="h6 mb-0">Penjualan per Bulan</h4>
                    <p class="small text-muted mb-0">Pengajuan disetujui/selesai, menurut tanggal pengajuan (sama dengan Laporan).</p>
                </div>
                <div class="card-body pt-2">
                    <div class="dashboard-chart">
                        <canvas data-chart="sales" data-chart-data='@json($charts['sales'])'
                                role="img" aria-label="Grafik unit terjual dan nilai penjualan per bulan, rinciannya ada di tabel di bawah."></canvas>
                    </div>
                    <details class="mt-2 small">
                        <summary class="text-muted">Lihat angka</summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Bulan</th>
                                        <th scope="col" class="text-end">Unit terjual</th>
                                        <th scope="col" class="text-end">Nilai penjualan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trend as $month)
                                        <tr>
                                            <th scope="row" class="fw-normal">{{ $month['label'] }}</th>
                                            <td class="text-end">{{ $month['units_sold'] }}</td>
                                            <td class="text-end"><x-price :amount="$month['sales_value']" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
</section>
