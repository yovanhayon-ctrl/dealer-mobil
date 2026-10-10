@extends('layouts.app')

@section('title', 'Booking Test Drive')
@section('meta_description', 'Booking jadwal test drive mobil di '.config('dealer.name').', pilih tanggal dan jam yang Anda inginkan.')

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Test Drive']]])

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <h1 class="h3 mb-1">Booking Test Drive</h1>
                <p class="text-muted mb-4">
                    Pilih mobil dan jadwal, lalu tim kami akan menghubungi Anda lewat WhatsApp untuk konfirmasi.
                </p>

                @if ($isAdmin)
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>
                            {{ \App\Http\Requests\TestDriveRequest::ADMIN_MESSAGE }}
                            <a href="{{ route('admin.test-drives.index') }}" class="alert-link">Kelola test drive di dashboard admin</a>.
                        </div>
                    </div>
                @elseif ($cars->isEmpty())
                    <div class="card">
                        <x-empty-state icon="bi-car-front" title="Belum ada mobil yang bisa di-test drive"
                                       message="Silakan cek kembali nanti atau hubungi dealer.">
                            <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                        </x-empty-state>
                    </div>
                @else
                    @if ($unavailableCar)
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <div>
                                Mobil {{ $unavailableCar->brand->name }} {{ $unavailableCar->name }} {{ $unavailableCar->year }}
                                tidak tersedia untuk test drive. Silakan pilih mobil lain.
                            </div>
                        </div>
                    @endif

                    @if ($selectedCar)
                        <div class="card test-drive-car-summary mb-4">
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                @if ($selectedCar->primaryImage)
                                    <img src="{{ $selectedCar->primaryImage->thumb_url }}" alt="{{ $selectedCar->brand->name }} {{ $selectedCar->name }} {{ $selectedCar->year }}"
                                         class="test-drive-car-thumb">
                                @else
                                    <span class="test-drive-car-thumb car-card-img-empty" aria-hidden="true"><i class="bi bi-car-front"></i></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="small text-muted mb-0">{{ $selectedCar->brand->name }} · {{ $selectedCar->condition_label }}</p>
                                    <p class="fw-semibold font-heading mb-0">{{ $selectedCar->name }} {{ $selectedCar->year }}</p>
                                    <a href="{{ route('cars.show', $selectedCar) }}" class="small">Lihat detail mobil</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body p-3 p-lg-4">
                            <form method="POST" action="{{ route('test-drives.store') }}" data-disable-on-submit>
                                @csrf

                                <x-form.select name="car_id" label="Mobil" required placeholder="Pilih mobil"
                                               :value="$selectedCar?->id"
                                               :options="$cars->mapWithKeys(fn ($car) => [$car->id => sprintf('%s %s %s (%s)', $car->brand->name, $car->name, $car->year, $car->condition_label)])->all()" />

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <x-form.input name="preferred_date" type="date" label="Tanggal" required
                                                      min="{{ $firstDate }}" max="{{ $lastDate }}"
                                                      help="Besok s/d 30 hari ke depan." />
                                    </div>
                                    <div class="col-sm-6">
                                        <x-form.select name="preferred_time" label="Jam (WIB)" required placeholder="Pilih jam"
                                                       :options="collect(\App\Models\TestDrive::TIME_SLOTS)->mapWithKeys(fn ($time) => [$time => $time.' WIB'])->all()" />
                                    </div>
                                </div>

                                <x-form.input name="phone" type="tel" label="Nomor WhatsApp" required
                                              :value="auth()->user()->phone" inputmode="tel" autocomplete="tel"
                                              help="Format 08xx. Dipakai tim kami untuk konfirmasi jadwal." />

                                <x-form.textarea name="notes" label="Catatan (opsional)" rows="3"
                                                 maxlength="{{ \App\Http\Requests\TestDriveRequest::MAX_NOTES_LENGTH }}"
                                                 help="Mis. ingin mencoba rute tol, atau datang bersama keluarga." />

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="bi bi-calendar-check"></i>Kirim Booking
                                    </button>
                                    <a href="{{ route('account.test-drives.index') }}" class="btn btn-outline-secondary">Riwayat Test Drive</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <ul class="small text-muted mt-3 mb-0">
                        <li>Jadwal dapat dipilih mulai besok sampai 30 hari ke depan, pukul 09.00–16.00 WIB.</li>
                        <li>Status booking "Menunggu" sampai dikonfirmasi dealer; Anda bisa membatalkannya selama belum dikonfirmasi.</li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
