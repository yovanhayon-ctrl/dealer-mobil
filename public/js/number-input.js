/*
 * Isian angka [data-number]: huruf & simbol tidak bisa diketik atau ditempel (dipakai halaman publik & admin).
 *   thousands : angka saja, titik ribuan otomatis (harga, DP, diskon, kilometer) -> 285.000.000
 *   digits    : angka saja (tahun, durasi, stok, kursi, cc); juga untuk <input type="number">
 *   phone     : angka, boleh diawali "+" (nomor WhatsApp)
 * Server tetap memvalidasi; skrip ini hanya mencegah salah ketik sejak awal.
 */
(function () {
    function withThousands(digits) {
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Posisi kursor setelah n digit pertama di dalam teks yang sudah diformat.
    function caretAfterDigits(text, count) {
        if (count <= 0) {
            return text.charAt(0) === '+' ? 1 : 0;
        }

        for (var index = 0, seen = 0; index < text.length; index++) {
            if (/\d/.test(text.charAt(index)) && ++seen === count) {
                return index + 1;
            }
        }

        return text.length;
    }

    function clean(input) {
        var type = input.getAttribute('data-number');
        var value = input.value;

        // <input type="number"> tidak memberi akses posisi kursor; nilainya cukup disaring.
        if (input.type === 'number') {
            var plain = value.replace(/\D/g, '');

            if (plain !== value) {
                input.value = plain;
            }

            return;
        }

        var digits = value.replace(/\D/g, '');
        var next = digits;

        if (type === 'thousands') {
            next = withThousands(digits);
        } else if (type === 'phone' && value.trim().charAt(0) === '+') {
            next = '+' + digits;
        }

        if (next === value) {
            return;
        }

        var caret = input.selectionStart === null ? value.length : input.selectionStart;
        var digitsBeforeCaret = value.slice(0, caret).replace(/\D/g, '').length;

        input.value = next;

        if (document.activeElement === input) {
            var position = caretAfterDigits(next, digitsBeforeCaret);
            input.setSelectionRange(position, position);
        }
    }

    document.addEventListener('input', function (event) {
        if (event.target.matches && event.target.matches('input[data-number]')) {
            clean(event.target);
        }
    });

    // type="number" menerima "e", "+", "-", "." dari keyboard: tolak sebelum masuk.
    document.addEventListener('keydown', function (event) {
        var input = event.target;

        if (input.matches && input.matches('input[type="number"][data-number]')
            && event.key.length === 1 && !/\d/.test(event.key) && !event.ctrlKey && !event.metaKey) {
            event.preventDefault();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-number]').forEach(clean);
    });
})();
