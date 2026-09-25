@extends('layouts.admin')

@section('title', 'Kategori')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Kategori']]])
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-3">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="d-flex gap-2 admin-search" role="search">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama kategori…" aria-label="Cari nama kategori">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i><span class="d-none d-md-inline">Cari</span></button>
            @if ($search !== '')
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.categories.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Kategori
        </a>
    </div>

    <div class="card">
        @if ($categories->isEmpty())
            @if ($search !== '')
                <x-empty-state icon="bi-search" title="Kategori tidak ditemukan" :message="'Tidak ada kategori yang cocok dengan “'.$search.'”.'" />
            @else
                <x-empty-state icon="bi-grid" title="Belum ada kategori" message="Tambahkan kategori seperti SUV, MPV, atau Sedan.">
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i>Tambah Kategori</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">No</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th class="text-center">Jumlah Mobil</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="text-center text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $category->name }}</td>
                                <td class="text-muted small">{{ $category->slug }}</td>
                                <td class="text-center">{{ $category->cars_count }}</td>
                                <td class="text-end text-nowrap">
                                    @include('admin.partials.row-actions', [
                                        'editUrl' => route('admin.categories.edit', $category),
                                        'deleteUrl' => route('admin.categories.destroy', $category),
                                        'name' => $category->name,
                                        'disabledReason' => $category->cars_count > 0 ? "Masih dipakai {$category->cars_count} mobil" : null,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $categories])
        @endif
    </div>

    <x-delete-modal entity="kategori" />
@endsection
