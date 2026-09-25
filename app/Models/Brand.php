<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
