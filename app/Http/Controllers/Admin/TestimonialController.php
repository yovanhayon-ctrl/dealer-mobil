<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Notifications\Notifier;
use App\Notifications\TestimonialModerated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderasi ulasan customer: setujui (tampil di beranda) atau tolak dengan alasan.
 */
class TestimonialController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Testimonial::STATUSES, true) ? $request->query('status') : null;

        $testimonials = Testimonial::query()
            ->with([
                'user:id,name,email',
                'purchaseRequest:id,car_id',
                'purchaseRequest.car:id,brand_id,name,year',
                'purchaseRequest.car.brand:id,name',
            ])
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->latest('updated_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.testimonials.index', [
            'testimonials' => $testimonials,
            'status' => $status,
            'counts' => Testimonial::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        if ($testimonial->isApproved()) {
            return back()->with('status', 'Ulasan ini sudah disetujui.');
        }

        $testimonial->update(['status' => Testimonial::STATUS_APPROVED, 'admin_note' => null, 'approved_at' => now()]);
        Notifier::send($testimonial->user, new TestimonialModerated($testimonial));

        return back()->with('success', 'Ulasan disetujui dan kini tampil di beranda.');
    }

    public function reject(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validateWithBag(
            "reject{$testimonial->id}",
            ['admin_note' => ['required', 'string', 'min:5', 'max:500']],
            [],
            ['admin_note' => 'alasan penolakan'],
        );

        if ($testimonial->status === Testimonial::STATUS_REJECTED) {
            return back()->with('status', 'Ulasan ini sudah ditolak.');
        }

        $testimonial->update(['status' => Testimonial::STATUS_REJECTED, 'admin_note' => $validated['admin_note'], 'approved_at' => null]);
        Notifier::send($testimonial->user, new TestimonialModerated($testimonial));

        return back()->with('success', 'Ulasan ditolak. Customer diberi tahu alasannya.');
    }
}
