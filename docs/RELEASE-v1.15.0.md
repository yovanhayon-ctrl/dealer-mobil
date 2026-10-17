# JAF Dealer v1.15.0

Rilis fitur setelah [v1.14.0](RELEASE-v1.14.0.md): tindak lanjut jadwal yang terlewat dan pengajuan yang belum ditanggapi.

## Status "Tidak datang"

Untuk test drive dan booking servis:

- Pilihan status baru **Tidak datang**, hanya untuk jadwal **Dikonfirmasi** pada atau sesudah tanggal jadwalnya (jadwal yang masih di masa depan tidak bisa ditandai). Jadwal yang masih *Menunggu* dan terlewat tetap dibatalkan, karena belum pernah dikonfirmasi dealer.
- Catatan untuk customer terisi otomatis: *"Anda tidak hadir pada jadwal 15 Okt 2026 pukul 10:00 WIB. Silakan booking ulang bila masih berminat."* Customer menerima notifikasi.
- Badge abu-abu **Tidak datang**, terpisah dari *Dibatalkan*: daftar admin dan **Laporan** kini membedakan keduanya. Di riwayat customer, status ini masuk kelompok *Dibatalkan*.
- Booking servis yang tidak datang hilang dari **Pekerjaan Saya** mekanik dan melepas slot bengkel.

## Jadwalkan ulang

Kartu **Jadwalkan Ulang** di detail test drive dan booking servis (admin):

- Untuk jadwal *Menunggu*, *Dikonfirmasi* (termasuk yang lewat jadwal), dan *Tidak datang* (customer menelepon minta jadwal baru).
- Aturan sama dengan booking customer: paling cepat besok, jam sesuai slot, tidak bentrok (test drive: mobil yang sama pada jam yang sama; servis: kapasitas slot & jadwal mekanik yang ditugaskan). Booking *Tidak datang* tidak bisa dibuka lagi bila customer sudah membuat booking baru untuk mobil/kendaraan yang sama.
- Setelah disimpan: status menjadi **Dikonfirmasi**, catatan terisi *"Jadwal diubah dari … ke …"* (atau *"Customer tidak hadir pada jadwal …; dijadwalkan ulang ke …"*), customer menerima notifikasi **Jadwal Test Drive/Servis Diubah**, dan mekanik yang ditugaskan diberi tahu.
- Hak akses mengikuti menu: test drive oleh Owner, Super Admin, Admin Dealer, Sales, Customer Service; servis oleh Owner, Super Admin, Admin Dealer, Customer Service.
- Customer tetap tidak mengubah jadwal sendiri (batal lalu booking ulang).

## Pengajuan belum ditanggapi

- Pengajuan yang masih **Menunggu** lebih dari **2 hari** sejak diajukan diberi badge **Belum ditanggapi** (customer dijanjikan dihubungi dalam 1 hari kerja).
- Filter baru *Tindak lanjut: Belum ditanggapi* di daftar Pengajuan, dan baris *"N pengajuan belum ditanggapi"* di strip **Perlu ditindaklanjuti** dashboard untuk peran yang memproses pengajuan (Owner, Super Admin, Admin Dealer, Sales).

## Pembaruan dari v1.14.0

```bash
git pull
php artisan migrate --force
php artisan optimize
```

- **1 migration**: menambah status `no_show` di tabel `test_drives` dan `service_bookings`. Data yang sudah ada tidak berubah.
- Data contoh (`db:seed`, hanya lokal) kini memuat contoh *Tidak datang*: 1 test drive dan 1 booking servis tambahan (9 booking servis).
- Cadangkan database sebelum update (lihat [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md) bagian 12).

## Kualitas

- 1036 test otomatis (PHPUnit) lulus di SQLite dan MySQL (8 test baru: aturan Tidak datang, slot servis dilepas, jadwal ulang test drive & servis beserta validasi, kapasitas, notifikasi customer & mekanik, jadwal ulang dari Tidak datang, hak per peran, dan pengajuan belum ditanggapi).
- Diuji manual di aplikasi lokal: booking test drive & servis customer dijadwalkan ulang oleh Sales dan Customer Service, Sales ditolak untuk servis (403), pengajuan belum ditanggapi muncul di filter & dashboard lalu hilang setelah diproses.
