<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Seperti rule "unique", tetapi tidak membedakan huruf besar/kecil di semua database
 * (MySQL collation *_ci sudah begitu, SQLite untuk test tidak).
 */
class UniqueIgnoringCase implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column = 'name',
        private Model|int|string|null $ignore = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ignoreId = $this->ignore instanceof Model ? $this->ignore->getKey() : $this->ignore;

        $exists = DB::table($this->table)
            ->whereRaw('LOWER('.$this->column.') = ?', [mb_strtolower((string) $value)])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
