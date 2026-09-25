<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Membuat slug unik dari sebuah nama. Dipanggil secara eksplisit (bukan lewat event model)
 * agar tetap berjalan di seeder yang memakai WithoutModelEvents.
 */
trait HasUniqueSlug
{
    /**
     * Slug dasar. Titik dijadikan pemisah agar "1.5" menjadi "1-5", bukan "15".
     */
    public static function slugify(string $name): string
    {
        return Str::slug(str_replace('.', ' ', $name));
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = static::slugify($name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
