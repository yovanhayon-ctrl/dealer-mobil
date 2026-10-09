@extends('layouts.app')

@use('App\Http\Controllers\CreditSimulationController')

@section('title', 'Simulasi Kredit')
@section('meta_description', 'Hitung perkiraan cicilan mobil di '.config('dealer.name').': pilih mobil, uang muka, dan tenor. Bunga flat, hasil langsung tampil.')

@php
    $rupiah = fn (int $amount) => CreditSimulationController::rupiah($amount);
    $digits = fn (?int $amount) => $amount === null ? '' : number_format($amount, 0, ',', '.');
    $percent = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
    $whatsapp = preg_replace('/\D/', '', (string) config('dealer.whatsapp'));
    $carTitle = $car ? "{$car->brand->name} {$car->name} {$car->year}" : null;
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Simulasi Kredit']]])

        <h1 class="h3 mb-1">Simulasi Kredit</h1>
        <p class="text-muted mb-4">Perkiraan cicilan dengan bunga flat. Pilih mobil atau isi harga, lalu atur uang muka dan tenor.</p>

        @if ($unknownCar)
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div>Mobil yang dipilih tidak tersedia. Silakan pilih mobil lain atau isi harga sendiri.</div>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-3 p-lg-4">
                        <form method="GET" action="{{ route('credit.index') }}" id="creditForm" novalidate
                              data-credit-form
                              data-rates='@json($rates)'
                              data-dp-min="{{ config('credit.dp_min') }}"
                              data-dp-max="{{ config('credit.dp_max') }}"
                              data-rounding="{{ config('credit.rounding') }}"
                              data-min-price="{{ CreditSimulationController::MIN_PRICE }}"
                              data-max-price="{{ CreditSimulationController::MAX_PRICE }}">
                            <div class="mb-3">
                                <label for="mobil" class="form-label">Mobil</label>
                                <select id="mobil" name="mobil" class="form-select" data-credit-car>
                                    <option value="">— Isi harga sendiri —</option>
                                    @foreach ($cars as $option)
                                        <option value="{{ $option->slug }}" data-price="{{ $option->finalPrice() }}" @selected($car?->is($option))>
                                            {{ $option->brand->name }} {{ $option->name }} {{ $option->year }} · {{ $rupiah($option->finalPrice()) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Harga mobil sudah termasuk potongan promo yang sedang berjalan.</div>
                            </div>

                            <div @class(['d-none' => $car !== null]) data-credit-price-group>
                                <x-form.input name="harga" label="Harga Mobil (Rp)" :value="$car ? '' : $digits($price)"
                                              inputmode="numeric" placeholder="Contoh: 300.000.000" data-credit-price
                                              help="Diisi bila mobil tidak dipilih dari daftar." />
                            </div>

                            <x-form.input name="dp" label="Uang Muka / DP (Rp)" :value="$digits($downPayment ?? ($result['down_payment'] ?? null))"
                                          inputmode="numeric" placeholder="Minimal {{ config('credit.dp_min') }}% dari harga" data-credit-dp
                                          :help="'Minimal '.config('credit.dp_min').'%, maksimal '.config('credit.dp_max').'% dari harga. Kosongkan untuk DP minimal.'" />
                            <div class="d-flex flex-wrap gap-2 mb-3 mt-n2 d-none" data-credit-dp-shortcuts>
                                @foreach ([20, 30, 50] as $share)
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dp-percent="{{ $share }}">DP {{ $share }}%</button>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label for="tenor" class="form-label">Tenor</label>
                                <select id="tenor" name="tenor" class="form-select" data-credit-tenor>
                                    @foreach ($tenors as $months)
                                        <option value="{{ $months }}" @selected($tenor === $months)>
                                            {{ $months }} bulan ({{ $months / 12 }} tahun) · bunga {{ $percent((float) $rates[$months]) }}/tahun
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-accent w-100">
                                <i class="bi bi-calculator"></i>Hitung Cicilan
                            </button>
                        </form>
                    </div>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>Simulasi bersifat perkiraan dan tidak terhubung dengan perusahaan leasing.
                    Bunga flat per tahun; cicilan dibulatkan ke atas per Rp {{ number_format(config('credit.rounding'), 0, ',', '.') }}.
                    Nilai akhir mengikuti persetujuan leasing.
                </p>
            </div>

            <div class="col-lg-7">
                <div @class(['card', 'd-none' => $result !== null]) data-credit-empty>
                    <x-empty-state icon="bi-calculator" title="Belum ada simulasi"
                                   message="Pilih mobil atau isi harga, lalu tekan Hitung Cicilan." />
                </div>

                <div @class(['d-none' => $result === null]) data-credit-result>
                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <p class="small text-muted text-uppercase fw-semibold mb-1">Cicilan per bulan</p>
                            <p class="display-6 fw-bold text-accent mb-1" data-out="monthly_installment">{{ $result ? $rupiah($result['monthly_installment']) : '' }}</p>
                            <p class="small text-muted mb-4">
                                selama <span data-out="tenor_months">{{ $result['tenor_months'] ?? '' }}</span> bulan
                                @if ($carTitle)
                                    <span data-credit-car-title>· <span class="fw-semibold">{{ $carTitle }}</span></span>
                                @endif
                            </p>

                            <dl class="row small mb-0">
                                <dt class="col-6 text-muted fw-normal">Harga mobil</dt>
                                <dd class="col-6 text-end" data-out="price">{{ $result ? $rupiah($result['price']) : '' }}</dd>

                                <dt class="col-6 text-muted fw-normal">Uang muka (DP)</dt>
                                <dd class="col-6 text-end" data-out="down_payment">{{ $result ? $rupiah($result['down_payment']) : '' }}</dd>

                                <dt class="col-6 text-muted fw-normal">Pokok pinjaman</dt>
                                <dd class="col-6 text-end" data-out="principal">{{ $result ? $rupiah($result['principal']) : '' }}</dd>

                                <dt class="col-6 text-muted fw-normal">Bunga flat per tahun</dt>
                                <dd class="col-6 text-end" data-out="interest_rate">{{ $result ? $percent($result['interest_rate']) : '' }}</dd>

                                <dt class="col-6 text-muted fw-normal">Total bunga</dt>
                                <dd class="col-6 text-end" data-out="interest_total">{{ $result ? $rupiah($result['interest_total']) : '' }}</dd>

                                <dt class="col-6 fw-semibold border-top pt-2">Total pembayaran</dt>
                                <dd class="col-6 text-end fw-semibold border-top pt-2 mb-0" data-out="total_payment">{{ $result ? $rupiah($result['total_payment']) : '' }}</dd>
                            </dl>

                            @if ($car)
                                <div class="d-flex flex-wrap gap-2 mt-4" data-credit-car-actions>
                                    @if ($result && $car->canBePurchased() && Route::has('purchase-requests.create'))
                                        <a href="{{ route('purchase-requests.create', ['car' => $car, 'metode' => 'kredit', 'dp' => $result['down_payment'], 'tenor' => $result['tenor_months']]) }}"
                                           class="btn btn-accent btn-sm" data-credit-apply>
                                            <i class="bi bi-cart-check"></i>Ajukan dengan Simulasi Ini
                                        </a>
                                    @endif
                                    <a href="{{ route('cars.show', $car) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-car-front"></i>Lihat Detail Mobil
                                    </a>
                                    @if ($car->canBeTestDriven() && Route::has('test-drives.create'))
                                        <a href="{{ route('test-drives.create', ['mobil' => $car->slug]) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-calendar-check"></i>Test Drive
                                        </a>
                                    @endif
                                    @if ($whatsapp && $result)
                                        <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode("Halo, saya ingin konsultasi kredit {$carTitle}: DP {$rupiah($result['down_payment'])}, tenor {$result['tenor_months']} bulan, cicilan {$rupiah($result['monthly_installment'])}/bulan.") }}"
                                           target="_blank" rel="noopener" class="btn btn-success btn-sm">
                                            <i class="bi bi-whatsapp"></i>Konsultasi via WhatsApp
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-transparent border-0 pt-3 px-3">
                            <h2 class="h6 mb-0">Perbandingan Tenor (DP sama)</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tenor</th>
                                        <th class="text-end">Bunga/tahun</th>
                                        <th class="text-end">Cicilan/bulan</th>
                                        <th class="text-end d-none d-sm-table-cell">Total pembayaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tenors as $months)
                                        @php($row = collect($comparison)->firstWhere('tenor_months', $months))
                                        <tr @class(['table-active fw-semibold' => $months === $tenor]) data-tenor-row="{{ $months }}">
                                            <td>{{ $months }} bulan</td>
                                            <td class="text-end">{{ $percent((float) $rates[$months]) }}</td>
                                            <td class="text-end" data-col="monthly_installment">{{ $row ? $rupiah($row['monthly_installment']) : '' }}</td>
                                            <td class="text-end d-none d-sm-table-cell" data-col="total_payment">{{ $row ? $rupiah($row['total_payment']) : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\Asset::url('js/credit-simulation.js') }}"></script>
@endpush
