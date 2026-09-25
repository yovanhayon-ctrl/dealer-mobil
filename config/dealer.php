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

    'name' => env('DEALER_NAME', 'Dealer Mobil'),

    'tagline' => env('DEALER_TAGLINE', ''),

    'address' => env('DEALER_ADDRESS', ''),

    'phone' => env('DEALER_PHONE', ''),

    // Format internasional tanpa "+" (contoh: 6281234567890) untuk link wa.me.
    'whatsapp' => env('DEALER_WHATSAPP', ''),

    'email' => env('DEALER_EMAIL', ''),

    'hours' => env('DEALER_HOURS', ''),

    'maps_embed_url' => env('DEALER_MAPS_EMBED_URL', ''),

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

];
