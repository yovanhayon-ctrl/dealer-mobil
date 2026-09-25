@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
    <h1 class="h4 mb-1">Daftar Akun</h1>
    <p class="text-muted mb-4">Buat akun untuk mengajukan test drive dan pembelian.</p>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   autocomplete="name" required autofocus>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   autocomplete="email" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Nomor HP</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                   class="form-control @error('phone') is-invalid @enderror"
                   placeholder="081234567890" autocomplete="tel" required>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text">Boleh diawali 08, 62, atau +62.</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text">Minimal 8 karakter.</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-accent w-100">
            <i class="bi bi-person-plus"></i>Daftar
        </button>
    </form>

    <p class="text-center text-muted mt-4 mb-0">
        Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
    </p>
@endsection
