<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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

    /** Pemilik dealer: semua akses admin + kelola akun staf. */
    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    /** Mekanik bengkel: hanya panel Pekerjaan Saya. */
    public const ROLE_MECHANIC = 'mechanic';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_LABELS = [
        self::ROLE_OWNER => 'Owner',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_MECHANIC => 'Mekanik',
        self::ROLE_CUSTOMER => 'Customer',
    ];

    /** Role yang bisa diberikan owner lewat menu Staf (owner baru lewat perintah user:make-owner). */
    public const ASSIGNABLE_STAFF_ROLES = [self::ROLE_ADMIN, self::ROLE_MECHANIC];

    public const STAFF_ROLES = [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MECHANIC];

    /** Bisa membuka panel admin (owner & admin). */
    public const PANEL_ROLES = [self::ROLE_OWNER, self::ROLE_ADMIN];

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
     * Boleh membuka panel admin: owner atau admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, self::PANEL_ROLES, true);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isMechanic(): bool
    {
        return $this->role === self::ROLE_MECHANIC;
    }

    /**
     * Akun staf dealer (owner, admin, mekanik): tidak memakai fitur customer (booking, pengajuan, favorit).
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
