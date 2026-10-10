<?php

namespace App\Http\Controllers\Account\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * Penyaring riwayat akun menurut kelompok status (Berjalan / Selesai / Dibatalkan).
 * Kelompok didefinisikan tiap controller lewat konstanta STATUS_GROUPS: kunci => daftar status.
 */
trait FiltersByStatusGroup
{
    /**
     * Kelompok dari query string ?status=…; nilai tak dikenal = semua (null).
     */
    protected function statusGroup(Request $request): ?string
    {
        $group = $request->query('status');

        return is_string($group) && array_key_exists($group, static::STATUS_GROUPS) ? $group : null;
    }

    /**
     * Jumlah data per kelompok (satu query GROUP BY status) + "semua".
     *
     * @return array<string, int>
     */
    protected function statusGroupCounts(HasMany $relation): array
    {
        $perStatus = $relation->toBase()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['semua' => (int) $perStatus->sum()];

        foreach (static::STATUS_GROUPS as $group => $statuses) {
            $counts[$group] = (int) $perStatus->only($statuses)->sum();
        }

        return $counts;
    }
}
