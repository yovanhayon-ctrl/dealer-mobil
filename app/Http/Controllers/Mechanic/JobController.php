<?php

namespace App\Http\Controllers\Mechanic;

use App\Actions\ChangeServiceBookingStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingTask;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Panel mekanik: servis yang ditugaskan admin, mulai kerjakan, centang checklist, tulis temuan, selesaikan.
 * Booking milik mekanik lain: 404.
 */
class JobController extends Controller
{
    /** Riwayat servis selesai yang ditampilkan di bawah daftar aktif. */
    public const RECENT_COMPLETED = 10;

    public function index(Request $request): View
    {
        $jobs = ServiceBooking::query()
            ->where('mechanic_id', $request->user()->id)
            ->with(['service:id,name', 'tasks:id,service_booking_id,status'])
            ->whereIn('status', [ServiceBooking::STATUS_CONFIRMED, ServiceBooking::STATUS_IN_PROGRESS])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 ELSE 1 END")
            ->orderBy('preferred_date')
            ->orderBy('preferred_time')
            ->get();

        $completed = ServiceBooking::query()
            ->where('mechanic_id', $request->user()->id)
            ->where('status', ServiceBooking::STATUS_COMPLETED)
            ->with('service:id,name')
            ->latest('finished_at')
            ->limit(self::RECENT_COMPLETED)
            ->get();

        return view('mechanic.jobs.index', [
            'inProgress' => $jobs->where('status', ServiceBooking::STATUS_IN_PROGRESS),
            'upcoming' => $jobs->where('status', ServiceBooking::STATUS_CONFIRMED),
            'completed' => $completed,
        ]);
    }

    public function show(Request $request, ServiceBooking $serviceBooking): View
    {
        $this->ensureAssigned($request, $serviceBooking);

        $serviceBooking->load(['service:id,name,duration_minutes', 'tasks', 'user:id,name']);

        return view('mechanic.jobs.show', ['booking' => $serviceBooking]);
    }

    public function start(Request $request, ServiceBooking $serviceBooking, ChangeServiceBookingStatus $changeStatus): RedirectResponse
    {
        $this->ensureAssigned($request, $serviceBooking);
        $redirect = $this->backToJob($serviceBooking);

        if (! $serviceBooking->canBeStarted()) {
            return $redirect->with('error', $serviceBooking->status === ServiceBooking::STATUS_CONFIRMED
                ? 'Servis baru bisa dimulai pada tanggal jadwalnya ('.$serviceBooking->preferred_date->translatedFormat('d M Y').').'
                : 'Servis ini tidak bisa dimulai.');
        }

        return $this->changeStatus($changeStatus, $serviceBooking, ServiceBooking::STATUS_IN_PROGRESS, 'Servis dimulai. Customer sudah diberi tahu.');
    }

    public function finish(Request $request, ServiceBooking $serviceBooking, ChangeServiceBookingStatus $changeStatus): RedirectResponse
    {
        $this->ensureAssigned($request, $serviceBooking);

        if ($serviceBooking->status !== ServiceBooking::STATUS_IN_PROGRESS) {
            return $this->backToJob($serviceBooking)->with('error', 'Hanya servis yang sedang dikerjakan yang bisa diselesaikan.');
        }

        if ($serviceBooking->hasPendingTasks()) {
            return $this->backToJob($serviceBooking)->with('error', 'Masih ada pekerjaan yang belum dicentang. Tandai selesai atau lewati dengan alasan.');
        }

        return $this->changeStatus($changeStatus, $serviceBooking, ServiceBooking::STATUS_COMPLETED, 'Servis selesai. Customer sudah diberi tahu.');
    }

    /**
     * Centang pekerjaan: selesai, dilewati (wajib alasan), atau kembali ke belum.
     */
    public function updateTask(Request $request, ServiceBooking $serviceBooking, ServiceBookingTask $task): RedirectResponse
    {
        $this->ensureAssigned($request, $serviceBooking);

        $validated = $request->validateWithBag("task{$task->id}", [
            'status' => ['required', Rule::in(ServiceBookingTask::STATUSES)],
            'note' => [
                Rule::requiredIf($request->input('status') === ServiceBookingTask::STATUS_SKIPPED),
                'nullable', 'string', 'min:3', 'max:'.ServiceBookingTask::MAX_NOTE_LENGTH,
            ],
        ], ['note.required' => 'Tulis alasan pekerjaan ini dilewati.'], ['note' => 'alasan']);

        if ($serviceBooking->status !== ServiceBooking::STATUS_IN_PROGRESS) {
            return $this->backToJob($serviceBooking)->with('error', 'Tekan Mulai Kerjakan dulu sebelum mencentang pekerjaan.');
        }

        $task->update([
            'status' => $validated['status'],
            'note' => $validated['status'] === ServiceBookingTask::STATUS_SKIPPED ? $validated['note'] : null,
            'checked_at' => $validated['status'] === ServiceBookingTask::STATUS_PENDING ? null : now(),
        ]);

        return redirect()->to(route('mechanic.jobs.show', $serviceBooking)."#tugas-{$task->id}");
    }

    /**
     * Temuan / rekomendasi untuk customer (mis. kampas rem tinggal 20%).
     */
    public function updateFindings(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $this->ensureAssigned($request, $serviceBooking);

        $validated = $request->validateWithBag('findings', [
            'findings' => ['nullable', 'string', 'max:'.ServiceBooking::MAX_FINDINGS_LENGTH],
        ], [], ['findings' => 'temuan']);

        if (! in_array($serviceBooking->status, [ServiceBooking::STATUS_IN_PROGRESS, ServiceBooking::STATUS_COMPLETED], true)) {
            return $this->backToJob($serviceBooking)->with('error', 'Temuan bisa ditulis setelah servis dimulai.');
        }

        $serviceBooking->update(['findings' => trim((string) $validated['findings']) ?: null]);

        return redirect()->to(route('mechanic.jobs.show', $serviceBooking).'#temuan')->with('success', 'Temuan disimpan.');
    }

    private function changeStatus(ChangeServiceBookingStatus $changeStatus, ServiceBooking $booking, string $status, string $message): RedirectResponse
    {
        try {
            $updated = $changeStatus->handle($booking, $status);
        } catch (DomainException $e) {
            return $this->backToJob($booking)->with('error', $e->getMessage());
        }

        return $this->backToJob($booking)->with($updated->wasChanged('status') ? 'success' : 'status', $updated->wasChanged('status')
            ? $message
            : "Servis ini sudah berstatus {$updated->statusLabel()}.");
    }

    private function ensureAssigned(Request $request, ServiceBooking $serviceBooking): void
    {
        abort_unless($serviceBooking->isAssignedTo($request->user()), 404);
    }

    private function backToJob(ServiceBooking $serviceBooking): RedirectResponse
    {
        return redirect()->route('mechanic.jobs.show', $serviceBooking);
    }
}
