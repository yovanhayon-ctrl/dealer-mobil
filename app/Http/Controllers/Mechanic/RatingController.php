<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rating dan komentar customer untuk mekanik yang login (nama customer disingkat).
 */
class RatingController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $mechanic = $request->user();
        $ratings = $mechanic->mechanicRatings()
            ->with(['user:id,name', 'booking:id,service_id,plate_number,vehicle_model', 'booking.service:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('mechanic.ratings.index', [
            'ratings' => $ratings,
            'average' => $mechanic->mechanicRatings()->avg('rating'),
            'distribution' => $mechanic->mechanicRatings()->selectRaw('rating, COUNT(*) AS total')->groupBy('rating')->pluck('total', 'rating'),
            'completedJobs' => $mechanic->assignedServiceBookings()->where('status', ServiceBooking::STATUS_COMPLETED)->count(),
        ]);
    }
}
