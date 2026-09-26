<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Simulasi Kredit (bunga flat)
    |--------------------------------------------------------------------------
    |
    | Dipakai App\Support\CreditCalculator (RANCANGAN §5):
    | Pokok = Harga − DP; Bunga = Pokok × rate% × (tenor/12);
    | Cicilan = (Pokok + Bunga) / tenor, dibulatkan ke atas per `rounding`.
    |
    */

    // Tenor (bulan) => bunga flat per tahun (%).
    'rates' => [
        12 => 5,
        24 => 5.5,
        36 => 6,
        48 => 6.5,
        60 => 7,
    ],

    // Batas uang muka (persen dari harga).
    'dp_min' => 20,
    'dp_max' => 90,

    // Cicilan dibulatkan ke atas per Rp 1.000.
    'rounding' => 1000,

];
