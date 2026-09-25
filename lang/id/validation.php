<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Pesan error bawaan validator dalam bahasa Indonesia.
    |
    */

    'accepted' => ':Attribute harus diterima.',
    'accepted_if' => ':Attribute harus diterima jika :other bernilai :value.',
    'active_url' => ':Attribute harus berupa URL yang valid.',
    'after' => ':Attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':Attribute tidak valid.',
    'array' => ':Attribute harus berupa array.',
    'array_keys' => ':Attribute hanya boleh berisi key berikut: :values.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik dan simbol single-byte.',
    'base64' => ':Attribute harus berupa string Base64 yang valid.',
    'before' => ':Attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus berisi antara :min sampai :max item.',
        'file' => ':Attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => ':Attribute harus bernilai antara :min sampai :max.',
        'string' => ':Attribute harus berisi antara :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'can' => ':Attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':Attribute tidak memuat nilai yang diwajibkan.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute harus berupa tanggal yang valid.',
    'date_equals' => ':Attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => ':Attribute harus sesuai format :format.',
    'decimal' => ':Attribute harus memiliki :decimal angka desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak jika :other bernilai :value.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus terdiri dari :digits digit.',
    'digits_between' => ':Attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Dimensi gambar :attribute tidak valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'doesnt_contain' => ':Attribute tidak boleh memuat salah satu dari: :values.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri salah satu dari: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali salah satu dari: :values.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'encoding' => ':Attribute harus menggunakan encoding :encoding.',
    'ends_with' => ':Attribute harus diakhiri salah satu dari: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => ':Attribute harus berupa file.',
    'filled' => ':Attribute harus memiliki nilai.',
    'gt' => [
        'array' => ':Attribute harus berisi lebih dari :value item.',
        'file' => ':Attribute harus berukuran lebih dari :value kilobyte.',
        'numeric' => ':Attribute harus lebih besar dari :value.',
        'string' => ':Attribute harus berisi lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus berisi :value item atau lebih.',
        'file' => ':Attribute harus berukuran lebih dari atau sama dengan :value kilobyte.',
        'numeric' => ':Attribute harus lebih besar dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi :value karakter atau lebih.',
    ],
    'hex_color' => ':Attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute harus ada di :other.',
    'in_array_keys' => ':Attribute harus berisi minimal salah satu key berikut: :values.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa string JSON yang valid.',
    'list' => ':Attribute harus berupa daftar.',
    'lowercase' => ':Attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => ':Attribute harus berisi kurang dari :value item.',
        'file' => ':Attribute harus berukuran kurang dari :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari :value.',
        'string' => ':Attribute harus berisi kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :value item.',
        'file' => ':Attribute harus berukuran kurang dari atau sama dengan :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari atau sama dengan :value.',
        'string' => ':Attribute harus berisi :value karakter atau kurang.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :max item.',
        'file' => ':Attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => ':Attribute tidak boleh lebih dari :max digit.',
    'mimes' => ':Attribute harus berupa file bertipe: :values.',
    'mimetypes' => ':Attribute harus berupa file bertipe: :values.',
    'min' => [
        'array' => ':Attribute harus berisi minimal :min item.',
        'file' => ':Attribute harus berukuran minimal :min kilobyte.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal berisi :min karakter.',
    ],
    'min_digits' => ':Attribute minimal terdiri dari :min digit.',
    'missing' => ':Attribute tidak boleh ada.',
    'missing_if' => ':Attribute tidak boleh ada jika :other bernilai :value.',
    'missing_unless' => ':Attribute tidak boleh ada kecuali :other bernilai :value.',
    'missing_with' => ':Attribute tidak boleh ada jika :values ada.',
    'missing_with_all' => ':Attribute tidak boleh ada jika :values ada.',
    'multiple_of' => ':Attribute harus kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus berisi minimal satu huruf.',
        'mixed' => ':Attribute harus berisi minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus berisi minimal satu angka.',
        'symbols' => ':Attribute harus berisi minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah muncul dalam kebocoran data. Silakan pilih :attribute lain.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada jika :other bernilai :value.',
    'present_unless' => ':Attribute harus ada kecuali :other bernilai :value.',
    'present_with' => ':Attribute harus ada jika :values ada.',
    'present_with_all' => ':Attribute harus ada jika :values ada.',
    'prohibited' => ':Attribute tidak diizinkan.',
    'prohibited_if' => ':Attribute tidak diizinkan jika :other bernilai :value.',
    'prohibited_if_accepted' => ':Attribute tidak diizinkan jika :other diterima.',
    'prohibited_if_declined' => ':Attribute tidak diizinkan jika :other ditolak.',
    'prohibited_unless' => ':Attribute tidak diizinkan kecuali :other ada di :values.',
    'prohibits' => ':Attribute melarang :other untuk diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_array_keys' => ':Attribute harus berisi entri untuk: :values.',
    'required_if' => ':Attribute wajib diisi jika :other bernilai :value.',
    'required_if_accepted' => ':Attribute wajib diisi jika :other diterima.',
    'required_if_declined' => ':Attribute wajib diisi jika :other ditolak.',
    'required_unless' => ':Attribute wajib diisi kecuali :other ada di :values.',
    'required_with' => ':Attribute wajib diisi jika :values ada.',
    'required_with_all' => ':Attribute wajib diisi jika :values ada.',
    'required_without' => ':Attribute wajib diisi jika :values tidak ada.',
    'required_without_all' => ':Attribute wajib diisi jika :values tidak ada sama sekali.',
    'same' => ':Attribute harus sama dengan :other.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => ':Attribute harus berukuran :size kilobyte.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus berisi :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali salah satu dari: :values.',
    'string' => ':Attribute harus berupa teks.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah terdaftar.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => ':Attribute harus berupa huruf kapital.',
    'url' => ':Attribute harus berupa URL yang valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Khusus per Atribut
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'phone' => [
            'regex' => 'Format nomor HP tidak valid. Gunakan nomor Indonesia, contoh 081234567890.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    |
    | Mengganti nama field (mis. "phone") dengan nama yang mudah dibaca.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'phone' => 'nomor HP',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'remember' => 'ingat saya',
        'address' => 'alamat',
        'notes' => 'catatan',
    ],

];
