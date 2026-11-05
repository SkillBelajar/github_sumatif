<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Teacher\AssessmentSessionController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LearningObjectiveController;
use App\Http\Controllers\Teacher\QuestionBankController;
use App\Http\Controllers\Teacher\QuestionImportController;
use App\Http\Controllers\Student\AssessmentController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

// Landing Page
Route::get('/', [WelcomeController::class, 'show'])->name('home');

// Siswa: akses ujian tanpa login
Route::prefix('ujian')->name('student.')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('access');
    Route::post('/check', [AssessmentController::class, 'checkToken'])->name('check');
    Route::get('/{assessmentSession}/prepare', [AssessmentController::class, 'prepare'])->name('prepare');
    Route::post('/{assessmentSession}/start', [AssessmentController::class, 'start'])->name('start');
    Route::post('/{assessmentSession}/answer', [AssessmentController::class, 'saveAnswer'])->name('answer');
    Route::post('/{assessmentSession}/submit', [AssessmentController::class, 'submit'])->name('submit');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});

// Teacher routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    // Tujuan Pembelajaran (TP)
    Route::get('/learning-objectives', [LearningObjectiveController::class, 'index'])->name('learning-objectives.index');
    Route::post('/learning-objectives', [LearningObjectiveController::class, 'store'])->name('learning-objectives.store');
    Route::put('/learning-objectives/{learningObjective}', [LearningObjectiveController::class, 'update'])->name('learning-objectives.update');
    Route::delete('/learning-objectives/{learningObjective}', [LearningObjectiveController::class, 'destroy'])->name('learning-objectives.destroy');

    // Bank Soal
    Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::post('/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::put('/question-bank/{question}', [QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/question-bank/{question}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');
    Route::get('/question-bank/template', [QuestionImportController::class, 'downloadTemplate'])->name('question-bank.template');
    Route::post('/question-bank/import', [QuestionImportController::class, 'import'])->name('question-bank.import');

    // Sesi Asesmen
    Route::get('/assessments', [AssessmentSessionController::class, 'index'])->name('assessment-sessions.index');
    Route::post('/assessments', [AssessmentSessionController::class, 'store'])->name('assessment-sessions.store');
    Route::put('/assessments/{assessmentSession}', [AssessmentSessionController::class, 'update'])->name('assessment-sessions.update');
    Route::delete('/assessments/{assessmentSession}', [AssessmentSessionController::class, 'destroy'])->name('assessment-sessions.destroy');
    Route::get('/assessments/{assessmentSession}/questions', [AssessmentSessionController::class, 'questions'])->name('assessment-sessions.questions');
    Route::post('/assessments/{assessmentSession}/questions', [AssessmentSessionController::class, 'syncQuestions'])->name('assessment-sessions.sync-questions');
    Route::post('/assessments/{assessmentSession}/activate', [AssessmentSessionController::class, 'activate'])->name('assessment-sessions.activate');
});
