@extends('layouts.admin')

@section('title', 'Staf')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Staf']]])
@endsection

@php
    use App\Models\User;

    $summary = collect(User::STAFF_ROLES)
        ->filter(fn ($role) => ($counts[$role] ?? 0) > 0)
        ->map(fn ($role) => ($counts[$role] ?? 0).' '.User::ROLE_LABELS[$role])
        ->implode(', ');
@endphp

@section('content')
    <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-between mb-3">
        <p class="text-muted small mb-0">{{ $summary }}. Akun staf hanya dibuat di sini, tidak lewat halaman Daftar.</p>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Staf
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>WhatsApp</th>
                        <th>Kinerja Mekanik</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        <tr @class(['text-muted' => ! $member->isActive()])>
                            <td>
                                <div class="fw-semibold">
                                    {{ $member->name }}
                                    @if ($member->is(auth()->user()))
                                        <span class="small text-muted fw-normal">(Anda)</span>
                                    @endif
                                </div>
                                <div class="small text-muted text-break">{{ $member->email }}</div>
                            </td>
                            <td>@include('admin.users._role-badge', ['user' => $member, 'hideInactive' => true])</td>
                            <td class="text-nowrap">{{ $member->phone ?: '-' }}</td>
                            <td class="text-nowrap small">
                                @if ($member->isMechanic())
                                    @if ($member->mechanic_ratings_count)
                                        <i class="bi bi-star-fill text-warning" aria-hidden="true"></i>
                                        <span class="fw-semibold">{{ \App\Models\MechanicRating::formatAverage((float) $member->mechanic_ratings_avg_rating) }}</span>
                                        <span class="text-muted">({{ $member->mechanic_ratings_count }} rating)</span>
                                    @else
                                        <span class="text-muted">Belum ada rating</span>
                                    @endif
                                    <div class="text-muted">{{ $member->completed_jobs_count }} servis selesai</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$member->isActive() ? 'active' : 'inactive'" /></td>
                            <td class="text-end">
                                @if (auth()->user()->canManageStaffAccount($member))
                                    <a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary"
                                       aria-label="Ubah akun {{ $member->name }}">
                                        <i class="bi bi-pencil"></i>Ubah
                                    </a>
                                @else
                                    <span class="small text-muted">{{ $member->id === auth()->id() ? 'Akun Anda' : ($member->isOwner() ? 'Pemilik' : 'Khusus owner') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>Staf nonaktif tidak bisa login. Riwayat pekerjaannya tetap tersimpan.
        Owner baru ditambahkan lewat perintah <code>php artisan user:make-owner email</code>.
    </p>
@endsection
