<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Jadwal (test drive / booking servis) yang tanggalnya sudah lewat tetapi masih Menunggu atau Dikonfirmasi.
 * Jadwal hari ini belum dianggap lewat. Model memakai kolom preferred_date (cast date).
 */
trait HasSchedule
{
    /**
     * @return list<string>
     */
    public static function openScheduleStatuses(): array
    {
        return [static::STATUS_PENDING, static::STATUS_CONFIRMED];
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, static::openScheduleStatuses(), true)
            && $this->preferred_date !== null
            && $this->preferred_date->lt(today());
    }

    /**
     * Aturannya sama dengan isOverdue().
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), static::openScheduleStatuses())
            ->whereDate($query->qualifyColumn('preferred_date'), '<', today()->toDateString());
    }
}
