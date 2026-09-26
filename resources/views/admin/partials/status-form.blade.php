{{--
    Form ubah status + catatan admin (test drive & pengajuan).
    Parameter: $action (URL PATCH), $currentLabel (label status sekarang), $allowed ([status => label]
    transisi yang diizinkan; kosong = status akhir), $adminNote, $noteHelp.
    Server tetap memeriksa ulang transisi yang diizinkan.
--}}
<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @method('PATCH')

    @if ($allowed === [])
        <p class="small text-muted mb-3">
            <i class="bi bi-lock me-1"></i>Status <strong>{{ $currentLabel }}</strong> adalah status akhir dan tidak bisa diubah lagi.
            Catatan admin masih bisa diperbarui.
        </p>
        <input type="hidden" name="status" value="">
    @else
        <x-form.select name="status" label="Ubah status" :options="$allowed" :value="null"
                       :placeholder="'Tetap: '.$currentLabel"
                       help="Hanya status lanjutan yang diizinkan yang ditampilkan." />
    @endif

    <x-form.textarea name="admin_note" label="Catatan admin" :value="$adminNote" rows="4" maxlength="1000"
                     :help="$noteHelp" />

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i>Simpan
    </button>
</form>
