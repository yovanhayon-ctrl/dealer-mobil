<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Dealer
    |--------------------------------------------------------------------------
    |
    | Data dealer yang tampil di navbar, footer, halaman Kontak, dan tombol
    | WhatsApp. Nilai dibaca dari .env (lihat .env.example).
    |
    */

    'name' => env('DEALER_NAME', 'JAF Dealer'),

    'tagline' => env('DEALER_TAGLINE', ''),

    'address' => env('DEALER_ADDRESS', ''),

    'phone' => env('DEALER_PHONE', ''),

    // Format internasional tanpa "+" (contoh: 6281234567890) untuk link wa.me.
    'whatsapp' => env('DEALER_WHATSAPP', ''),

    'email' => env('DEALER_EMAIL', ''),

    'hours' => env('DEALER_HOURS', ''),

    'maps_embed_url' => env('DEALER_MAPS_EMBED_URL', ''),

    // Notifikasi (status untuk customer, booking baru untuk admin) juga dikirim lewat email.
    // Pengiriman memakai pengaturan MAIL_* (MAIL_MAILER=log = hanya dicatat di storage/logs/laravel.log).
    'mail_notifications' => (bool) env('DEALER_MAIL_NOTIFICATIONS', true),

    // Video latar hero beranda (MP4 di folder public; boleh beberapa, dipisah koma, diputar bergantian di layar lebar).
    // Kosongkan untuk memakai banner tanpa video (garis diagonal + kartu mobil unggulan).
    'hero_videos' => array_values(array_filter(array_map('trim', explode(',', (string) env('DEALER_HERO_VIDEOS', 'videos/video2.mp4'))))),

    /*
    |--------------------------------------------------------------------------
    | JAF Service (Bengkel)
    |--------------------------------------------------------------------------
    |
    | Jumlah booking servis aktif (menunggu / dikonfirmasi / dikerjakan) maksimal
    | per slot jam, sesuai jumlah stall bengkel.
    |
    */

    'service_slot_capacity' => 3,

    /*
    |--------------------------------------------------------------------------
    | Akun Admin Awal
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh AdminUserSeeder. Jangan di-hardcode, isi di .env.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Dummy (hanya local/testing)
    |--------------------------------------------------------------------------
    |
    | Kata sandi customer dummy dari CustomerSeeder. Jangan di-hardcode, isi di .env.
    | Jika kosong, CustomerSeeder dilewati.
    |
    */

    'seed' => [
        'customer_password' => env('SEED_CUSTOMER_PASSWORD'),
    ],

];
