@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <h1 class="h4 mb-1">Lupa Kata Sandi</h1>
    <p class="text-muted mb-4">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate data-disable-on-submit>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   autocomplete="email" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-envelope"></i>Kirim Tautan Reset
        </button>
    </form>

    <p class="text-center text-muted mt-4 mb-0">
        Ingat kata sandi? <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
    </p>
@endsection
