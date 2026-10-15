<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\View\View;

/**
 * Layanan bengkel aktif beserta checklist bawaannya (hanya baca), sebagai acuan mekanik.
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        return view('mechanic.services.index', [
            'services' => Service::query()->active()->orderBy('name')->get(['id', 'name', 'description', 'checklist', 'duration_minutes']),
        ]);
    }
}
