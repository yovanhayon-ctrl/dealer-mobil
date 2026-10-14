<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master layanan JAF Service (servis berkala, inspeksi, dll.).
 * Layanan yang sudah punya booking tidak bisa dihapus (FK restrict); nonaktifkan saja.
 */
#[Fillable(['name', 'slug', 'description', 'checklist', 'price_from', 'duration_minutes', 'is_active'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUniqueSlug;

    protected function casts(): array
    {
        return [
            'price_from' => 'integer',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Layanan yang boleh dipilih customer.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /** Batas daftar pekerjaan bawaan per layanan. */
    public const MAX_CHECKLIST_ITEMS = 30;

    /**
     * Daftar pekerjaan bawaan (satu per baris di kolom checklist), tanpa baris kosong / ganda.
     *
     * @return list<string>
     */
    public function checklistItems(): array
    {
        return self::parseChecklist((string) $this->checklist);
    }

    /**
     * @return list<string>
     */
    public static function parseChecklist(string $text): array
    {
        $items = array_map(
            // Tanda daftar di awal baris ("- ", "* ", "• ") dibuang; spasi berlebih dirapikan.
            fn (string $line) => trim((string) preg_replace(['/^[\s\-*\x{2022}\x{2013}]+/u', '/\s+/u'], ['', ' '], $line)),
            preg_split('/\R/', $text) ?: [],
        );

        return array_values(array_unique(array_filter($items, fn (string $item) => $item !== '')));
    }
}
