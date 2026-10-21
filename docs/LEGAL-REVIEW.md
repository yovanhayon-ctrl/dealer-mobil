# Review Kebijakan Privasi dan Syarat & Ketentuan

Dokumen ini untuk **dealer (Owner)** sebelum website dipakai customer. Teks yang tampil di situs saat ini masih umum, disusun mengikuti cara kerja aplikasi, dan **belum diperiksa dealer atau ahli hukum**.

- Halaman: `/kebijakan-privasi` dan `/syarat-ketentuan` (tautan di footer dan form pendaftaran).
- Tanggal berlaku saat ini: **20 Oktober 2026** (`PageController::LEGAL_EFFECTIVE_DATE`).
- Kontak di bagian bawah kedua halaman diambil otomatis dari **Admin > Pengaturan** (email, WhatsApp, alamat).

Cara pakai: baca ringkasan di bagian 1, isi kolom **Jawaban dealer** di bagian 3, lalu kirim kembali. Teks halaman akan disesuaikan dari jawaban tersebut dan tanggal berlakunya diperbarui.

> Dokumen ini membantu menyiapkan bahan, **bukan nasihat hukum**. Untuk kepastian, minta konsultan hukum atau notaris dealer membaca versi akhir, terutama terkait Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP) dan perlindungan konsumen.

## 1. Ringkasan isi saat ini

### Kebijakan Privasi

| Bagian | Isi singkat |
|---|---|
| 1. Data yang dikumpulkan | Akun (nama, email, WhatsApp, kata sandi terenkripsi); test drive; pengajuan (telepon, alamat, rincian kredit); bukti pembayaran (disimpan privat); booking servis (kendaraan, nomor polisi, keluhan); ulasan dan rating mekanik; cookie sesi, alamat IP, pilihan tema. |
| 2. Penggunaan | Memproses permintaan, menghubungi lewat WhatsApp/telepon/email, notifikasi, PDF pengajuan, keamanan akun. **Tidak dijual**, tidak untuk iklan pihak ketiga. |
| 3. Siapa yang melihat | Customer sendiri dan staf sesuai tugas (Pemilik & Admin Dealer seluruh data; Super Admin, Sales, CS tanpa file bukti bayar; Mekanik hanya servis yang ditugaskan). Data kredit dapat diteruskan ke lembaga pembiayaan setelah dibicarakan dengan customer. |
| 4. Pihak ketiga | Google Fonts, jsDelivr, Google Maps, WhatsApp, penyedia email. |
| 5. Lama penyimpanan | Data akun selama akun aktif; data transaksi "dapat disimpan lebih lama" untuk pembukuan (belum ada angka). |
| 6. Hak customer | Ubah profil; batalkan permintaan yang masih Menunggu; minta salinan, perbaikan, atau penghapusan data lewat kontak dealer. |
| 7. Keamanan | HTTPS, kata sandi terenkripsi, akses per peran, bukti bayar privat; dealer tidak pernah meminta kata sandi/OTP. |
| 8. Perubahan | Dapat diperbarui; tanggal berlaku menunjukkan versi terbaru. |

### Syarat & Ketentuan

| Bagian | Isi singkat |
|---|---|
| 1. Akun | Data harus benar; customer menjaga kata sandi; akun penipuan dapat dinonaktifkan. |
| 2. Katalog, harga, promo | Harga dan stok dapat berubah; harga berlaku yang dikonfirmasi saat pengajuan diproses; foto sebagai gambaran. |
| 3. Simulasi kredit | Hanya perkiraan; keputusan di lembaga pembiayaan. |
| 4. Pengajuan | Bukan transaksi final; unit dipesan setelah **disetujui**; dealer boleh menolak dengan alasan; customer membatalkan sendiri hanya saat Menunggu. |
| 5. Pembayaran | Hanya ke rekening resmi atas nama dealer atau di showroom; tidak pernah ke rekening pribadi staf; dianggap diterima setelah diverifikasi. |
| 6. Test drive & servis | Berlaku setelah dikonfirmasi; test drive wajib SIM berlaku; batal sendiri hanya saat Menunggu. |
| 7. Ulasan | Harus jujur dan sopan; dealer boleh tidak menampilkan. |
| 8. Batasan tanggung jawab | Situs tidak dijamin bebas gangguan; kesepakatan mengikat adalah yang dikonfirmasi dealer. |
| 9. Hukum | Dapat diperbarui; tunduk pada hukum Republik Indonesia. |

## 2. Aturan yang sudah dijalankan aplikasi tetapi belum tertulis

Hal-hal ini **sudah berlaku di website** (customer melihatnya di akun), tetapi belum tercantum di Syarat & Ketentuan / Kebijakan Privasi. Disarankan ditambahkan agar customer tahu sejak awal.

