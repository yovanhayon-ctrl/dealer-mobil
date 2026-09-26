@extends('layouts.admin')

@section('title', 'Edit Promo')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Promo', 'url' => route('admin.promos.index')],
        ['label' => $promo->title],
    ]])
@endsection

@section('content')
    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.promos.update', $promo) }}" enctype="multipart/form-data" novalidate>
                @method('PUT')
                @include('admin.promos._form')
            </form>
        </div>
    </div>
@endsection
