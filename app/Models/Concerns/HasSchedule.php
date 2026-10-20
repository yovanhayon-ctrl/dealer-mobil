<?php

namespace App\Models\Concerns;

use App\Models\TestDrive;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aturan jadwal bersama test drive & booking servis (kolom preferred_date/preferred_time):
 * - lewat jadwal: masih Menunggu/Dikonfirmasi padahal tanggalnya sudah lewat (hari ini belum);
 * - Tidak datang: hanya dari Dikonfirmasi dan pada/sesudah tanggal jadwal;
 * - jadwal ulang: selama masih Menunggu/Dikonfirmasi, atau Tidak datang (customer minta jadwal baru).
 */
trait HasSchedule
{
    /**
     * @return list<string>
     */
    public static function openScheduleStatuses(): array
    {
        return [static::STATUS_PENDING, static::STATUS_CONFIRMED];
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, static::openScheduleStatuses(), true)
            && $this->preferred_date !== null
            && $this->preferred_date->lt(today());
    }

    /**
     * Status "Tidak datang" baru bisa dipilih pada atau sesudah tanggal jadwal.
     *
     * @param  list<string>  $transitions
     * @return list<string>
     */
    protected function withoutEarlyNoShow(array $transitions): array
    {
        if ($this->preferred_date === null || ! $this->preferred_date->isAfter(today())) {
            return $transitions;
        }

        return array_values(array_diff($transitions, [static::STATUS_NO_SHOW]));
    }

    /**
     * Catatan bawaan untuk customer saat ditandai Tidak datang.
     */
    public function noShowNote(): string
    {
        return 'Anda tidak hadir pada jadwal '.$this->preferred_date->translatedFormat('d M Y')." pukul {$this->timeLabel()} WIB."
            .' Silakan booking ulang bila masih berminat.';
    }

    /**
     * Pesan WhatsApp siap kirim (wa.me) untuk mengingatkan customer secara manual; null bila nomor kosong.
     */
    public function whatsappReminderUrl(): ?string
    {
        $number = preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $this->phone));

        if ($number === '') {
            return null;
        }

        $this->loadMissing($this instanceof TestDrive ? ['user', 'car.brand'] : ['user', 'service']);
        $dealer = config('dealer');
        $what = $this instanceof TestDrive
            ? "test drive {$this->car->brand->name} {$this->car->name}"
            : "servis {$this->service->name} untuk {$this->plate_number}";
        $text = "Halo {$this->user->name}, kami dari {$dealer['name']} mengingatkan jadwal {$what} pada "
            .$this->preferred_date->translatedFormat('l, d M Y')." pukul {$this->timeLabel()} WIB"
            .(filled($dealer['address']) ? " di {$dealer['address']}" : '').'.'
            .($this instanceof TestDrive ? ' Mohon bawa SIM yang masih berlaku.' : ' Mohon datang 15 menit lebih awal.')
            .' Terima kasih.';

        return "https://wa.me/{$number}?text=".rawurlencode($text);
    }

    public function canBeRescheduled(): bool
    {
        return in_array($this->status, [...static::openScheduleStatuses(), static::STATUS_NO_SHOW], true);
    }

    /**
     * Aturannya sama dengan isOverdue().
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), static::openScheduleStatuses())
            ->whereDate($query->qualifyColumn('preferred_date'), '<', today()->toDateString());
    }
}
