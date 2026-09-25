@extends('layouts.admin')

@section('title', 'Edit Mobil')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Mobil', 'url' => route('admin.cars.index')],
        ['label' => $car->name.' '.$car->year],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card admin-form-card-wide">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.cars.update', $car) }}" novalidate>
                @method('PUT')
                @include('admin.cars._form')
            </form>
        </div>
    </div>
@endsection
