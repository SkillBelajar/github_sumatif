<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $teachers = User::where('role', 'teacher')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'school_name', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'totalTeachers' => $teachers->count(),
            'teachers' => $teachers,
        ]);
    }
}
