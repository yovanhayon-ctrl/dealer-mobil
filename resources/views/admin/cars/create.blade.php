@extends('layouts.admin')

@section('title', 'Tambah Mobil')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Mobil', 'url' => route('admin.cars.index')],
        ['label' => 'Tambah'],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card admin-form-card-wide">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.cars.store') }}" novalidate>
                @include('admin.cars._form')
            </form>
        </div>
    </div>
@endsection
