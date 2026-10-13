<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Panel mekanik: servis yang ditugaskan admin (daftar & checklist menyusul di fase penugasan).
 */
class JobController extends Controller
{
    public function index(): View
    {
        return view('mechanic.jobs.index');
    }
}
