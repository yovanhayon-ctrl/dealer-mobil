{{--
    Mekanik & checklist pekerjaan pada detail booking servis (admin).
    Butuh: $booking (dengan relasi mechanic, tasks, service), $mechanics ([id => nama] mekanik aktif).
--}}
@php
    [$handled, $total] = $booking->taskProgress();
    $mechanicErrors = $errors->getBag('mechanic');
    $taskErrors = $errors->getBag('task');
@endphp

<div class="card mb-4" id="pekerjaan">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3">
            <h3 class="admin-card-title mb-0">Mekanik & Pekerjaan</h3>
            @if ($total > 0)
                <span class="small text-muted">{{ $handled }} dari {{ $total }} pekerjaan ditangani</span>
            @endif
        </div>

        @if ($booking->canBeAssigned())
            <form method="POST" action="{{ route('admin.service-bookings.assign-mechanic', $booking) }}" class="d-flex flex-wrap gap-2 align-items-start mb-3" novalidate>
                @csrf
                @method('PATCH')
                <div class="flex-grow-1">
                    <label for="mechanic_id" class="visually-hidden">Mekanik</label>
                    <select id="mechanic_id" name="mechanic_id" @class(['form-select', 'is-invalid' => $mechanicErrors->has('mechanic_id')])>
                        <option value="">{{ $mechanics === [] ? 'Belum ada akun mekanik aktif' : 'Pilih mekanik' }}</option>
                        @foreach ($mechanics as $id => $name)
                            <option value="{{ $id }}" @selected((int) old('mechanic_id', $booking->mechanic_id) === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @if ($mechanicErrors->has('mechanic_id'))
                        <div class="invalid-feedback">{{ $mechanicErrors->first('mechanic_id') }}</div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary" @disabled($mechanics === [])>
                    <i class="bi bi-person-check"></i>{{ $booking->mechanic_id ? 'Ganti Mekanik' : 'Tugaskan' }}
                </button>
            </form>
        @endif

        <p class="small mb-3">
            <span class="text-muted">Mekanik:</span>
            @if ($booking->mechanic)
                <span class="fw-semibold">{{ $booking->mechanic->name }}</span>
                @unless ($booking->mechanic->isActive())
                    <span class="text-muted">(nonaktif)</span>
                @endunless
                @if ($booking->mechanic_chosen_by_customer)
                    <span class="badge rounded-pill text-bg-light border ms-1">Dipilih customer</span>
                @endif
            @else
                <span class="text-muted">belum ditugaskan</span>
            @endif
            @if ($booking->started_at)
                <span class="text-muted ms-2">Mulai {{ $booking->started_at->translatedFormat('d M H:i') }}</span>
            @endif
            @if ($booking->finished_at)
                <span class="text-muted ms-2">Selesai {{ $booking->finished_at->translatedFormat('d M H:i') }}</span>
            @endif
        </p>

        @if ($booking->tasks->isEmpty())
            <p class="small text-muted mb-3">
                @if ($booking->mechanic_id)
                    Checklist masih kosong. Tambahkan pekerjaan di bawah, atau isi Daftar Pekerjaan Mekanik di menu Layanan Servis.
                @else
                    Checklist dari layanan {{ $booking->service->name }} disalin saat mekanik ditugaskan.
                @endif
            </p>
        @else
            <ul class="service-task-list list-unstyled small mb-3">
                @foreach ($booking->tasks as $task)
                    <li @class(['is-done' => $task->isDone(), 'is-skipped' => $task->isSkipped()])>
                        <i @class([
                            'bi',
                            'bi-check-circle-fill text-success' => $task->isDone(),
                            'bi-dash-circle text-muted' => $task->isSkipped(),
                            'bi-circle text-muted' => $task->isPending(),
                        ]) aria-hidden="true"></i>
                        <div class="flex-grow-1 min-w-0">
                            <span class="service-task-name">{{ $task->name }}</span>
                            <span class="visually-hidden">({{ $task->isDone() ? 'selesai' : ($task->isSkipped() ? 'dilewati' : 'belum') }})</span>
                            @if ($task->isSkipped() && $task->note)
                                <div class="text-muted">Dilewati: {{ $task->note }}</div>
                            @endif
                        </div>
                        @if ($task->isPending() && $booking->canBeAssigned())
                            <form method="POST" action="{{ route('admin.service-bookings.tasks.destroy', [$booking, $task]) }}"
                                  data-confirm="Hapus pekerjaan &quot;{{ $task->name }}&quot; dari checklist?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" aria-label="Hapus {{ $task->name }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($booking->canBeAssigned() && $booking->mechanic_id)
            <form method="POST" action="{{ route('admin.service-bookings.tasks.store', $booking) }}" class="d-flex gap-2 align-items-start" novalidate>
                @csrf
                <div class="flex-grow-1">
                    <label for="task-name" class="visually-hidden">Pekerjaan tambahan</label>
                    <input type="text" id="task-name" name="name" maxlength="{{ \App\Models\ServiceBookingTask::MAX_NAME_LENGTH }}"
                           value="{{ $taskErrors->any() ? old('name') : '' }}" placeholder="Pekerjaan tambahan, mis. cek bunyi roda depan"
                           @class(['form-control form-control-sm', 'is-invalid' => $taskErrors->has('name')])>
                    @if ($taskErrors->has('name'))
                        <div class="invalid-feedback">{{ $taskErrors->first('name') }}</div>
                    @endif
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i>Tambah</button>
            </form>
        @endif

        @if ($booking->findings)
            <div class="admin-note-box small rounded-3 p-2 mt-3">
                <span class="fw-semibold">Temuan mekanik:</span> {{ $booking->findings }}
            </div>
        @endif

        @if ($booking->mechanicRating)
            <div class="small mt-3">
                <span class="fw-semibold">Rating customer untuk mekanik:</span>
                <x-rating-stars :rating="$booking->mechanicRating->rating" class="text-warning ms-1" />
                @if ($booking->mechanicRating->comment)
                    <div class="text-muted mt-1">“{{ $booking->mechanicRating->comment }}”</div>
                @endif
            </div>
        @endif
    </div>
</div>
