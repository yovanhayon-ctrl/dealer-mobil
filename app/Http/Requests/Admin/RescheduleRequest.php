<?php

namespace App\Http\Requests\Admin;

use App\Actions\RescheduleBooking;
use App\Http\Requests\ServiceBookingRequest;
use App\Http\Requests\TestDriveRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Jadwal ulang test drive / booking servis oleh staf. Rentang tanggal & jam sama dengan booking customer.
 */
class RescheduleRequest extends FormRequest
{
    /** Error ditampilkan di form jadwal ulang, bukan form status. */
    protected $errorBag = 'reschedule';

    /**
     * Akses dibatasi middleware can:... pada grup route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        [$first, $last, $slots] = $this->booking() instanceof TestDrive
            ? [TestDriveRequest::firstDate(), TestDriveRequest::lastDate(), TestDrive::TIME_SLOTS]
            : [ServiceBookingRequest::firstDate(), ServiceBookingRequest::lastDate(), ServiceBooking::TIME_SLOTS];

        return [
            'preferred_date' => ['required', 'date_format:Y-m-d', "after_or_equal:{$first}", "before_or_equal:{$last}"],
            'preferred_time' => ['required', 'string', Rule::in($slots)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                $message = app(RescheduleBooking::class)->conflict(
                    $this->booking(),
                    $this->string('preferred_date')->value(),
                    $this->string('preferred_time')->value(),
                );

                if ($message !== null) {
                    $validator->errors()->add('preferred_time', $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['preferred_date' => 'tanggal baru', 'preferred_time' => 'jam baru'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $slots = $this->booking() instanceof TestDrive ? TestDrive::TIME_SLOTS : ServiceBooking::TIME_SLOTS;

        return [
            'preferred_date.after_or_equal' => 'Tanggal baru paling cepat besok.',
            'preferred_date.before_or_equal' => 'Tanggal baru terlalu jauh ke depan.',
            'preferred_time.in' => 'Pilih jam antara '.reset($slots).' dan '.end($slots).' WIB.',
        ];
    }

    public function booking(): TestDrive|ServiceBooking
    {
        return $this->route('testDrive') ?? $this->route('serviceBooking');
    }
}
