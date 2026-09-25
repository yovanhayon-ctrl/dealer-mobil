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
});
