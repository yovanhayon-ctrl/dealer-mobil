<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\ActivityLog;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola akun staf (owner & super admin): tambah, ubah data & role, nonaktifkan.
 * Owner mengelola semua role staf; super admin tidak bisa mengelola Owner & Super Admin (User::MANAGEABLE_STAFF_ROLES).
 * Akun sendiri tidak bisa diubah di sini, jadi tidak ada yang bisa menurunkan atau menonaktifkan dirinya sendiri.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::query()
            ->whereIn('role', User::STAFF_ROLES)
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 WHEN 'sales' THEN 3 WHEN 'customer_service' THEN 4 ELSE 5 END")
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->withAvg('mechanicRatings', 'rating')
            ->withCount([
                'mechanicRatings',
                'assignedServiceBookings as completed_jobs_count' => fn ($query) => $query->where('status', ServiceBooking::STATUS_COMPLETED),
            ])
            ->get(['id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at']);

        return view('admin.staff.index', [
            'staff' => $staff,
            'counts' => $staff->countBy('role'),
        ]);
    }

    public function create(): View
    {
        // Bawaan mekanik (role tidak bisa diisi lewat fill, jadi diset langsung).
        $staff = new User;
        $staff->role = User::ROLE_MECHANIC;

        return view('admin.staff.create', ['staff' => $staff]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $staff = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        $staff->role = $request->validated('role');
        $staff->is_active = true;
        $staff->email_verified_at = now();
        $staff->save();

        ActivityLog::record(ActivityLog::CATEGORY_ACCOUNT, 'staff.created',
            "Menambah akun staf {$staff->name} sebagai {$staff->roleLabel()}", $staff, ['role' => $staff->role]);

        return redirect()->route('admin.staff.index')
            ->with('success', "Akun {$staff->roleLabel()} {$staff->name} dibuat. Berikan email dan kata sandinya untuk login.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureEditable($request, $user);

        return view('admin.staff.edit', ['staff' => $user]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        $this->ensureEditable($request, $user);

        $before = $user->only(['name', 'email', 'phone', 'role', 'is_active']);
        $oldRoleLabel = $user->roleLabel();

        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        $user->role = $request->validated('role');
        $user->is_active = $request->boolean('is_active');

        if (filled($request->validated('password'))) {
            $user->password = $request->validated('password');
        }

        $passwordReset = $user->isDirty('password');
        $user->save();

        $this->logChanges($user, $before, $oldRoleLabel, $passwordReset);

        return redirect()->route('admin.staff.index')
            ->with('success', "Akun {$user->name} diperbarui".($user->is_active ? '.' : ' dan dinonaktifkan (tidak bisa login).'));
    }

    /**
     * Catat perubahan akun staf (role, aktif/nonaktif, data, kata sandi) di Riwayat Aktivitas; tanpa perubahan tidak dicatat.
     *
     * @param  array<string, mixed>  $before
     */
    private function logChanges(User $user, array $before, string $oldRoleLabel, bool $passwordReset): void
    {
        $changes = [];
        $properties = [];

        if ($before['role'] !== $user->role) {
            $changes[] = "role {$oldRoleLabel} → {$user->roleLabel()}";
        }

        if ((bool) $before['is_active'] !== $user->is_active) {
            $changes[] = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        }

        foreach (['name' => 'nama', 'email' => 'email', 'phone' => 'nomor WhatsApp'] as $field => $label) {
            if ($before[$field] !== $user->{$field}) {
                $changes[] = "{$label} diubah";
            }
        }

        foreach (['name', 'email', 'phone', 'role', 'is_active'] as $field) {
            if ($before[$field] !== $user->{$field}) {
                $properties[$field] = ['old' => $before[$field], 'new' => $user->{$field}];
            }
        }

        if ($passwordReset) {
            $changes[] = 'kata sandi direset';
            $properties['password'] = 'reset';
        }

        if ($changes !== []) {
            ActivityLog::record(ActivityLog::CATEGORY_ACCOUNT, 'staff.updated',
                "Mengubah akun {$user->name}: ".implode(', ', $changes), $user, $properties);
        }
    }

    /**
     * Hanya akun staf yang boleh dikelola user ini; akun sendiri, owner, customer, dan (bagi super admin) super admin: 404.
     */
    private function ensureEditable(Request $request, User $user): void
    {
        abort_unless($request->user()->canManageStaffAccount($user), 404);
    }
}
