{{-- Tombol Edit + Hapus di baris tabel. Hapus dinonaktifkan jika data masih dipakai ($usedCount > 0). --}}
<a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil-square"></i>Edit
</a>

@if (($usedCount ?? 0) > 0)
    <span class="d-inline-block" tabindex="0" title="Masih dipakai {{ $usedCount }} mobil">
        <button type="button" class="btn btn-sm btn-outline-danger" disabled>
            <i class="bi bi-trash"></i>Hapus
        </button>
    </span>
@else
    <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#deleteModal"
            data-delete-url="{{ $deleteUrl }}" data-delete-name="{{ $name }}">
        <i class="bi bi-trash"></i>Hapus
    </button>
@endif
