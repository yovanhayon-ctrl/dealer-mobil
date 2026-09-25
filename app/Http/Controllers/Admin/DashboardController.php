<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->stats(),
            'latestPurchases' => PurchaseRequest::query()
                ->with(['user:id,name', 'car:id,brand_id,name', 'car.brand:id,name'])
                ->latest()
                ->limit(5)
                ->get(),
            'upcomingTestDrives' => TestDrive::query()
                ->with(['user:id,name', 'car:id,brand_id,name', 'car.brand:id,name'])
                ->whereDate('preferred_date', '>=', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('preferred_date')
                ->orderBy('preferred_time')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Angka kartu statistik. Mobil aktif & stok habis dihitung dalam satu query agregat.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        $cars = Car::toBase()
            ->selectRaw('COUNT(CASE WHEN is_active = 1 THEN 1 END) AS active')
            ->selectRaw('COUNT(CASE WHEN is_active = 1 AND stock = 0 THEN 1 END) AS out_of_stock')
            ->first();

        return [
            'active_cars' => (int) $cars->active,
            'out_of_stock_cars' => (int) $cars->out_of_stock,
            'pending_test_drives' => TestDrive::where('status', 'pending')->count(),
            'pending_purchases' => PurchaseRequest::where('status', 'pending')->count(),
            'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
        ];
    }
}
