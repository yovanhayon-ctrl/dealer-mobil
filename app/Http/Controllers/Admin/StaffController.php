<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Kelola akun staf (khusus owner): tambah admin / mekanik, ubah data & role, nonaktifkan.
 * Akun owner tidak bisa diubah di sini, jadi owner tidak bisa menurunkan atau menonaktifkan dirinya sendiri.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::query()
            ->whereIn('role', User::STAFF_ROLES)
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderByDesc('is_active')
            ->orderBy('name')
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

        return redirect()->route('admin.staff.index')
            ->with('success', "Akun {$staff->roleLabel()} {$staff->name} dibuat. Berikan email dan kata sandinya untuk login.");
    }

    public function edit(User $user): View
    {
        $this->ensureEditable($user);

        return view('admin.staff.edit', ['staff' => $user]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        $this->ensureEditable($user);

        $user->fill($request->safe()->only(['name', 'email', 'phone']));
        $user->role = $request->validated('role');
        $user->is_active = $request->boolean('is_active');

        if (filled($request->validated('password'))) {
            $user->password = $request->validated('password');
        }

        $user->save();

        return redirect()->route('admin.staff.index')
            ->with('success', "Akun {$user->name} diperbarui".($user->is_active ? '.' : ' dan dinonaktifkan (tidak bisa login).'));
    }

    /**
     * Hanya akun admin / mekanik; owner dan customer: 404.
     */
    private function ensureEditable(User $user): void
    {
        abort_unless(in_array($user->role, User::ASSIGNABLE_STAFF_ROLES, true), 404);
    }
}
