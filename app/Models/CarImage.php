<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['car_id', 'path', 'thumb_path', 'is_primary', 'sort_order'])]
class CarImage extends Model
{
    public const DISK = 'public';

    public const DIRECTORY = 'cars';

    public const MAX_PER_CAR = 10;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Folder file gambar satu mobil di disk public, mis. "cars/12".
     */
    public static function directory(int $carId): string
    {
        return self::DIRECTORY.'/'.$carId;
    }

    /**
     * URL publik gambar (disk public).
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk(self::DISK)->url($this->path));
    }

    /**
     * URL thumbnail (lebar 480 px) untuk kartu & daftar; foto lama tanpa thumbnail memakai foto utama.
     */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn () => Storage::disk(self::DISK)->url($this->thumb_path ?: $this->path));
    }

    /**
     * Semua file milik gambar ini di disk (foto + thumbnail).
     *
     * @return list<string>
     */
    public function files(): array
    {
        return array_values(array_filter([$this->path, $this->thumb_path]));
    }
}
