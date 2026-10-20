# JAF Dealer v1.16.0

Rilis fitur setelah [v1.15.1](RELEASE-v1.15.1.md): pengingat H-1 untuk test drive dan booking servis.

## Pengingat H-1

| Penerima | Notifikasi | Syarat |
|---|---|---|
| Customer | **Pengingat: Test Drive Besok** / **Pengingat: Servis Besok** | Jadwal **Dikonfirmasi** dan tanggalnya besok |
| Mekanik | **Pekerjaan Servis Besok** | Booking servis yang ditugaskan kepadanya, dikonfirmasi untuk besok |

- Isi untuk customer: mobil atau layanan, kendaraan, jam, alamat showroom, *"Bawa SIM yang masih berlaku"* (test drive) atau *"Mohon datang 15 menit lebih awal"* (servis), dan nomor WhatsApp dealer bila berhalangan. Masuk lonceng, dan email bila `DEALER_MAIL_NOTIFICATIONS=true`.
- Jadwal yang masih **Menunggu** tidak diingatkan; dashboard staf menampilkan *"N test drive/booking servis besok belum dikonfirmasi"* di strip **Perlu ditindaklanjuti**.
- **Tidak terkirim dua kali**: kolom baru `reminded_at` diisi sebelum mengirim. Jadwal yang **dijadwalkan ulang** dikosongkan lagi tandanya, sehingga jadwal baru diingatkan sendiri.

## Cara menjalankan

1. **Otomatis**: perintah `reminders:send` dijadwalkan setiap hari pukul `DEALER_REMINDER_TIME` (bawaan **16:00 WIB**). Butuh satu cron di hosting: `cd ~/dealer-mobil && php artisan schedule:run` setiap menit (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 7, *Cron untuk pengingat H-1*).
2. **Tanpa cron / untuk demo**: tombol **Kirim Pengingat Besok** di dashboard (Owner, Super Admin, Admin Dealer). Aman ditekan berulang: jadwal yang sudah diingatkan dilewati.
3. **Terminal**: `php artisan reminders:send`; `--dry-run` hanya menampilkan jadwal yang akan diingatkan tanpa mengirim.

## Pengingat WhatsApp manual

- Di detail test drive dan booking servis (status Menunggu/Dikonfirmasi, tanggal belum lewat) ada tombol **Ingatkan via WhatsApp**: membuka WhatsApp ke nomor customer dengan pesan pengingat yang sudah terisi. Pesan dikirim staf sendiri (tanpa WhatsApp Business API).
- Detail admin juga menampilkan *"Pengingat H-1 terkirim …"* bila pengingat otomatis sudah dikirim.

## Pembaruan dari v1.15.1

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: kolom `reminded_at` di `test_drives` dan `service_bookings`.
- Opsional di `.env`: `DEALER_REMINDER_TIME=16:00`.
- Pasang cron (bagian 7 panduan deploy). Tanpa cron, gunakan tombol **Kirim Pengingat Besok** setiap sore.
- Ini fitur pertama yang memakai scheduler; panduan deploy kini menyarankan cron (sebelumnya tidak diperlukan).

## Kualitas

- 1043 test otomatis (PHPUnit) lulus di SQLite dan MySQL (7 test baru: hanya jadwal Dikonfirmasi besok yang diingatkan, tanpa pengingat ganda, servis ke customer & mekanik, `--dry-run`, jadwal ulang mengosongkan tanda, tombol dashboard per peran, tombol WhatsApp, dan jadwal scheduler 16:00).
- Dicek di data lokal: `reminders:send --dry-run` menemukan test drive besok yang dikonfirmasi; tampilan tombol dashboard dan tombol WhatsApp diperiksa lewat screenshot.
