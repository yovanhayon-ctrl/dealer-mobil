<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\MechanicRating;
use App\Models\ServiceBooking;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Customer menilai mekanik setelah servis selesai (satu kali per booking, tidak tampil di halaman publik).
 */
class MechanicRatingController extends Controller
{
    public function store(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        // Booking milik customer lain: 404 agar keberadaan datanya tidak terlihat.
        abort_unless($serviceBooking->user_id === $request->user()->id, 404);

        $validated = $request->validateWithBag("rating{$serviceBooking->id}", [
            'rating' => ['required', 'integer', 'between:'.Testimonial::MIN_RATING.','.Testimonial::MAX_RATING],
            'comment' => ['nullable', 'string', 'max:'.MechanicRating::MAX_COMMENT_LENGTH],
        ], ['rating.required' => 'Pilih jumlah bintang.'], ['comment' => 'komentar']);

        $back = redirect()->to(route('account.service-bookings.index')."#servis-{$serviceBooking->id}");

        if (! $serviceBooking->canBeRatedBy($request->user())) {
            return $back->with('status', $serviceBooking->mechanicRating()->exists()
                ? 'Anda sudah memberi rating untuk servis ini.'
                : 'Rating mekanik bisa diberikan setelah servis selesai.');
        }

        $serviceBooking->mechanicRating()->create([
            'mechanic_id' => $serviceBooking->mechanic_id,
            'user_id' => $request->user()->id,
            'rating' => (int) $validated['rating'],
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
        ]);

        return $back->with('success', 'Terima kasih! Rating Anda membantu kami menjaga kualitas bengkel.');
    }
}
