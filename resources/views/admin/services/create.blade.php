@extends('layouts.admin')

@section('title', 'Tambah Layanan')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Layanan Servis', 'url' => route('admin.services.index')],
        ['label' => 'Tambah'],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.services.store') }}" novalidate>
                @include('admin.services._form')
            </form>
        </div>
    </div>
@endsection
