<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DesignationResponseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RugbyMatchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

// Public dashboard — visible to all
Route::get('/', [DashboardController::class, 'public'])->name('home');

// Referee accept/decline via signed URL — no auth required
Route::get('/designations/{designation}/respond/{action}', [DesignationResponseController::class, 'respond'])
    ->name('designations.respond');

// Protected area — requires login
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'private'])->name('dashboard');

    Route::resource('referees', RefereeController::class);
    Route::resource('teams', TeamController::class);
    Route::resource('venues', VenueController::class);
    Route::resource('rugby-matches', RugbyMatchController::class);
    Route::resource('designations', DesignationController::class);

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/pdf', [ReportController::class, 'pdf'])->name('pdf');
        Route::get('/markdown', [ReportController::class, 'markdown'])->name('markdown');
        Route::get('/text', [ReportController::class, 'text'])->name('text');
        Route::post('/telegram', [ReportController::class, 'telegram'])->name('telegram');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
