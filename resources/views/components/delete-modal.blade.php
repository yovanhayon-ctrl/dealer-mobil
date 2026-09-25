{{--
    Satu modal konfirmasi hapus per halaman. Tombol pemicu:
    <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal"
            data-delete-url="{{ route('...destroy', $item) }}" data-delete-name="{{ $item->name }}">
    URL dan nama diisi oleh public/js/admin.js.
--}}
@props(['entity' => 'data'])

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="POST" action="" data-delete-form>
                @csrf
                @method('DELETE')

                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-accent me-1"></i>Hapus {{ $entity }}?
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    Hapus {{ $entity }} <strong data-delete-name></strong>? Tindakan ini tidak bisa dibatalkan.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i>Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
