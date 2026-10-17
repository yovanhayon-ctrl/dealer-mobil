<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aturan jadwal bersama test drive & booking servis (kolom preferred_date/preferred_time):
 * - lewat jadwal: masih Menunggu/Dikonfirmasi padahal tanggalnya sudah lewat (hari ini belum);
 * - Tidak datang: hanya dari Dikonfirmasi dan pada/sesudah tanggal jadwal;
 * - jadwal ulang: selama masih Menunggu/Dikonfirmasi, atau Tidak datang (customer minta jadwal baru).
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
     * Status "Tidak datang" baru bisa dipilih pada atau sesudah tanggal jadwal.
     *
     * @param  list<string>  $transitions
     * @return list<string>
     */
    protected function withoutEarlyNoShow(array $transitions): array
    {
        if ($this->preferred_date === null || ! $this->preferred_date->isAfter(today())) {
            return $transitions;
        }

        return array_values(array_diff($transitions, [static::STATUS_NO_SHOW]));
    }

    /**
     * Catatan bawaan untuk customer saat ditandai Tidak datang.
     */
    public function noShowNote(): string
    {
        return 'Anda tidak hadir pada jadwal '.$this->preferred_date->translatedFormat('d M Y')." pukul {$this->timeLabel()} WIB."
            .' Silakan booking ulang bila masih berminat.';
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, [...static::openScheduleStatuses(), static::STATUS_NO_SHOW], true);
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
