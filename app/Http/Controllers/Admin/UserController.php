<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar pengguna hanya baca: menghapus user akan ikut menghapus riwayat test drive
 * dan pengajuannya (FK cascadeOnDelete), jadi tidak ada tambah/edit/hapus.
 */
class UserController extends Controller
{
    /** Pilihan urutan: nilai query string => [kolom, arah]. */
    public const SORTS = [
        'terbaru' => ['created_at', 'desc'],
        'nama' => ['name', 'asc'],
    ];

    /** Nilai filter role => label. Default customer. */
    public const ROLE_FILTERS = [
        User::ROLE_CUSTOMER => 'Customer',
        User::ROLE_ADMIN => 'Admin',
        'semua' => 'Semua',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        [$sortColumn, $sortDirection] = self::SORTS[$filters['urut']];

        $users = User::query()
            // Password & remember_token tidak pernah diambil dari database.
            ->select(['id', 'name', 'email', 'phone', 'role', 'created_at'])
            ->withCount(['testDrives', 'purchaseRequests'])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'hasFilters' => $filters['q'] !== null || $filters['role'] !== User::ROLE_CUSTOMER,
        ]);
    }

    public function show(User $user): View
    {
        $car = fn ($query) => $query->select(['id', 'brand_id', 'name', 'year'])->with('brand:id,name');

        $user->load([
            'testDrives' => fn ($query) => $query->orderByDesc('preferred_date')->orderByDesc('preferred_time')->orderByDesc('id'),
            'testDrives.car' => $car,
            'purchaseRequests' => fn ($query) => $query->latest()->orderByDesc('id'),
            'purchaseRequests.car' => $car,
        ]);

        return view('admin.users.show', ['user' => $user]);
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, role: string, urut: string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'role' => $pick('role', array_keys(self::ROLE_FILTERS)) ?? User::ROLE_CUSTOMER,
            'urut' => $pick('urut', array_keys(self::SORTS)) ?? 'terbaru',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'], fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($filters['role'] !== 'semua', fn ($q) => $q->where('role', $filters['role']));
    }
}
