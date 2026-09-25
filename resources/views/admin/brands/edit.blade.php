@extends('layouts.admin')

@section('title', 'Edit Merek')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Merek', 'url' => route('admin.brands.index')],
        ['label' => $brand->name],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data" novalidate>
                @method('PUT')
                @include('admin.brands._form')
            </form>
        </div>
    </div>
@endsection
