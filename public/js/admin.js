/*
 * Dealer Mobil — script kecil untuk halaman admin.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Modal konfirmasi hapus: isi action form & nama item dari tombol pemicu.
    var deleteModal = document.getElementById('deleteModal');

    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            deleteModal.querySelector('[data-delete-form]').action = trigger.getAttribute('data-delete-url');
            deleteModal.querySelector('[data-delete-name]').textContent = trigger.getAttribute('data-delete-name');
        });
    }

    // Form mobil: field kilometer hanya tampil untuk kondisi "bekas".
    var mileageField = document.querySelector('[data-mileage-field]');
    var conditionRadios = document.querySelectorAll('[data-condition-toggle]');

    if (mileageField && conditionRadios.length) {
        var syncMileage = function () {
            var checked = document.querySelector('[data-condition-toggle]:checked');
            mileageField.classList.toggle('d-none', !checked || checked.value !== 'bekas');
        };

        conditionRadios.forEach(function (radio) {
            radio.addEventListener('change', syncMileage);
        });
        syncMileage();
    }

    // Tombol cetak halaman: <button type="button" data-print>.
    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    // Pratinjau gambar sebelum upload: <input type="file" data-preview-target="#idGambar">.
    document.querySelectorAll('input[type="file"][data-preview-target]').forEach(function (input) {
        input.addEventListener('change', function () {
            var preview = document.querySelector(input.getAttribute('data-preview-target'));
            var file = input.files && input.files[0];

            if (!preview) {
                return;
            }

            if (file && file.type.indexOf('image/') === 0) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
            } else {
                preview.removeAttribute('src');
                preview.classList.add('d-none');
            }
        });
    });

    // Baris tabel dashboard: klik di mana saja membuka detail (link di sel pertama tetap untuk keyboard).
    document.querySelectorAll('tr[data-row-link]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }

            window.location.href = row.getAttribute('data-row-link');
        });
    });

    // Grafik dashboard: <canvas data-chart="activity|sales" data-chart-data='{...}'>.
    // Bila Chart.js gagal dimuat (CDN), tabel "Lihat angka" di bawahnya tetap berisi datanya.
    var chartCanvases = document.querySelectorAll('canvas[data-chart]');

    if (chartCanvases.length && window.Chart) {
        var number = new Intl.NumberFormat('id-ID');
        var rupiahShort = function (value) {
            if (value >= 1e9) {
                return 'Rp ' + number.format(Math.round(value / 1e8) / 10) + ' M';
            }

            if (value >= 1e6) {
                return 'Rp ' + number.format(Math.round(value / 1e6)) + ' jt';
            }

            return 'Rp ' + number.format(value);
        };

        window.Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

        var builders = {
            activity: function (data) {
                return {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets.map(function (set) {
                            return {
                                label: set.label,
                                data: set.data,
                                borderColor: set.color,
                                backgroundColor: set.color,
                                cubicInterpolationMode: 'monotone',
                                pointRadius: 3
                            };
                        })
                    },
                    options: {
                        interaction: { mode: 'index', intersect: false },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                };
            },
            sales: function (data) {
                return {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Unit terjual',
                                data: data.units,
                                backgroundColor: 'rgba(195, 0, 47, .75)',
                                yAxisID: 'y',
                                order: 2
                            },
                            {
                                type: 'line',
                                label: 'Nilai penjualan',
                                data: data.values,
                                borderColor: '#1c1c1e',
                                backgroundColor: '#1c1c1e',
                                cubicInterpolationMode: 'monotone',
                                yAxisID: 'value',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Unit' } },
                            value: {
                                position: 'right',
                                beginAtZero: true,
                                grid: { drawOnChartArea: false },
                                ticks: { callback: rupiahShort }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.yAxisID === 'value'
                                            ? context.dataset.label + ': Rp ' + number.format(context.parsed.y)
                                            : context.dataset.label + ': ' + number.format(context.parsed.y);
                                    }
                                }
                            }
                        }
                    }
                };
            }
        };

        chartCanvases.forEach(function (canvas) {
            var build = builders[canvas.getAttribute('data-chart')];

            if (!build) {
                return;
            }

            var config = build(JSON.parse(canvas.getAttribute('data-chart-data')));
            config.options.maintainAspectRatio = false;
            config.options.plugins = Object.assign({ legend: { position: 'bottom' } }, config.options.plugins);

            new window.Chart(canvas, config);
        });
    }
});
