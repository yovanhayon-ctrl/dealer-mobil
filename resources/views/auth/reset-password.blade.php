@extends('layouts.auth')

@section('title', 'Buat Kata Sandi Baru')

@section('content')
    <h1 class="h4 mb-1">Buat Kata Sandi Baru</h1>
    <p class="text-muted mb-4">Masukkan kata sandi baru untuk akun Anda (minimal 8 karakter).</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate data-disable-on-submit>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                   class="form-control @error('email') is-invalid @enderror"
                   autocomplete="email" required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi Baru</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password" required autofocus>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Ulangi Kata Sandi Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-key"></i>Simpan Kata Sandi Baru
        </button>
    </form>
@endsection
