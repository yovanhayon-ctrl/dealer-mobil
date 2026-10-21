@extends('pages.legal.layout', [
    'pageTitle' => 'Kebijakan Privasi',
    'sections' => [
        'data-dikumpulkan' => 'Data yang kami kumpulkan',
        'penggunaan' => 'Untuk apa data dipakai',
        'akses' => 'Siapa yang bisa melihat data',
        'pihak-ketiga' => 'Layanan pihak ketiga',
        'penyimpanan' => 'Berapa lama data disimpan',
        'hak' => 'Hak Anda',
        'keamanan' => 'Keamanan',
        'perubahan' => 'Perubahan kebijakan',
    ],
])

@section('title', 'Kebijakan Privasi')
@section('meta_description', 'Kebijakan Privasi '.config('dealer.name').': data apa yang dikumpulkan, untuk apa dipakai, siapa yang bisa melihat, dan hak Anda atas data pribadi.')

@section('legal_body')
    <p>
        {{ $dealer['name'] }} ("kami") menghormati privasi Anda. Kebijakan ini menjelaskan data pribadi apa yang kami kumpulkan saat
        Anda memakai situs ini, untuk apa data itu dipakai, dan bagaimana kami menjaganya, sesuai Undang-Undang Nomor 27 Tahun 2022
        tentang Pelindungan Data Pribadi.
    </p>

    <h2 id="data-dikumpulkan">1. Data yang kami kumpulkan</h2>
    <p>Kami hanya mengumpulkan data yang Anda isi sendiri saat memakai fitur berikut:</p>
    <ul>
        <li><strong>Akun:</strong> nama, email, nomor WhatsApp, dan kata sandi (disimpan dalam bentuk terenkripsi, tidak bisa dibaca siapa pun termasuk staf kami).</li>
        <li><strong>Test drive:</strong> mobil yang dipilih, tanggal dan jam, nomor telepon, dan catatan.</li>
        <li><strong>Pengajuan pembelian:</strong> nomor telepon, alamat, metode pembayaran, serta rincian kredit seperti uang muka, tenor, dan cicilan.</li>
        <li><strong>Bukti pembayaran:</strong> foto atau PDF bukti transfer beserta nominal dan tanggalnya. Karena bisa memuat data rekening, file ini disimpan di penyimpanan privat yang tidak bisa dibuka publik.</li>
        <li><strong>Booking servis:</strong> model dan tahun kendaraan, nomor polisi, kilometer, dan keluhan.</li>
        <li><strong>Ulasan dan rating mekanik:</strong> bintang dan komentar Anda. Ulasan pembelian yang disetujui tampil di beranda bersama nama Anda; rating mekanik hanya dipakai di internal.</li>
        <li><strong>Data teknis:</strong> cookie sesi agar Anda tetap masuk (berakhir setelah {{ config('session.lifetime') }} menit tidak aktif), alamat IP dan jenis browser yang tercatat bersama sesi, serta pilihan tema terang/gelap yang disimpan di browser Anda sendiri.</li>
    </ul>

    <h2 id="penggunaan">2. Untuk apa data dipakai</h2>
    <ul>
        <li>Memproses test drive, pengajuan pembelian, pembayaran, dan servis yang Anda minta.</li>
        <li>Menghubungi Anda lewat WhatsApp, telepon, atau email terkait permintaan tersebut.</li>
        <li>Mengirim notifikasi perubahan status di situs dan, bila diaktifkan dealer, lewat email.</li>
        <li>Membuat dokumen pengajuan (PDF) dan catatan pembayaran.</li>
        <li>Menjaga keamanan akun dan mencegah penyalahgunaan.</li>
    </ul>
    <p>Kami <strong>tidak menjual</strong> data Anda dan tidak memakainya untuk iklan pihak ketiga.</p>

    <h2 id="akses">3. Siapa yang bisa melihat data</h2>
    <p>
        Data Anda hanya bisa dilihat oleh Anda sendiri dan staf {{ $dealer['name'] }} sesuai tugasnya. Pengguna lain tidak bisa melihat data Anda.
    </p>
    <ul>
        <li><strong>Pemilik dan admin dealer:</strong> seluruh data untuk memproses permintaan, pembayaran, dan laporan dealer.</li>
        <li><strong>Super admin:</strong> data yang diperlukan untuk mengelola akun staf dan menjaga sistem tetap berjalan. Super admin tidak membuka file bukti pembayaran.</li>
        <li><strong>Sales:</strong> data test drive dan pengajuan pembelian untuk melayani pembelian Anda. Sales tidak membuka file bukti pembayaran.</li>
        <li><strong>Customer service:</strong> data kontak, test drive, booking servis, pengajuan, dan ulasan untuk menjawab pertanyaan Anda. Customer service tidak membuka file bukti pembayaran.</li>
        <li><strong>Mekanik:</strong> data kendaraan dan servis yang ditugaskan kepadanya.</li>
    </ul>
    <p>
        Bila Anda mengajukan kredit, data pengajuan dapat diteruskan ke lembaga pembiayaan yang memproses kredit tersebut,
        setelah dibicarakan dengan Anda.
    </p>

    <h2 id="pihak-ketiga">4. Layanan pihak ketiga</h2>
    <p>Situs ini memakai beberapa layanan luar yang dapat menerima alamat IP Anda saat halaman dimuat:</p>
    <ul>
        <li><strong>Google Fonts</strong> dan <strong>jsDelivr</strong> untuk huruf dan tampilan halaman.</li>
        <li><strong>Google Maps</strong> untuk peta lokasi di halaman Kontak.</li>
        <li><strong>WhatsApp</strong>, hanya saat Anda menekan tombol WhatsApp.</li>
        <li>Penyedia layanan email untuk mengirim notifikasi dan tautan atur ulang kata sandi.</li>
    </ul>
    <p>Penggunaan data oleh layanan tersebut mengikuti kebijakan privasi masing-masing.</p>

    <h2 id="penyimpanan">5. Berapa lama data disimpan</h2>
    <p>
        Data akun disimpan selama akun Anda aktif. Data transaksi, seperti pengajuan pembelian dan bukti pembayaran,
        dapat disimpan lebih lama karena diperlukan untuk pembukuan dan kewajiban hukum dealer.
    </p>

    <h2 id="hak">6. Hak Anda</h2>
    <ul>
        <li>Melihat dan mengubah nama, email, nomor WhatsApp, dan kata sandi kapan saja di menu <strong>Profil</strong>.</li>
        <li>Membatalkan test drive, pengajuan, atau booking servis yang masih berstatus <strong>Menunggu</strong> lewat akun Anda.</li>
        <li>Meminta salinan, perbaikan, atau penghapusan data pribadi Anda dengan menghubungi kami lewat kontak di bawah. Data yang wajib disimpan untuk pembukuan akan dijelaskan saat permintaan diproses.</li>
    </ul>

    <h2 id="keamanan">7. Keamanan</h2>
    <p>
        Kami menjaga data dengan koneksi terenkripsi (HTTPS), kata sandi yang disimpan terenkripsi, pembatasan akses sesuai peran staf,
        dan penyimpanan bukti pembayaran yang tidak bisa dibuka publik. Kami tidak pernah meminta kata sandi atau kode OTP Anda.
    </p>

    <h2 id="perubahan">8. Perubahan kebijakan</h2>
    <p>
        Kebijakan ini dapat diperbarui bila fitur situs berubah. Tanggal berlaku di bagian atas halaman menunjukkan versi terbaru.
    </p>
@endsection
