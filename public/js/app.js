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

    // Video latar hero: hanya layar lebar (≥992px), tanpa "reduce motion", dan tidak dalam mode hemat data.
    // Satu video diputar berulang; beberapa video diputar bergantian.
    var heroVideo = document.querySelector('[data-hero-videos]');

    if (heroVideo) {
        var heroSection = heroVideo.closest('.home-hero');
        var sources = [];

        try {
            sources = JSON.parse(heroVideo.getAttribute('data-hero-videos')) || [];
        } catch (error) {
            sources = [];
        }

        var wide = window.matchMedia('(min-width: 992px)').matches;
        var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var saveData = navigator.connection && navigator.connection.saveData;

        if (sources.length && wide && !calm && !saveData) {
            var index = 0;

            // Autoplay hanya diizinkan bila video tanpa suara; set juga lewat properti (bukan hanya atribut).
            heroVideo.muted = true;
            heroVideo.defaultMuted = true;

            var retryLater = function () {
                // Autoplay ditolak (mis. tab belum terlihat): banner tanpa video tetap tampil,
                // lalu dicoba lagi saat tab terlihat atau saat pengunjung pertama kali berinteraksi.
                var retry = function () {
                    if (heroVideo.paused && !document.hidden) {
                        heroVideo.play().catch(function () {});
                    }
                };

                document.addEventListener('visibilitychange', retry);
                ['pointerdown', 'keydown', 'scroll'].forEach(function (type) {
                    window.addEventListener(type, retry, { once: true, passive: true });
                });
            };

            var playCurrent = function () {
                heroVideo.src = sources[index];
                var attempt = heroVideo.play();

                if (attempt && typeof attempt.catch === 'function') {
                    attempt.catch(retryLater);
                }
            };

            heroVideo.loop = sources.length === 1;
            heroVideo.addEventListener('playing', function () {
                heroVideo.classList.add('is-playing');
                heroSection.classList.add('video-on');
            });
            heroVideo.addEventListener('ended', function () {
                index = (index + 1) % sources.length;
                playCurrent();
            });

            playCurrent();
        }
    }
});
