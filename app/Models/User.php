<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Pemilik dealer: semua menu + kelola semua akun staf. */
    public const ROLE_OWNER = 'owner';

    /** Semua menu; kelola akun staf kecuali Owner & Super Admin. */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** Admin Dealer: operasional (mobil, katalog, promo, transaksi, servis, ulasan, laporan). */
    public const ROLE_ADMIN = 'admin';

    /** Melayani pembeli: test drive & pengajuan; mobil & promo hanya lihat. */
    public const ROLE_SALES = 'sales';

    /** Konfirmasi jadwal test drive & servis; pengajuan, ulasan, dan customer hanya lihat. */
    public const ROLE_CUSTOMER_SERVICE = 'customer_service';

    /** Mekanik bengkel: panel Pekerjaan Saya. */
    public const ROLE_MECHANIC = 'mechanic';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_LABELS = [
        self::ROLE_OWNER => 'Owner',
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_ADMIN => 'Admin Dealer',
        self::ROLE_SALES => 'Sales',
        self::ROLE_CUSTOMER_SERVICE => 'Customer Service',
        self::ROLE_MECHANIC => 'Mekanik',
        self::ROLE_CUSTOMER => 'Customer',
    ];

    /** Role yang bisa diberikan lewat menu Staf (owner baru lewat perintah user:make-owner). */
    public const ASSIGNABLE_STAFF_ROLES = [
        self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE, self::ROLE_MECHANIC,
    ];

    public const STAFF_ROLES = [
        self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE, self::ROLE_MECHANIC,
    ];

    /** Bisa membuka panel admin (semua staf kecuali mekanik, yang punya panel sendiri). */
    public const PANEL_ROLES = [
        self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE,
    ];

    /**
     * Hak akses per kemampuan => role yang memilikinya. Satu-satunya sumber aturan akses staf:
     * didaftarkan sebagai Gate (AppServiceProvider), dipakai route (can:...), sidebar, tombol, notifikasi, dan dashboard.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'cars.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES],
        'cars.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'catalog.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'promos.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES],
        'promos.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        // Hapus data master (mobil, merek, kategori, promo, layanan).
        'master.delete' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN],
        'test-drives.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE],
        'purchases.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE],
        'purchases.status' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_SALES],
        // Verifikasi bukti bayar, pembayaran di showroom, file bukti, dan pengembalian dana.
        'payments.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'service-bookings.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_CUSTOMER_SERVICE],
        'services.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'testimonials.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_CUSTOMER_SERVICE],
        'testimonials.moderate' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        // Daftar & detail customer; staff-accounts.view juga melihat akun staf.
        'users.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_CUSTOMER_SERVICE],
        'staff-accounts.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'reports.view' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        'staff.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN],
        // Tombol "Kirim Pengingat Besok" di dashboard (cadangan bila hosting tanpa cron).
        'reminders.send' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
        // Menu Pengaturan: profil dealer, rekening pembayaran, hero beranda.
        'settings.manage' => [self::ROLE_OWNER, self::ROLE_SUPER_ADMIN],
    ];

    /**
     * Role yang boleh dibuat/diubah lewat menu Staf oleh role tertentu. Super Admin tidak bisa mengelola Owner & Super Admin.
     *
     * @var array<string, list<string>>
     */
    public const MANAGEABLE_STAFF_ROLES = [
        self::ROLE_OWNER => self::ASSIGNABLE_STAFF_ROLES,
        self::ROLE_SUPER_ADMIN => [self::ROLE_ADMIN, self::ROLE_SALES, self::ROLE_CUSTOMER_SERVICE, self::ROLE_MECHANIC],
    ];

    /** @var array<int, true>|null */
    private ?array $favoriteCarIdsCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boleh membuka panel admin (semua staf kecuali mekanik). Hak per menu: hasPermission().
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, self::PANEL_ROLES, true);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Lihat User::PERMISSIONS; di Blade/route pakai @can / can:... (Gate yang sama).
     */
    public function hasPermission(string $ability): bool
    {
        return in_array($this->role, self::PERMISSIONS[$ability] ?? [], true);
    }

    /**
     * Role yang boleh diberikan user ini lewat menu Staf.
     *
     * @return list<string>
     */
    public function manageableStaffRoles(): array
    {
        return self::MANAGEABLE_STAFF_ROLES[$this->role] ?? [];
    }

    public function canManageStaffAccount(User $staff): bool
    {
        return $staff->id !== $this->id && in_array($staff->role, $this->manageableStaffRoles(), true);
    }

    public function isMechanic(): bool
    {
        return $this->role === self::ROLE_MECHANIC;
    }

    /**
     * Akun staf dealer (semua role selain customer): tidak memakai fitur customer (booking, pengajuan, favorit).
     */
    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    /**
     * Halaman awal setelah login sesuai role.
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isMechanic() => route('mechanic.jobs.index'),
            default => route('home'),
        };
    }

    public function testDrives(): HasMany
    {
        return $this->hasMany(TestDrive::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function serviceBookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    /** Rating yang diterima akun mekanik. */
    public function mechanicRatings(): HasMany
    {
        return $this->hasMany(MechanicRating::class, 'mechanic_id');
    }

    /**
     * Mekanik aktif (pilihan customer saat booking & penugasan admin).
     *
     * @return Builder<self>
     */
    public static function activeMechanics(): Builder
    {
        return self::query()->where('role', self::ROLE_MECHANIC)->where('is_active', true)->orderBy('name');
    }

    /** Servis yang ditugaskan ke akun mekanik. */
    public function assignedServiceBookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class, 'mechanic_id');
    }

    /**
     * Mobil favorit, terbaru ditambahkan lebih dulu.
     */
    public function favoriteCars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'favorites')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('id', 'desc');
    }

    /**
     * Id mobil favorit, diambil sekali per request (dipakai ikon hati di setiap kartu mobil).
     *
     * @return array<int, true>
     */
    public function favoriteCarIds(): array
    {
        return $this->favoriteCarIdsCache ??= $this->isAdmin()
            ? []
            : array_fill_keys($this->favoriteCars()->pluck('cars.id')->all(), true);
    }

    public function forgetFavoriteCarIds(): void
    {
        $this->favoriteCarIdsCache = null;
    }
}
