/*
 * Form pengajuan pembelian (/mobil/{slug}/ajukan) — tanpa library.
 * Menampilkan/menyembunyikan isian kredit dan ringkasan cicilan live memakai rumus yang sama
 * dengan server (window.DealerCredit dari credit-simulation.js). Nilai yang disimpan tetap
 * dihitung ulang di server; tanpa JavaScript isian kredit selalu tampil.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-purchase-form]');

    if (!form || !window.DealerCredit || typeof BigInt !== 'function') {
        return;
    }

    var credit = window.DealerCredit.create(form);
    var price = BigInt(form.getAttribute('data-price'));
    var creditFields = form.querySelector('[data-credit-fields]');
    var dpInput = form.querySelector('[data-purchase-dp]');
    var tenorSelect = form.querySelector('[data-purchase-tenor]');
    var preview = form.querySelector('[data-purchase-preview]');
    var formatter = new Intl.NumberFormat('id-ID');
    var rupiah = function (value) { return 'Rp ' + formatter.format(value); };

    var isCredit = function () {
        var checked = form.querySelector('input[name="payment_method"]:checked');
        return checked !== null && checked.value === 'credit';
    };

    var render = function () {
        creditFields.classList.toggle('d-none', !isCredit());

        var digits = (dpInput.value || '').replace(/\D/g, '').replace(/^0+/, '').slice(0, 13);
        var downPayment = digits === '' ? null : BigInt(digits);
        var tenor = parseInt(tenorSelect.value, 10);
        var valid = downPayment !== null
            && downPayment >= credit.minDownPayment(price)
            && downPayment <= credit.maxDownPayment(price)
            && tenor in credit.rates;

        preview.classList.toggle('d-none', !valid);

        if (!valid) {
            return;
        }

        var result = credit.calculate(price, downPayment, tenor);
        ['monthly_installment', 'principal', 'total_payment'].forEach(function (key) {
            var element = preview.querySelector('[data-preview="' + key + '"]');
            if (element) {
                element.textContent = rupiah(result[key]);
            }
        });
        preview.querySelector('[data-preview="tenor_months"]').textContent = String(tenor);
    };

    form.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', render);
    });
    dpInput.addEventListener('input', render);
    tenorSelect.addEventListener('change', render);
    render();
});