| No | Sudah berjalan di aplikasi | Usulan teks (disesuaikan dengan jawaban di bagian 3) |
|---|---|---|
| A | **Batas pembayaran pertama**: 3 hari setelah pengajuan disetujui (`DEALER_PAYMENT_DUE_DAYS`); lewat batas, pengajuan ditandai dan dealer boleh membatalkannya agar unit dilepas. Customer diingatkan sehari sebelumnya. | Syarat & Ketentuan bagian 5: "Pembayaran pertama (tanda jadi, DP, atau pelunasan) dilakukan paling lambat 3 hari setelah pengajuan disetujui. Lewat batas tersebut, dealer dapat membatalkan pengajuan dan unit ditawarkan kembali." |
| B | **Pengembalian dana**: dealer mencatat pengembalian untuk pengajuan yang dibatalkan/ditolak setelah ada pembayaran, atau menutupnya dengan alasan. | Syarat & Ketentuan bagian 5: aturan uang kembali (lihat pertanyaan 2). |
| C | **Pengingat H-1** test drive, servis, dan batas bayar lewat notifikasi/email; staf dapat mengirim pengingat lewat WhatsApp. | Sudah tercakup "menghubungi lewat WhatsApp/email" di Privasi bagian 2; cukup ditegaskan. |
| D | **Cadangan data harian** (database dan bukti pembayaran) disimpan 30 hari di server. | Kebijakan Privasi bagian 5: "Cadangan data harian disimpan paling lama 30 hari untuk pemulihan bila terjadi gangguan." |
| E | **Nama di ulasan** yang tampil di beranda disingkat: nama depan + inisial nama belakang (mis. "Budi S."). Teks saat ini menyebut "bersama nama Anda". | Kebijakan Privasi bagian 1: "tampil di beranda dengan nama depan dan inisial nama belakang". |

## 3. Pertanyaan untuk dealer

Isi kolom jawaban. Kolom **Usulan** adalah pilihan yang umum dipakai dealer, boleh diganti.

| No | Pertanyaan | Usulan | Jawaban dealer |
|---|---|---|---|
| 1 | Batas pembayaran pertama setelah pengajuan disetujui tetap **3 hari**? | 3 hari | |
| 2 | Bila pengajuan **dibatalkan customer** setelah membayar tanda jadi, uangnya: dikembalikan penuh, dipotong biaya, atau hangus? Bila **ditolak/dibatalkan dealer** (mis. unit tidak tersedia)? | Dibatalkan customer: tanda jadi tidak kembali kecuali disepakati lain. Dibatalkan/ditolak dealer: dikembalikan penuh paling lambat 14 hari kerja. | |
| 3 | Berapa lama pengembalian dana diproses? | 14 hari kerja | |
| 4 | Data transaksi (pengajuan, bukti bayar) disimpan berapa lama setelah transaksi selesai? | 10 tahun (umum dipakai untuk dokumen keuangan perusahaan) | |
| 5 | Akun yang **tidak aktif** sekian lama dihapus otomatis, atau disimpan sampai customer minta hapus? | Disimpan sampai customer minta hapus | |
| 6 | Permintaan salinan/hapus data dijawab paling lama berapa hari? Siapa yang menangani (nama/jabatan atau email khusus)? | 3 hari kerja; ditangani Owner lewat email dealer | |
| 7 | Data pengajuan kredit diteruskan ke lembaga pembiayaan mana? Perlu disebut namanya? | Cukup "lembaga pembiayaan rekanan", disebut saat dibicarakan dengan customer | |
| 8 | Usia minimal membuat akun dan mengajukan pembelian? | 17 tahun (punya KTP) | |
| 9 | Test drive: syarat tambahan (KTP, usia minimal, rute ditentukan dealer)? Siapa menanggung kerusakan bila terjadi kecelakaan saat test drive? | Wajib SIM A dan KTP asli; rute dan pendamping dari dealer; tanggung jawab kerusakan sesuai kesepakatan tertulis di showroom | |
| 10 | Servis: ada garansi pekerjaan servis? Berapa lama kendaraan boleh ditinggal setelah selesai? Harga di website "mulai dari" atau pasti? | Harga "mulai dari", total final setelah pemeriksaan; garansi sesuai nota servis | |
| 11 | Harga yang tampil sudah termasuk apa (on the road, BBN, asuransi)? | "Harga on the road wilayah dealer kecuali dinyatakan lain" | |
| 12 | Sengketa diselesaikan di pengadilan negeri mana? | Pengadilan Negeri di kota showroom dealer | |
| 13 | Nama badan usaha resmi dealer (mis. PT/CV) untuk ditulis di halaman? | (isi nama resmi) | |
| 14 | Ada hal lain yang wajib dicantumkan (kebijakan internal, ketentuan pabrikan Nissan, dll.)? | - | |

## 4. Setelah jawaban diterima

1. Teks Kebijakan Privasi dan Syarat & Ketentuan disesuaikan (termasuk poin A-E di bagian 2).
2. Tanggal berlaku diperbarui ke tanggal persetujuan dealer.
3. Bila batas pembayaran berubah dari 3 hari, nilai `DEALER_PAYMENT_DUE_DAYS` di `.env` server ikut diubah agar aplikasi dan teks sama.
4. Versi akhir dibaca sekali lagi oleh dealer (dan konsultan hukum bila ada), lalu dicentang di [GO-LIVE.md](GO-LIVE.md) bagian 1.
