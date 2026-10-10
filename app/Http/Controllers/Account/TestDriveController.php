<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Concerns\FiltersByStatusGroup;
use App\Http\Controllers\Controller;
use App\Models\TestDrive;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\TestDriveActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Riwayat test drive milik customer yang login + pembatalan saat status pending.
 */
class TestDriveController extends Controller
{
    use FiltersByStatusGroup;

    public const PER_PAGE = 10;

    /** Kelompok penyaring status di halaman riwayat. */
    public const STATUS_GROUPS = [
        'berjalan' => [TestDrive::STATUS_PENDING, TestDrive::STATUS_CONFIRMED],
        'selesai' => [TestDrive::STATUS_COMPLETED],
        'dibatalkan' => [TestDrive::STATUS_CANCELLED],
    ];

    public function index(Request $request): View
    {
        $group = $this->statusGroup($request);

        $testDrives = $request->user()->testDrives()
            ->with([
                'car:id,brand_id,name,slug,year,is_active',
                'car.brand:id,name',
                'car.primaryImage:id,car_id,path',
            ])
            ->latest()
            ->orderByDesc('id')
            ->when($group, fn ($query, $group) => $query->whereIn('status', self::STATUS_GROUPS[$group]))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.account.test-drives.index', [
            'testDrives' => $testDrives,
            'statusGroup' => $group,
            'statusCounts' => $this->statusGroupCounts($request->user()->testDrives()),
        ]);
    }

    public function cancel(Request $request, TestDrive $testDrive): RedirectResponse
    {
        // Milik customer lain: 404 agar keberadaan datanya tidak terlihat.
        abort_unless($testDrive->user_id === $request->user()->id, 404);

        $redirect = redirect()->back(fallback: route('account.test-drives.index'));

        // Baca ulang baris yang dikunci: admin mungkin sudah mengubah status, atau tombol diklik dua kali.
        $result = DB::transaction(function () use ($testDrive) {
            $locked = TestDrive::whereKey($testDrive->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === TestDrive::STATUS_CANCELLED) {
                return 'already';
            }

            if (! $locked->canBeCancelledByCustomer()) {
                return 'denied';
            }

            $locked->update(['status' => TestDrive::STATUS_CANCELLED]);

            return 'cancelled';
        });

        // Hanya pembatalan yang benar-benar terjadi sekarang (bukan klik ganda / sudah batal) yang dikabarkan.
        if ($result === 'cancelled') {
            AdminActivityNotification::notifyAdmins(new TestDriveActivity($testDrive, AdminActivityNotification::CANCELLED));
        }

        return match ($result) {
            'already' => $redirect->with('status', 'Test drive ini sudah dibatalkan.'),
            'denied' => $redirect->with('error', 'Test drive yang sudah dikonfirmasi atau selesai tidak bisa dibatalkan dari sini. Silakan hubungi dealer.'),
            default => $redirect->with('success', 'Test drive berhasil dibatalkan.'),
        };
    }
}
