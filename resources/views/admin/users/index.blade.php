@extends('layouts.admin')

@section('title', 'Pengguna')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Pengguna']]])
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-12 col-md-5">
                    <label for="filter_q" class="form-label small mb-1">Kata kunci</label>
                    <input type="search" id="filter_q" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Nama, email, atau nomor HP…">
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter_role" class="form-label small mb-1">Role</label>
                    <select id="filter_role" name="role" class="form-select form-select-sm">
                        @foreach (\App\Http\Controllers\Admin\UserController::ROLE_FILTERS as $value => $label)
                            <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="filter_urut" class="form-label small mb-1">Urutkan</label>
                    <select id="filter_urut" name="urut" class="form-select form-select-sm">
                        <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru</option>
                        <option value="nama" @selected($filters['urut'] === 'nama')>Nama (A–Z)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i>Terapkan</button>
                    @if ($hasFilters || $filters['urut'] !== 'terbaru')
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            @if ($filters['q'] !== null)
                <x-empty-state icon="bi-search" title="Pengguna tidak ditemukan" message="Tidak ada pengguna yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-people" title="Belum ada pengguna" message="Customer yang mendaftar akan muncul di sini." />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Nomor HP</th>
                            <th>Role</th>
                            <th class="text-center">Test Drive</th>
                            <th class="text-center">Pengajuan</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-reset text-decoration-none">{{ $user->name }}</a>
                                </td>
                                <td class="small">{{ $user->email }}</td>
                                <td class="text-nowrap">{{ $user->phone ?: '—' }}</td>
                                <td>@include('admin.users._role-badge')</td>
                                <td class="text-center">{{ $user->test_drives_count }}</td>
                                <td class="text-center">{{ $user->purchase_requests_count }}</td>
                                <td class="text-nowrap small">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $users])
        @endif
    </div>
@endsection
