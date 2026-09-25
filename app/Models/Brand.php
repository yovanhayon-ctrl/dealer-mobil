<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'logo'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasUniqueSlug;

    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'brands';

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * URL publik logo (disk public), null jika tidak ada logo.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo
            ? Storage::disk(self::LOGO_DISK)->url($this->logo)
            : null);
    }
}
