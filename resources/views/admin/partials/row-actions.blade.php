{{--
    Tombol Edit + Hapus di baris tabel.
    Jika $disabledReason diisi (mis. "Masih dipakai 3 mobil"), tombol Hapus dinonaktifkan
    dan alasannya tampil sebagai tooltip. Server tetap memeriksa ulang saat menghapus.
--}}
<a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil-square"></i>Edit
</a>

@if (! empty($disabledReason))
    <span class="d-inline-block" tabindex="0" title="{{ $disabledReason }}">
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
