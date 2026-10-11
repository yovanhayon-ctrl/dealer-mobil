/*
 * Mode terang/gelap untuk halaman publik, akun, dan login (admin selalu terang).
 * Dimuat di <head> tanpa defer, agar tema terpasang sebelum halaman tampil (tidak berkedip putih).
 * Urutan: pilihan tersimpan (localStorage "jaf-theme"), lalu pengaturan perangkat (prefers-color-scheme).
 */
(function () {
    var KEY = 'jaf-theme';
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function stored() {
        try {
            var value = window.localStorage.getItem(KEY);

            return value === 'dark' || value === 'light' ? value : null;
        } catch (error) {
            return null;
        }
    }

    function preferred() {
        return stored() || (media && media.matches ? 'dark' : 'light');
    }

    // Tombol [data-theme-toggle]: label untuk pembaca layar & teks di HP mengikuti mode yang akan dipilih.
    function apply(theme) {
        var isDark = theme === 'dark';
        var label = isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';

        root.setAttribute('data-bs-theme', theme);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);

            var text = button.querySelector('[data-theme-label]');

            if (text) {
                text.textContent = isDark ? 'Mode terang' : 'Mode gelap';
            }
        });
    }

    apply(preferred());

    document.addEventListener('DOMContentLoaded', function () {
        apply(preferred());

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-theme-toggle]');

            if (!button) {
                return;
            }

            var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';

            try {
                window.localStorage.setItem(KEY, next);
            } catch (error) {
                // Penyimpanan diblokir (mode privat): tema tetap berganti untuk halaman ini.
            }

            apply(next);
        });
    });

    // Selama pengunjung belum memilih sendiri, ikuti perubahan pengaturan perangkat.
    if (media && media.addEventListener) {
        media.addEventListener('change', function () {
            if (!stored()) {
                apply(preferred());
            }
        });
    }
})();
