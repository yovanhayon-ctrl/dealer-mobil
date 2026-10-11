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

    // Navbar: bayangan tipis setelah halaman digulir.
    var navbar = document.querySelector('[data-navbar]');

    if (navbar) {
        var syncNavbar = function () {
            navbar.classList.toggle('is-scrolled', window.scrollY > 4);
        };

        syncNavbar();
        window.addEventListener('scroll', syncNavbar, { passive: true });
    }

    // Menu "tubelight" (desktop ≥1200px): satu lampu meluncur ke menu yang disorot, lalu kembali ke menu aktif.
    // Tanpa JavaScript, CSS sudah menandai menu aktif; di HP menu tetap hamburger biasa.
    var tubelight = document.querySelector('[data-tubelight]');

    if (tubelight) {
        var lamp = document.createElement('li');
        lamp.className = 'tubelight-lamp';
        lamp.setAttribute('aria-hidden', 'true');
        tubelight.appendChild(lamp);
        tubelight.classList.add('is-enhanced');

        var activeLink = tubelight.querySelector('.nav-link.active');

        var moveLamp = function (link) {
            if (!link) {
                lamp.classList.remove('is-visible');

                return;
            }

            lamp.style.setProperty('--lamp-x', link.offsetLeft + 'px');
            lamp.style.setProperty('--lamp-w', link.offsetWidth + 'px');
            lamp.classList.add('is-visible');
        };

        tubelight.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('mouseenter', function () {
                moveLamp(link);
            });
            link.addEventListener('focus', function () {
                moveLamp(link);
            });
        });

        tubelight.addEventListener('mouseleave', function () {
            moveLamp(activeLink);
        });
        tubelight.addEventListener('focusout', function (event) {
            if (!tubelight.contains(event.relatedTarget)) {
                moveLamp(activeLink);
            }
        });

        // Posisi awal tanpa animasi; dihitung ulang bila lebar menu berubah (font selesai dimuat, ukuran layar).
        lamp.style.transition = 'none';
        moveLamp(activeLink);
        lamp.getBoundingClientRect();
        lamp.style.transition = '';

        if ('ResizeObserver' in window) {
            new ResizeObserver(function () {
                moveLamp(tubelight.contains(document.activeElement) ? document.activeElement : activeLink);
            }).observe(tubelight);
        }
    }

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
