<?php

use App\Http\Controllers\Api\DiagnosticController;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — APELS Platform
|--------------------------------------------------------------------------
|
| Semua route di file ini otomatis mendapat prefix `/api`.
| Auth menggunakan session-based (web guard) karena Livewire SPA.
|
*/

Route::middleware(['auth', 'role:mahasiswa', 'daily.test.limit'])->group(function () {
    Route::post('diagnostic/submit', [DiagnosticController::class, 'submit'])
        ->name('api.diagnostic.submit');
});

Route::middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('dashboard', [ApiController::class, 'dashboard'])
        ->name('api.dashboard');
    Route::get('learning-path', [ApiController::class, 'learningPath'])
        ->name('api.learning-path');
    Route::get('feedback', [ApiController::class, 'feedback'])
        ->name('api.feedback');
});

Route::middleware(['auth', 'role:admin|dosen'])->group(function () {
    Route::post('questions', [ApiController::class, 'createQuestion'])
        ->name('api.questions.create');
});
