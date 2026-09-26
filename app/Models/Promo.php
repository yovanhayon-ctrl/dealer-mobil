<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\PromoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'car_id', 'title', 'slug', 'description', 'image', 'discount_amount',
    'start_date', 'end_date', 'is_active',
])]
class Promo extends Model
{
    /** @use HasFactory<PromoFactory> */
    use HasFactory, HasUniqueSlug;

    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'promos';

    /** Status dihitung dari is_active + tanggal hari ini (nilai = kunci x-status-badge). */
    public const STATUS_RUNNING = 'running';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ENDED = 'ended';

    public const STATUS_INACTIVE = 'inactive';

    /** Nilai filter di query string => [status, label]. */
    public const STATUS_FILTERS = [
        'berjalan' => [self::STATUS_RUNNING, 'Berjalan'],
        'terjadwal' => [self::STATUS_SCHEDULED, 'Terjadwal'],
        'berakhir' => [self::STATUS_ENDED, 'Berakhir'],
        'nonaktif' => [self::STATUS_INACTIVE, 'Nonaktif'],
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Promo aktif (Berjalan): is_active = true dan hari ini di antara start_date dan end_date.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Filter berdasarkan status hitungan (lihat accessor status).
     */
    #[Scope]
    protected function withStatus(Builder $query, string $status): void
    {
        $today = today()->toDateString();

        match ($status) {
            self::STATUS_RUNNING => $query->active(),
            self::STATUS_SCHEDULED => $query->where('is_active', true)->whereDate('start_date', '>', $today),
            self::STATUS_ENDED => $query->where('is_active', true)->whereDate('end_date', '<', $today),
            self::STATUS_INACTIVE => $query->where('is_active', false),
        };
    }

    /**
     * Nonaktif selalu menang atas tanggal; selanjutnya Terjadwal / Berakhir / Berjalan menurut hari ini (WIB).
     */
    protected function status(): Attribute
    {
        return Attribute::get(function () {
            $today = today();

            return match (true) {
                ! $this->is_active => self::STATUS_INACTIVE,
                $today->lt($this->start_date) => self::STATUS_SCHEDULED,
                $today->gt($this->end_date) => self::STATUS_ENDED,
                default => self::STATUS_RUNNING,
            };
        });
    }

    /**
     * URL publik banner (disk public), null jika tidak ada banner.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image
            ? Storage::disk(self::IMAGE_DISK)->url($this->image)
            : null);
    }

    /**
     * Promo umum (untuk semua mobil, informasi saja tanpa diskon).
     */
    public function isGeneral(): bool
    {
        return $this->car_id === null;
    }
}
