<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $questionCount = Question::whereHas('learningObjective', fn ($q) => $q->where('user_id', $userId))->count();

        return Inertia::render('Teacher/Dashboard', [
            'stats' => [
                'sessions' => AssessmentSession::where('user_id', $userId)->count(),
                'active_sessions' => AssessmentSession::where('user_id', $userId)->where('status', 'active')->count(),
                'questions' => $questionCount,
                'subjects' => Subject::where('user_id', $userId)->count(),
            ],
        ]);
    }
}
