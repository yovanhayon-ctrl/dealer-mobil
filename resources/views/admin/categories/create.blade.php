@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Kategori', 'url' => route('admin.categories.index')],
        ['label' => 'Tambah'],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.categories.store') }}" novalidate>
                @include('admin.categories._form')
            </form>
        </div>
    </div>
@endsection
