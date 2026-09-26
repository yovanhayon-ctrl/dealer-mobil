<?php

namespace App\Reports;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan admin untuk satu periode. Semua angka diagregasi di database (COUNT/SUM/GROUP BY),
 * jumlah query tetap berapa pun datanya, dan setiap bagian dihitung sekali (memo).
 *
 * - Terjual = pengajuan berstatus approved/completed (unit sudah dipotong dari stok).
 * - Pengajuan difilter berdasarkan tanggal pengajuan (created_at); test drive berdasarkan jadwal (preferred_date).
 */
class AdminReport
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(public readonly ReportPeriod $period) {}

    /**
     * @return array{total: int, statuses: array<string, int>, cash: int, credit: int, units_sold: int, sales_value: int}
     */
    public function purchaseSummary(): array
    {
        return $this->memo[__FUNCTION__] ??= $this->loadPurchaseSummary();
    }

    /**
     * @return Collection<int, object{name: string, units: int, value: int}>
     */
    public function salesByBrand(): Collection
    {
        return $this->memo[__FUNCTION__] ??= $this->salesGroupedBy('brands', 'cars.brand_id');
    }

    /**
     * @return Collection<int, object{name: string, units: int, value: int}>
     */
    public function salesByCategory(): Collection
    {
        return $this->memo[__FUNCTION__] ??= $this->salesGroupedBy('categories', 'cars.category_id');
    }

    /**
     * @return Collection<int, object{id: int, name: string, units: int, value: int}>
     */
    public function topCars(int $limit = 5): Collection
    {
        return $this->memo[__FUNCTION__.$limit] ??= $this->soldQuery()
            ->join('brands', 'brands.id', '=', 'cars.brand_id')
            ->groupBy('cars.id', 'cars.name', 'cars.year', 'brands.name')
            ->selectRaw('cars.id AS id, brands.name AS brand, cars.name AS car, cars.year AS year')
            ->selectRaw('COUNT(*) AS units, SUM(purchase_requests.car_price) AS value')
            ->orderByDesc('units')
            ->orderByDesc('value')
            ->orderBy('cars.name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (object) [
                'id' => (int) $row->id,
                'name' => "{$row->brand} {$row->car} {$row->year}",
                'units' => (int) $row->units,
                'value' => (int) $row->value,
            ]);
    }

    /**
     * @return array{total: int, statuses: array<string, int>, completion_rate: float}
     */
    public function testDriveSummary(): array
    {
        return $this->memo[__FUNCTION__] ??= $this->loadTestDriveSummary();
    }

    /**
     * Mobil aktif dengan stok ≤ Car::LOW_STOCK_THRESHOLD (tidak bergantung periode).
     *
     * @return EloquentCollection<int, Car>
     */
    public function lowStockCars(): EloquentCollection
    {
        return $this->memo[__FUNCTION__] ??= Car::active()
            ->where('stock', '<=', Car::LOW_STOCK_THRESHOLD)
            ->with('brand:id,name')
            ->orderBy('stock')
            ->orderBy('name')
            ->get(['id', 'brand_id', 'name', 'year', 'vehicle_condition', 'stock']);
    }

    /**
     * Persentase bagian dari total (1 desimal), 0 bila total 0.
     */
    public static function percent(int|float $part, int|float $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }

    private function loadPurchaseSummary(): array
    {
        $sold = PurchaseRequest::SOLD_STATUSES;
        $soldPlaceholders = implode(', ', array_fill(0, count($sold), '?'));

        $query = $this->purchasesInPeriod()->selectRaw('COUNT(*) AS total');

        foreach (PurchaseRequest::STATUSES as $status) {
            $query->selectRaw("COUNT(CASE WHEN status = ? THEN 1 END) AS status_{$status}", [$status]);
        }

        $row = $query
            ->selectRaw('COUNT(CASE WHEN payment_method = ? THEN 1 END) AS cash', [PurchaseRequest::PAYMENT_CASH])
            ->selectRaw('COUNT(CASE WHEN payment_method = ? THEN 1 END) AS credit', [PurchaseRequest::PAYMENT_CREDIT])
            ->selectRaw("COUNT(CASE WHEN status IN ({$soldPlaceholders}) THEN 1 END) AS units_sold", $sold)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ({$soldPlaceholders}) THEN car_price ELSE 0 END), 0) AS sales_value", $sold)
            ->first();

        return [
            'total' => (int) $row->total,
            'statuses' => collect(PurchaseRequest::STATUSES)
                ->mapWithKeys(fn ($status) => [$status => (int) $row->{"status_{$status}"}])
                ->all(),
            'cash' => (int) $row->cash,
            'credit' => (int) $row->credit,
            'units_sold' => (int) $row->units_sold,
            'sales_value' => (int) $row->sales_value,
        ];
    }

    private function loadTestDriveSummary(): array
    {
        // Rentang datetime (bukan whereDate) agar index preferred_date tetap terpakai.
        $counts = DB::table('test_drives')
            ->whereBetween('preferred_date', [$this->period->from->toDateTimeString(), $this->period->to->toDateTimeString()])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) AS total')
            ->pluck('total', 'status');

        $statuses = collect(TestDrive::STATUSES)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
        $total = array_sum($statuses);

        return [
            'total' => $total,
            'statuses' => $statuses,
            'completion_rate' => self::percent($statuses[TestDrive::STATUS_COMPLETED], $total),
        ];
    }

    /**
     * Penjualan dikelompokkan per merek/kategori, urut nilai terbesar.
     */
    private function salesGroupedBy(string $table, string $foreignKey): Collection
    {
        return $this->soldQuery()
            ->join($table, "{$table}.id", '=', $foreignKey)
            ->groupBy("{$table}.id", "{$table}.name")
            ->selectRaw("{$table}.name AS name, COUNT(*) AS units, SUM(purchase_requests.car_price) AS value")
            ->orderByDesc('value')
            ->orderByDesc('units')
            ->orderBy("{$table}.name")
            ->get()
            ->map(fn ($row) => (object) ['name' => $row->name, 'units' => (int) $row->units, 'value' => (int) $row->value]);
    }

    private function purchasesInPeriod(): Builder
    {
        return DB::table('purchase_requests')
            ->whereBetween('purchase_requests.created_at', [$this->period->from->toDateTimeString(), $this->period->to->toDateTimeString()]);
    }

    private function soldQuery(): Builder
    {
        return $this->purchasesInPeriod()
            ->join('cars', 'cars.id', '=', 'purchase_requests.car_id')
            ->whereIn('purchase_requests.status', PurchaseRequest::SOLD_STATUSES);
    }
}
