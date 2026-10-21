@extends('pages.legal.layout', [
    'pageTitle' => 'Syarat & Ketentuan',
    'sections' => [
        'akun' => 'Akun',
        'katalog' => 'Katalog, harga, dan promo',
        'simulasi' => 'Simulasi kredit',
        'pengajuan' => 'Pengajuan pembelian',
        'pembayaran' => 'Pembayaran',
        'jadwal' => 'Test drive dan servis',
        'ulasan' => 'Ulasan dan rating',
        'tanggung-jawab' => 'Batasan tanggung jawab',
        'lain-lain' => 'Perubahan dan hukum yang berlaku',
    ],
])

@section('title', 'Syarat & Ketentuan')
@section('meta_description', 'Syarat & Ketentuan penggunaan situs '.config('dealer.name').': akun, harga, pengajuan pembelian, pembayaran, test drive, dan servis.')

@section('legal_body')
    <p>
        Dengan memakai situs {{ $dealer['name'] }}, Anda menyetujui ketentuan berikut. Mohon dibaca bersama
        <a href="{{ route('privacy') }}">Kebijakan Privasi</a> kami.
    </p>

    <h2 id="akun">1. Akun</h2>
    <ul>
        <li>Data yang Anda isi saat mendaftar dan mengajukan permintaan harus benar dan milik Anda sendiri.</li>
        <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan atas aktivitas yang dilakukan dengan akun Anda.</li>
        <li>Kami dapat menonaktifkan akun yang dipakai untuk penipuan, data palsu, atau penyalahgunaan lainnya.</li>
    </ul>

    <h2 id="katalog">2. Katalog, harga, dan promo</h2>
    <ul>
        <li>Harga, promo, dan ketersediaan stok dapat berubah sewaktu-waktu. Harga yang berlaku adalah yang dikonfirmasi dealer saat pengajuan diproses.</li>
        <li>Promo berlaku sesuai periode dan syarat yang tertera pada masing-masing promo.</li>
        <li>Foto dan spesifikasi ditampilkan sebagai gambaran. Kondisi unit, terutama mobil bekas, dapat sedikit berbeda dan dapat diperiksa langsung di showroom.</li>
    </ul>

    <h2 id="simulasi">3. Simulasi kredit</h2>
    <p>
        Hasil simulasi kredit hanyalah perkiraan. Persetujuan kredit, bunga, uang muka, dan cicilan akhir ditentukan oleh lembaga
        pembiayaan setelah verifikasi.
    </p>

    <h2 id="pengajuan">4. Pengajuan pembelian</h2>
    <ul>
        <li>Pengajuan pembelian bukan transaksi final. Unit baru dipesan untuk Anda setelah pengajuan <strong>disetujui</strong> dealer.</li>
        <li>Dealer dapat menolak pengajuan, misalnya karena stok habis atau data belum lengkap, dengan alasan yang tampil di akun Anda.</li>
        <li>Anda dapat membatalkan pengajuan yang masih berstatus <strong>Menunggu</strong> lewat menu <strong>Pengajuan Saya</strong>. Setelah pengajuan diproses, hubungi dealer untuk membatalkan.</li>
    </ul>

    <h2 id="pembayaran">5. Pembayaran</h2>
    <ul>
        <li>Pembayaran hanya dilakukan ke rekening resmi atas nama dealer yang tampil di akun Anda setelah pengajuan disetujui, atau langsung di showroom.</li>
        <li>Kami <strong>tidak pernah</strong> meminta transfer ke rekening pribadi staf. Pembayaran ke rekening lain di luar tanggung jawab kami.</li>
        <li>Pembayaran dianggap diterima setelah bukti diverifikasi admin. Status pembayaran dapat dilihat di menu <strong>Pengajuan Saya</strong>.</li>
        <li>Pembayaran pertama (tanda jadi, DP, atau pelunasan) dilakukan paling lambat <strong>{{ config('dealer.payment_due_days') }} hari</strong> setelah pengajuan disetujui. Lewat batas tersebut, dealer dapat membatalkan pengajuan dan unit ditawarkan kembali.</li>
        <li>Bila pengajuan dibatalkan atau ditolak setelah ada pembayaran, pengembalian dana diproses sesuai kesepakatan dengan dealer. Setiap pengembalian dicatat dan dapat dilihat di menu <strong>Pengajuan Saya</strong>.</li>
    </ul>

    <h2 id="jadwal">6. Test drive dan servis</h2>
    <ul>
        <li>Jadwal test drive dan servis berlaku setelah dikonfirmasi dealer. Dealer dapat menghubungi Anda untuk mengatur ulang jadwal.</li>
        <li>Untuk test drive, pengemudi wajib membawa SIM yang masih berlaku dan mengikuti arahan staf.</li>
        <li>Jadwal yang masih berstatus <strong>Menunggu</strong> dapat dibatalkan lewat akun Anda. Setelah dikonfirmasi, hubungi dealer untuk membatalkan atau mengubah jadwal.</li>
    </ul>

    <h2 id="ulasan">7. Ulasan dan rating</h2>
    <p>
        Ulasan dan rating harus jujur, sopan, dan berdasarkan pengalaman Anda sendiri. Kami berhak tidak menampilkan ulasan yang
        mengandung kata kasar, data pribadi orang lain, promosi, atau isi yang tidak berkaitan.
    </p>

    <h2 id="tanggung-jawab">8. Batasan tanggung jawab</h2>
    <p>
        Kami berupaya menjaga informasi di situs ini tetap akurat dan situs tetap dapat diakses, tetapi tidak menjamin situs bebas
        dari gangguan atau kesalahan penulisan. Kesepakatan jual beli dan servis yang mengikat adalah yang dikonfirmasi dealer secara
        langsung atau tertulis.
    </p>

    <h2 id="lain-lain">9. Perubahan dan hukum yang berlaku</h2>
    <p>
        Ketentuan ini dapat diperbarui sewaktu-waktu; tanggal berlaku di bagian atas halaman menunjukkan versi terbaru.
        Ketentuan ini tunduk pada hukum Republik Indonesia.
    </p>
@endsection
