<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetExportController;
use App\Http\Controllers\AssetImportController;
use App\Http\Controllers\AssetPeripheralController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/password', function () {
        return view('profile.password');
    })->name('password.edit');

    // Fase 2: Manajemen Aset & Periferal
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/export/excel', [AssetExportController::class, 'export'])->name('assets.export');
    Route::get('/assets/import/template', [AssetImportController::class, 'template'])->name('assets.import.template');

    Route::middleware('role:super_admin,teknisi')->group(function () {
        Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::post('/assets/bulk-delete', [AssetController::class, 'bulkDestroy'])->name('assets.bulk-destroy');
        Route::post('/assets/import/excel', [AssetImportController::class, 'import'])->name('assets.import');

        Route::post('/assets/{asset}/peripherals', [AssetPeripheralController::class, 'store'])->name('assets.peripherals.store');
        Route::delete('/peripherals/{peripheral}', [AssetPeripheralController::class, 'destroy'])->name('assets.peripherals.destroy');
    });

    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    // Fase 3: Penjadwalan & Reservasi Lab
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');

    Route::middleware('role:super_admin,teknisi,instruktur')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    });

    Route::middleware('role:super_admin,teknisi')->group(function () {
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    });

    // Fase 4: Helpdesk & Tiket (Kanban)
    Route::middleware('role:super_admin,teknisi,instruktur')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    });

    Route::middleware('role:super_admin,teknisi')->group(function () {
        Route::post('/tickets/{ticket}/start', [TicketController::class, 'start'])->name('tickets.start');
        Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    });
});

require __DIR__.'/auth.php';
