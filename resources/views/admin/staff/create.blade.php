@extends('layouts.admin')

@section('title', 'Tambah Staf')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Staf', 'url' => route('admin.staff.index')],
        ['label' => 'Tambah'],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.staff.store') }}" novalidate>
                @include('admin.staff._form')
            </form>
        </div>
    </div>
@endsection
