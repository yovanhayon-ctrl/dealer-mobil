<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Staf > Riwayat Aktivitas (Owner & Super Admin): log tindakan sensitif staf, terbaru di atas. Hanya baca.
 */
class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->filters($request);

        $logs = ActivityLog::query()
            ->with('subject')
            ->when($filters['kelompok'], fn ($query, $category) => $query->where('category', $category))
            ->when($filters['staf'], fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['dari'], fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['sampai'], fn ($query, $date) => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->latest('id')
            ->paginate(ActivityLog::PER_PAGE)
            ->withQueryString();

        return view('admin.staff.activity', [
            'logs' => $logs,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'staffOptions' => User::query()->whereIn('role', User::STAFF_ROLES)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{kelompok: ?string, staf: ?int, dari: ?string, sampai: ?string}
     */
    private function filters(Request $request): array
    {
        $date = function (string $key) use ($request): ?string {
            $value = (string) $request->query($key);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : null;
        };
        $category = $request->query('kelompok');
        $staff = $request->query('staf');

        return [
            'kelompok' => is_string($category) && isset(ActivityLog::CATEGORIES[$category]) ? $category : null,
            'staf' => is_string($staff) && ctype_digit($staff) ? (int) $staff : null,
            'dari' => $date('dari'),
            'sampai' => $date('sampai'),
        ];
    }
}
