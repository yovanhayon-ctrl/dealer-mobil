@extends('layouts.admin')

@section('title', 'Edit Layanan')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Layanan Servis', 'url' => route('admin.services.index')],
        ['label' => $service->name],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.services.update', $service) }}" novalidate>
                @method('PUT')
                @include('admin.services._form')
            </form>
        </div>
    </div>
@endsection
