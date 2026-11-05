<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class UserGuideController extends Controller
{
    /** Panduan pengguna: konten dinamis sesuai role yang login. */
    public function show()
    {
        $user = auth()->user();

        return Inertia::render('UserGuide', [
            'role' => $user?->role ?? 'guest',
        ]);
    }
}
