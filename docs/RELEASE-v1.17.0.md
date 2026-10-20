# JAF Dealer v1.17.0

Rilis fitur setelah [v1.16.0](RELEASE-v1.16.0.md): pengingat batas pembayaran pertama ke customer.

## Pengingat batas pembayaran

- Customer menerima **Pengingat: Batas Pembayaran Besok** sehari sebelum batas pembayaran pertama (tenggat `DEALER_PAYMENT_DUE_DAYS`, bawaan 3 hari sejak disetujui). Contoh: disetujui Senin → batas Kamis 23:59 → pengingat Rabu pukul 16:00.
- Isi: mobil dan metode bayar, tanggal batas, rekening resmi dari `DEALER_BANK_*` (bila belum diisi tertulis *rekening resmi dealer*), pilihan bayar langsung di showroom, dan ajakan mengirim bukti di **Pengajuan Saya**. Masuk lonceng, dan email bila `DEALER_MAIL_NOTIFICATIONS=true`.
- Hanya untuk pengajuan **Disetujui** yang **belum ada pembayaran diterima** dan **tidak ada bukti yang sedang diverifikasi** (aturan yang sama dengan penanda *Lewat tenggat*). Dikirim sekali per pengajuan (kolom baru `payment_reminded_at`).
- Tidak ada pemberitahuan tambahan saat batas terlewat: customer sudah melihat peringatan di Pengajuan Saya, dan staf bisa menghubungi lewat WhatsApp sebelum membatalkan.

## Cara menjalankan

Memakai mekanisme pengingat [v1.16.0](RELEASE-v1.16.0.md):

- Ikut perintah `reminders:send` yang dijadwalkan setiap hari pukul `DEALER_REMINDER_TIME` (butuh cron `schedule:run`, lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 7).
- Ikut tombol **Kirim Pengingat Besok** di dashboard; pesan hasil kini menyebut jumlah pengingat pembayaran.
- `php artisan reminders:send --dry-run` ikut menampilkan pengajuan yang akan diingatkan.

## Detail pengajuan (admin)

- Untuk pengajuan yang menunggu pembayaran pertama: *"Batas pembayaran pertama Rabu, 21 Okt 2026 · pengingat terkirim …"*.
- Tombol **Ingatkan via WhatsApp** (Owner, Super Admin, Admin Dealer, Sales): membuka WhatsApp ke nomor customer dengan pesan berisi nomor pengajuan, batas bayar, dan rekening dealer. Customer Service hanya melihat tanggal batasnya.

## Pembaruan dari v1.16.0

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: kolom `payment_reminded_at` di `purchase_requests`.
- Tidak ada pengaturan `.env` baru (memakai `DEALER_PAYMENT_DUE_DAYS`, `DEALER_REMINDER_TIME`, dan `DEALER_BANK_*` yang sudah ada).

## Kualitas

- 1047 test otomatis (PHPUnit) lulus di SQLite dan MySQL (4 test baru: hanya batas besok yang belum dibayar diingatkan sekali, mengikuti lama tenggat & rekening kosong, `--dry-run`, tombol dashboard, detail pengajuan & tombol WhatsApp per peran).
- Perbaikan test: satu test dashboard yang sesekali gagal (tanggal acak data uji bisa jatuh besok) kini memakai tanggal tetap.
