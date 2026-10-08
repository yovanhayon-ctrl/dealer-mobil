<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\View\View;

/**
 * Halaman publik JAF Service (/servis): daftar layanan aktif + ajakan booking.
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        return view('pages.services.index', [
            'services' => Service::query()->active()->orderBy('id')->get(),
        ]);
    }
}
