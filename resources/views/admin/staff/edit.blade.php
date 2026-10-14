@extends('layouts.admin')

@section('title', 'Ubah Staf')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Staf', 'url' => route('admin.staff.index')],
        ['label' => $staff->name],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.staff.update', $staff) }}" novalidate>
                @method('PUT')
                @include('admin.staff._form')
            </form>
        </div>
    </div>
@endsection
