@extends('layouts.admin')

@section('title', 'Pekerjaan Saya')

@section('content')
    <p class="text-muted mb-3">Halo, {{ auth()->user()->name }}. Servis yang ditugaskan admin ke Anda tampil di sini.</p>

    <div class="card">
        <x-empty-state icon="bi-wrench-adjustable" title="Belum ada servis yang ditugaskan"
                       message="Admin menugaskan servis saat mengonfirmasi booking. Daftar pekerjaan dan checklist-nya akan muncul di halaman ini." />
    </div>
@endsection
