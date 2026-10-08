/*
 * Simulasi kredit live (/simulasi-kredit) — tanpa library.
 * Rumus sama persis dengan App\Support\CreditCalculator (bunga flat, bilangan bulat via BigInt):
 *   Pokok = Harga − DP; Bunga = Pokok × rate% × (tenor/12);
 *   Cicilan = (Pokok + Bunga) / tenor, dibulatkan ke atas per `rounding`.
 * Tanpa JavaScript form tetap bekerja (GET, dihitung server).
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-credit-form]');

    if (!form || typeof BigInt !== 'function') {
        return;
    }

    var rates = JSON.parse(form.getAttribute('data-rates'));
    var dpMin = BigInt(form.getAttribute('data-dp-min'));
    var dpMax = BigInt(form.getAttribute('data-dp-max'));
    var rounding = BigInt(form.getAttribute('data-rounding'));
    var minPrice = BigInt(form.getAttribute('data-min-price'));
    var maxPrice = BigInt(form.getAttribute('data-max-price'));

    var carSelect = form.querySelector('[data-credit-car]');
    var priceGroup = form.querySelector('[data-credit-price-group]');
    var priceInput = form.querySelector('[data-credit-price]');
    var dpInput = form.querySelector('[data-credit-dp]');
    var tenorSelect = form.querySelector('[data-credit-tenor]');
    var shortcuts = form.querySelector('[data-credit-dp-shortcuts]');
    var resultBox = document.querySelector('[data-credit-result]');
    var emptyBox = document.querySelector('[data-credit-empty]');
    var carOnly = document.querySelectorAll('[data-credit-car-actions], [data-credit-car-title]');
    var initialCar = carSelect.value;

    var formatter = new Intl.NumberFormat('id-ID');
    var rupiah = function (value) { return 'Rp ' + formatter.format(value); };
    var percent = function (value) { return String(value).replace('.', ',') + '%'; };

    var digits = function (input) {
        var value = (input.value || '').replace(/\D/g, '').replace(/^0+/, '');
        return value === '' ? null : BigInt(value.slice(0, 13));
    };

    var setText = function (selector, text, root) {
        var element = (root || resultBox).querySelector(selector);
        if (element) {
            element.textContent = text;
        }
    };

    var selectedPrice = function () {
        var option = carSelect.options[carSelect.selectedIndex];
        return carSelect.value !== '' && option ? BigInt(option.getAttribute('data-price')) : digits(priceInput);
    };

    var minDownPayment = function (price) { return (price * dpMin + 99n) / 100n; };
    var maxDownPayment = function (price) { return (price * dpMax) / 100n; };

    var calculate = function (price, downPayment, tenor) {
        var rate = rates[tenor];
        var rateBasisPoints = BigInt(Math.round(rate * 100));
        var months = BigInt(tenor);
        var principal = price - downPayment;
        var totalScaled = principal * 120000n + principal * rateBasisPoints * months;
        var divisor = 120000n * months * rounding;
        var monthly = ((totalScaled + divisor - 1n) / divisor) * rounding;

        return {
            price: price,
            down_payment: downPayment,
            principal: principal,
            tenor_months: tenor,
            interest_rate: rate,
            interest_total: (principal * rateBasisPoints * months + 60000n) / 120000n,
            monthly_installment: monthly,
            total_payment: downPayment + monthly * months
        };
    };

    var showResult = function (visible) {
        resultBox.classList.toggle('d-none', !visible);
        emptyBox.classList.toggle('d-none', visible);
    };

    var render = function () {
        var price = selectedPrice();
        var tenor = parseInt(tenorSelect.value, 10);

        priceGroup.classList.toggle('d-none', carSelect.value !== '');
        // Nama & tombol aksi mobil dibuat server untuk mobil awal; sembunyikan bila pilihan berubah.
        carOnly.forEach(function (element) {
            element.classList.toggle('d-none', carSelect.value !== initialCar);
        });

        if (price === null || price < minPrice || price > maxPrice || !(tenor in rates)) {
            showResult(false);
            return;
        }

        var downPayment = digits(dpInput);
        downPayment = downPayment === null ? minDownPayment(price) : downPayment;

        if (downPayment < minDownPayment(price) || downPayment > maxDownPayment(price)) {
            showResult(false);
            return;
        }

        var result = calculate(price, downPayment, tenor);
        ['monthly_installment', 'price', 'down_payment', 'principal', 'interest_total', 'total_payment'].forEach(function (key) {
            setText('[data-out="' + key + '"]', rupiah(result[key]));
        });
        setText('[data-out="tenor_months"]', String(tenor));
        setText('[data-out="interest_rate"]', percent(result.interest_rate));

        resultBox.querySelectorAll('[data-tenor-row]').forEach(function (row) {
            var months = parseInt(row.getAttribute('data-tenor-row'), 10);
            var item = calculate(price, downPayment, months);

            setText('[data-col="monthly_installment"]', rupiah(item.monthly_installment), row);
            setText('[data-col="total_payment"]', rupiah(item.total_payment), row);
            row.classList.toggle('table-active', months === tenor);
            row.classList.toggle('fw-semibold', months === tenor);
        });

        showResult(true);
    };

    // Tombol DP cepat (20/30/50%): dibulatkan ke atas per Rp 1 juta, tidak melebihi DP maksimal.
    shortcuts.classList.remove('d-none');
    shortcuts.querySelectorAll('[data-dp-percent]').forEach(function (button) {
        button.addEventListener('click', function () {
            var price = selectedPrice();

            if (price === null) {
                priceInput.focus();
                return;
            }

            var share = BigInt(button.getAttribute('data-dp-percent'));
            var million = 1000000n;
            var value = ((price * share + 100n * million - 1n) / (100n * million)) * million;
            value = value > maxDownPayment(price) ? maxDownPayment(price) : value;
            value = value < minDownPayment(price) ? minDownPayment(price) : value;

            dpInput.value = formatter.format(value);
            render();
        });
    });

    // DP dikosongkan saat mobil diganti agar kembali ke DP minimal mobil baru.
    carSelect.addEventListener('change', function () {
        dpInput.value = '';
        render();
    });
    [priceInput, dpInput].forEach(function (input) { input.addEventListener('input', render); });
    tenorSelect.addEventListener('change', render);
});
