/*
 * Dealer Mobil — script kecil untuk halaman publik (tanpa library).
 */
document.addEventListener('DOMContentLoaded', function () {
    // <select data-auto-submit>: kirim form begitu pilihan berubah.
    // Tombol submit di form tetap ada sebagai cadangan bila JavaScript mati.
    document.querySelectorAll('[data-auto-submit]').forEach(function (field) {
        field.addEventListener('change', function () {
            var form = field.form;

            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
});
