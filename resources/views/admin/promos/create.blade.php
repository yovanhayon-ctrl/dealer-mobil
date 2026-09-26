@extends('layouts.admin')

@section('title', 'Tambah Promo')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Promo', 'url' => route('admin.promos.index')],
        ['label' => 'Tambah'],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.promos.store') }}" enctype="multipart/form-data" novalidate>
                @include('admin.promos._form')
            </form>
        </div>
    </div>
@endsection
