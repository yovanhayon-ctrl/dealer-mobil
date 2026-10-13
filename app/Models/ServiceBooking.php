<?php

namespace App\Models;

use Database\Factories\ServiceBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'service_id', 'vehicle_model', 'plate_number', 'vehicle_year', 'mileage',
    'preferred_date', 'preferred_time', 'phone', 'complaint', 'status', 'admin_note',
    'mechanic_id', 'findings', 'started_at', 'finished_at',
])]
class ServiceBooking extends Model
{
    /** @use HasFactory<ServiceBookingFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED, self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_CONFIRMED => 'Dikonfirmasi',
        self::STATUS_IN_PROGRESS => 'Dikerjakan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    /** Transisi status yang diizinkan. Status lain adalah status akhir. */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
    ];

    /** Status yang wajib disertai catatan admin (alasan untuk customer). */
    public const NOTE_REQUIRED_STATUSES = [self::STATUS_CANCELLED];

    /** Status yang masih memakai slot bengkel: dipakai cek kapasitas slot & booking ganda per plat. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS];

    /** Customer hanya boleh membatalkan saat status ini. */
    public const CUSTOMER_CANCELLABLE_STATUSES = [self::STATUS_PENDING];

    /** Slot jam booking servis (WIB), per jam 08:00–15:00. */
    public const TIME_SLOTS = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'];

    /** Rentang tanggal booking: besok s/d hari ini + 30 hari (WIB). */
    public const MIN_DAYS_AHEAD = 1;

    public const MAX_DAYS_AHEAD = 30;

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'vehicle_year' => 'integer',
            'mileage' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Booking yang masih memakai slot bengkel (pending / confirmed / in_progress).
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Mekanik yang ditugaskan admin. */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /** Checklist pekerjaan booking ini, urut sesuai posisi. */
    public function tasks(): HasMany
    {
        return $this->hasMany(ServiceBookingTask::class)->orderBy('position')->orderBy('id');
    }

    /** Rating customer untuk mekanik booking ini (satu per booking). */
    public function mechanicRating(): HasOne
    {
        return $this->hasOne(MechanicRating::class);
    }

    /**
     * Customer pemilik booking boleh memberi rating satu kali setelah servis selesai dikerjakan mekanik.
     */
    public function canBeRatedBy(?User $user): bool
    {
        return $user !== null
            && $this->user_id === $user->id
            && $this->status === self::STATUS_COMPLETED
            && $this->mechanic_id !== null
            && ! $this->mechanicRating()->exists();
    }

    /** Batas temuan / rekomendasi mekanik. */
    public const MAX_FINDINGS_LENGTH = 2000;

    /** Mekanik ditugaskan / checklist diubah selama booking belum selesai atau batal. */
    public function canBeAssigned(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isAssignedTo(?User $user): bool
    {
        return $user !== null && $this->mechanic_id === $user->id;
    }

    /**
     * Salin daftar pekerjaan bawaan layanan bila booking belum punya checklist.
     */
    public function copyChecklistFromService(): void
    {
        if ($this->tasks()->exists()) {
            return;
        }

        foreach ($this->service->checklistItems() as $position => $name) {
            $this->tasks()->create(['name' => $name, 'position' => $position + 1]);
        }
    }

    /**
     * [jumlah pekerjaan yang sudah ditangani (selesai / dilewati), total pekerjaan].
     *
     * @return array{int, int}
     */
    public function taskProgress(): array
    {
        $this->loadMissing('tasks');

        return [$this->tasks->reject->isPending()->count(), $this->tasks->count()];
    }

    public function hasPendingTasks(): bool
    {
        $this->loadMissing('tasks');

        return $this->tasks->contains->isPending();
    }

    /**
     * Mekanik boleh mulai pada tanggal jadwal (sama dengan aturan admin).
     */
    public function canBeStarted(): bool
    {
        return $this->status === self::STATUS_CONFIRMED && ! $this->preferred_date->isAfter(today());
    }

    /**
     * Kapasitas bengkel: jumlah booking aktif maksimal per slot (tanggal + jam).
     */
    public static function slotCapacity(): int
    {
        return max(1, (int) config('dealer.service_slot_capacity', 3));
    }

    /** Format plat setelah dinormalisasi: "B 1234 ABC", "AB 12", "D 1 A". */
    public const PLATE_REGEX = '/^[A-Z]{1,2} [0-9]{1,4}( [A-Z]{1,3})?$/';

    /**
     * Plat nomor dinormalisasi: huruf besar, satu spasi antar bagian
     * ("b 1234  abc" / "b1234abc" → "B 1234 ABC").
     */
    public static function normalizePlate(string $plate): string
    {
        $plate = strtoupper(trim((string) preg_replace('/\s+/', ' ', $plate)));

        if (preg_match('/^([A-Z]{1,2})([0-9]{1,4})([A-Z]{0,3})$/', str_replace(' ', '', $plate), $parts)) {
            return trim("{$parts[1]} {$parts[2]} {$parts[3]}");
        }

        return $plate;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES, true)
            && $this->canTransitionTo(self::STATUS_CANCELLED);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Jam jadwal "HH:MM" (kolom time bisa berisi detik).
     */
    public function timeLabel(): string
    {
        return substr((string) $this->preferred_time, 0, 5);
    }
}
