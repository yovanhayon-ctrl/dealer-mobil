<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo'])]
class Brand extends Model
{
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
