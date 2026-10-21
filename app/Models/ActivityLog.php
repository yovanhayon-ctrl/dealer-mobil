<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Throwable;

/**
 * Catatan aktivitas sensitif staf, dilihat Owner & Super Admin di Staf > Riwayat Aktivitas.
 * Dicatat langsung di controller lewat record() setelah aksi berhasil. Tidak bisa diubah atau dihapus dari aplikasi.
 */
#[Fillable(['user_id', 'user_name', 'user_role', 'category', 'action', 'description', 'subject_type', 'subject_id', 'properties', 'ip_address'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const PER_PAGE = 25;

    public const CATEGORY_ACCOUNT = 'account';

    public const CATEGORY_FINANCE = 'finance';

    public const CATEGORY_PURCHASE = 'purchase';

    public const CATEGORY_DELETE = 'delete';

    public const CATEGORY_LOGIN = 'login';

    /** Kelompok => [label, warna badge]. */
    public const CATEGORIES = [
        self::CATEGORY_ACCOUNT => ['Akun & Sistem', 'blue'],
        self::CATEGORY_FINANCE => ['Keuangan', 'green'],
        self::CATEGORY_PURCHASE => ['Pengajuan', 'yellow'],
        self::CATEGORY_DELETE => ['Hapus Data', 'red'],
        self::CATEGORY_LOGIN => ['Login', 'muted'],
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat satu aktivitas atas nama $user (bawaan: user yang login). Kegagalan mencatat tidak membatalkan aksi utama.
     *
     * @param  array<string, mixed>  $properties  nilai lama/baru atau detail lain
     */
    public static function record(
        string $category,
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): ?self {
        try {
            $user ??= auth()->user();

            return self::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistem',
                'user_role' => $user?->role ?? '-',
                'category' => $category,
                'action' => $action,
                'description' => mb_substr($description, 0, 500),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Rupiah untuk kalimat aktivitas, mis. "Rp 50.000.000".
     */
    public static function rupiah(int|float $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category][0] ?? $this->category;
    }

    public function categoryColor(): string
    {
        return self::CATEGORIES[$this->category][1] ?? 'muted';
    }

    public function roleLabel(): string
    {
        return User::ROLE_LABELS[$this->user_role] ?? $this->user_role;
    }

    /**
     * Tautan ke data terkait bila masih ada (relasi subject harus sudah dimuat).
     */
    public function subjectUrl(): ?string
    {
        if (str_starts_with($this->action, 'settings.')) {
            return route('admin.settings.edit');
        }

        return match (true) {
            $this->subject instanceof PurchaseRequest => route('admin.purchase-requests.show', $this->subject),
            $this->subject instanceof User => route('admin.users.show', $this->subject),
            default => null,
        };
    }
}
