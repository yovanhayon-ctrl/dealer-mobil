<?php

namespace App\Reports;

use App\Models\PurchaseRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tren bulanan untuk grafik dashboard admin (bulan berjalan + beberapa bulan sebelumnya).
 * Diagregasi di database (GROUP BY bulan): 3 query berapa pun datanya; bulan tanpa data = 0.
 *
 * Semua dihitung dari tanggal dibuat (created_at). Terjual = pengajuan berstatus approved/completed,
 * sama dengan halaman Laporan, sehingga angkanya cocok untuk periode yang sama.
 */
class DashboardTrend
{
    public const MONTHS = 6;

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public function __construct(?CarbonImmutable $now = null)
    {
        $now ??= CarbonImmutable::now();
        $this->to = $now->endOfMonth();
        $this->from = $now->startOfMonth()->subMonths(self::MONTHS - 1);
    }

    /**
     * @return list<array{key: string, label: string, purchases: int, test_drives: int, service_bookings: int, units_sold: int, sales_value: int}>
     */
    public function months(): array
    {
        $sold = PurchaseRequest::SOLD_STATUSES;
        $placeholders = implode(', ', array_fill(0, count($sold), '?'));

        $purchases = $this->perMonth('purchase_requests')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(CASE WHEN status IN ({$placeholders}) THEN 1 END) AS units_sold", $sold)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ({$placeholders}) THEN car_price ELSE 0 END), 0) AS sales_value", $sold)
            ->get()
            ->keyBy('month');
        $testDrives = $this->perMonth('test_drives')->selectRaw('COUNT(*) AS total')->pluck('total', 'month');
        $serviceBookings = $this->perMonth('service_bookings')->selectRaw('COUNT(*) AS total')->pluck('total', 'month');

        $months = [];

        for ($month = $this->from; $month->lessThanOrEqualTo($this->to); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $purchase = $purchases->get($key);

            $months[] = [
                'key' => $key,
                'label' => $month->translatedFormat('M Y'),
                'purchases' => (int) ($purchase->total ?? 0),
                'test_drives' => (int) ($testDrives[$key] ?? 0),
                'service_bookings' => (int) ($serviceBookings[$key] ?? 0),
                'units_sold' => (int) ($purchase->units_sold ?? 0),
                'sales_value' => (int) ($purchase->sales_value ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Data siap pakai Chart.js (dibaca admin.js dari atribut data-chart).
     *
     * @param  list<array<string, mixed>>  $months
     * @return array{activity: array<string, mixed>, sales: array<string, mixed>}
     */
    public static function chartData(array $months): array
    {
        $labels = array_column($months, 'label');

        return [
            'activity' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Pengajuan pembelian', 'data' => array_column($months, 'purchases'), 'color' => '#c3002f'],
                    ['label' => 'Test drive', 'data' => array_column($months, 'test_drives'), 'color' => '#1c1c1e'],
                    ['label' => 'Booking servis', 'data' => array_column($months, 'service_bookings'), 'color' => '#5b6b82'],
                ],
            ],
            'sales' => [
                'labels' => $labels,
                'units' => array_column($months, 'units_sold'),
                'values' => array_column($months, 'sales_value'),
            ],
        ];
    }

    /**
     * Query per bulan created_at dalam rentang. Ekspresi bulan berbeda antara SQLite (test) dan MySQL.
     */
    private function perMonth(string $table): Builder
    {
        $month = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        return DB::table($table)
            ->selectRaw("{$month} AS month")
            ->whereBetween('created_at', [$this->from->toDateTimeString(), $this->to->toDateTimeString()])
            ->groupByRaw($month);
    }
}
