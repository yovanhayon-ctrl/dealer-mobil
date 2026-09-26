{{-- Progress bar sederhana untuk persentase ($value 0–100). --}}
<div class="progress report-progress" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar" style="width: {{ min(100, max(0, $value)) }}%"></div>
</div>
