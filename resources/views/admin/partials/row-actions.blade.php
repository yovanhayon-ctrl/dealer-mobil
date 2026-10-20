{{--
    Tombol aksi baris tabel (ikon + tooltip; label lengkap untuk pembaca layar).
    $editAbility (opsional, mis. "cars.manage"): tombol Ubah hanya untuk yang punya hak itu (Sales hanya melihat).
    Tombol Hapus hanya untuk yang punya hak master.delete (owner & super admin; route destroy dibatasi can:master.delete)
    dan juga boleh mengubah datanya ($editAbility), mis. Super Admin tidak menghapus promo.
    Jika $disabledReason diisi (mis. "Masih dipakai 3 mobil"), tombol Hapus dinonaktifkan
    dan alasannya tampil sebagai tooltip. Server tetap memeriksa ulang saat menghapus.
--}}
@if (empty($editAbility) || auth()->user()->can($editAbility))
    <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary btn-icon" title="Edit" aria-label="Edit {{ $name }}">
        <i class="bi bi-pencil-square"></i>
    </a>
@endif

@if (! auth()->user()->can('master.delete') || (! empty($editAbility) && ! auth()->user()->can($editAbility)))
    {{-- Tanpa hak hapus: tidak ada tombol hapus. --}}
@elseif (! empty($disabledReason))
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
