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

    // <form data-confirm="Pesan?">: minta konfirmasi sebelum dikirim (tanpa JS form langsung terkirim,
    // server tetap memeriksa status).
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });
    });

    // <form data-disable-on-submit>: nonaktifkan tombol submit agar tidak terkirim dua kali.
    // Didaftarkan setelah data-confirm, jadi tombol tidak terkunci bila konfirmasi dibatalkan.
    var lockedButtons = [];

    document.querySelectorAll('form[data-disable-on-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                lockedButtons.push({ button: button, html: button.innerHTML });
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = 'Memproses…';
            });
        });
    });

    // Kembali lewat tombol Back (halaman dari bfcache): aktifkan lagi tombol yang tadi dikunci.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }

        lockedButtons.forEach(function (item) {
            item.button.disabled = false;
            item.button.removeAttribute('aria-busy');
            item.button.innerHTML = item.html;
        });
        lockedButtons = [];
    });
});
