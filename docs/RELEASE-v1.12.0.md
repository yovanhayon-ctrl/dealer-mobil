# JAF Dealer v1.12.0

Rilis fitur setelah [v1.11.2](RELEASE-v1.11.2.md): notifikasi untuk bukti dan pembayaran pengajuan. Sebelumnya hasil verifikasi pembayaran hanya terlihat bila customer membuka Pengajuan Saya, dan admin hanya melihat jumlah bukti yang menunggu di daftar pengajuan.

## Notifikasi pembayaran

| Kejadian | Penerima | Judul |
|---|---|---|
| Customer mengirim bukti transfer | Semua owner dan admin aktif | **Bukti Pembayaran Baru · nama customer** |
| Admin menerima bukti transfer | Customer | **Pembayaran Diterima** |
| Admin menolak bukti transfer | Customer | **Bukti Pembayaran Ditolak** |
| Admin mencatat pembayaran di showroom | Customer | **Pembayaran Diterima** |

- **Pembayaran Diterima** menyebut mobil, jenis, nominal, dan metode, lalu sisa tagihan (mis. *Sisa Rp 500.000.000*) atau *Lunas* / *DP lunas*. Badge di halaman notifikasi customer menampilkan status pembayaran saat itu: *Dibayar sebagian*, *DP sebagian*, *Lunas*, atau *DP lunas*.
- **Bukti Pembayaran Ditolak** memuat alasan penolakan dari admin sebagai *Catatan dealer*, sehingga customer tahu apa yang perlu diperbaiki sebelum mengirim ulang.
- Notifikasi admin membuka detail pengajuan langsung di bagian **Bukti Pembayaran**; badge-nya mengikuti status pengajuan dan pembayaran terkini. Mekanik dan staf nonaktif tidak menerimanya.
- Notifikasi customer membuka kartu pengajuan terkait di **Pengajuan Saya**.
- Sesama admin tidak diberi tahu saat bukti diterima atau ditolak. Aksi yang ditolak sistem (alasan kosong, bukti yang sudah diputuskan, total melebihi tagihan) tidak mengirim notifikasi.
- Seperti notifikasi lain: selalu masuk lonceng, dan dikirim juga lewat email bila `DEALER_MAIL_NOTIFICATIONS` aktif. Email yang gagal terkirim tidak membatalkan aksinya.

## Perbaikan

- Bukti transfer yang baru dibuat langsung bermetode *Transfer* (sama dengan nilai bawaan kolom), sehingga label metodenya selalu terisi.
- Skenario demo di [DEMO.md](DEMO.md) tidak lagi memakai filter *Merek* (tersembunyi selama dealer hanya punya satu merek); diganti filter *Kategori: Sport*.

## Pembaruan dari v1.11.2

```bash
git pull
php artisan optimize:clear
```

- Tidak ada migration, dependency, atau pengaturan `.env` baru.
- Notifikasi hanya dikirim untuk kejadian setelah pembaruan; bukti yang sudah ada tidak membuat notifikasi baru.

## Kualitas

- 966 test otomatis (PHPUnit) lulus di SQLite dan MySQL (7 test baru: penerima dan isi notifikasi bukti baru, diterima sebagian, DP lunas, ditolak dengan alasan, pembayaran showroom, aksi yang ditolak sistem, dan email penolakan).
