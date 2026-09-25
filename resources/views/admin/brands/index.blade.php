@extends('layouts.admin')

@section('title', 'Merek')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Merek']]])
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between mb-3">
        <form method="GET" action="{{ route('admin.brands.index') }}" class="d-flex gap-2 admin-search" role="search">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama merek…" aria-label="Cari nama merek">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i><span class="d-none d-md-inline">Cari</span></button>
            @if ($search !== '')
                <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.brands.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg"></i>Tambah Merek
        </a>
    </div>

    <div class="card">
        @if ($brands->isEmpty())
            @if ($search !== '')
                <x-empty-state icon="bi-search" title="Merek tidak ditemukan" :message="'Tidak ada merek yang cocok dengan “'.$search.'”.'" />
            @else
                <x-empty-state icon="bi-tags" title="Belum ada merek" message="Tambahkan merek pertama untuk mulai mengisi data mobil.">
                    <a href="{{ route('admin.brands.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i>Tambah Merek</a>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">No</th>
                            <th style="width: 72px">Logo</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th class="text-center">Jumlah Mobil</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr>
                                <td class="text-center text-muted">{{ $brands->firstItem() + $loop->index }}</td>
                                <td>
                                    @if ($brand->logo_url)
                                        <img src="{{ $brand->logo_url }}" alt="Logo {{ $brand->name }}" class="admin-thumb">
                                    @else
                                        <span class="admin-thumb admin-thumb-empty"><i class="bi bi-image"></i></span>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $brand->name }}</td>
                                <td class="text-muted small">{{ $brand->slug }}</td>
                                <td class="text-center">{{ $brand->cars_count }}</td>
                                <td class="text-end text-nowrap">
                                    @include('admin.partials.row-actions', [
                                        'editUrl' => route('admin.brands.edit', $brand),
                                        'deleteUrl' => route('admin.brands.destroy', $brand),
                                        'name' => $brand->name,
                                        'usedCount' => $brand->cars_count,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $brands])
        @endif
    </div>

    <x-delete-modal entity="merek" />
@endsection
