{{--
    Tombol aksi baris tabel (ikon + tooltip; label lengkap untuk pembaca layar).
    Jika $disabledReason diisi (mis. "Masih dipakai 3 mobil"), tombol Hapus dinonaktifkan
    dan alasannya tampil sebagai tooltip. Server tetap memeriksa ulang saat menghapus.
--}}
<a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Edit" aria-label="Edit {{ $name }}">
    <i class="bi bi-pencil-square"></i>
</a>

@if (! empty($disabledReason))
    <span class="d-inline-block" tabindex="0" title="{{ $disabledReason }}">
        <button type="button" class="btn btn-sm btn-outline-danger btn-icon" disabled aria-label="Hapus {{ $name }} (tidak bisa: {{ $disabledReason }})">
            <i class="bi bi-trash"></i>
        </button>
    </span>
@else
    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus" aria-label="Hapus {{ $name }}"
            data-bs-toggle="modal" data-bs-target="#deleteModal"
            data-delete-url="{{ $deleteUrl }}" data-delete-name="{{ $name }}">
        <i class="bi bi-trash"></i>
    </button>
@endif
