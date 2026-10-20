<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Pengaturan dealer yang diubah dari admin (menu Pengaturan). Kunci = jalur config di bawah "dealer."
 * (mis. "name", "bank.account", "hero.title"). Nilai yang terisi menggantikan nilai .env saat aplikasi berjalan
 * (apply() di AppServiceProvider), sehingga semua halaman, PDF, notifikasi, dan email ikut memakai data terbaru.
 * Kunci yang dihapus (dikosongkan) kembali ke nilai .env.
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model
{
    public const CACHE_KEY = 'dealer-settings';

    public const KEYS = [
        'name', 'tagline', 'address', 'phone', 'whatsapp', 'email', 'hours', 'maps_embed_url', 'about_text',
        'bank.name', 'bank.account', 'bank.holder',
        'hero.kicker', 'hero.title', 'hero.lead', 'hero.background', 'hero.image',
    ];

    public const BANK_KEYS = ['bank.name', 'bank.account', 'bank.holder'];

    /** Folder gambar hero di disk public. */
    public const HERO_DIRECTORY = 'settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, string>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->pluck('value', 'key')->all());
    }

    /**
     * Terapkan pengaturan ke config dealer.*. Nilai asli (.env / config/dealer.php) dicadangkan sekali di config
     * dealer_defaults.* (ikut tersimpan bila config:cache), sehingga kunci yang dikosongkan kembali ke nilai asli.
     * Aman dipanggil sebelum tabel ada (migrate pertama): tidak mengubah apa pun.
     */
    public static function apply(): void
    {
        try {
            $values = self::values();
        } catch (Throwable) {
            return;
        }

        $config = app('config');

        foreach (self::KEYS as $key) {
            if (! $config->has("dealer_defaults.{$key}")) {
                $config->set("dealer_defaults.{$key}", $config->get("dealer.{$key}"));
            }

            $value = $values[$key] ?? null;
            $config->set("dealer.{$key}", filled($value) ? $value : $config->get("dealer_defaults.{$key}"));
        }
    }

    /**
     * Nilai asli dari .env / config (sebelum diganti pengaturan), untuk petunjuk & pembanding di halaman admin.
     */
    public static function original(string $key): mixed
    {
        return config()->has("dealer_defaults.{$key}") ? config("dealer_defaults.{$key}") : config("dealer.{$key}");
    }

    /**
     * Simpan pengaturan: nilai kosong menghapus baris (kembali ke .env). Hanya kunci yang berubah yang mencatat pengubahnya.
     *
     * @param  array<string, ?string>  $values
     * @return list<string> kunci yang berubah
     */
    public static function store(array $values, ?User $user): array
    {
        $current = self::query()->pluck('value', 'key')->all();
        $changed = [];

        foreach ($values as $key => $value) {
            $value = filled($value) ? (string) $value : null;

            if (($current[$key] ?? null) === $value) {
                continue;
            }

            $value === null
                ? self::query()->whereKey($key)->delete()
                : self::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $user?->id]);
            $changed[] = $key;
        }

        Cache::forget(self::CACHE_KEY);
        self::apply();

        return $changed;
    }
}
