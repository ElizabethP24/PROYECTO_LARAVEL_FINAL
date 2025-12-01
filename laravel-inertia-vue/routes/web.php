<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use Inertia\Inertia;

// Public agenda (landing) — handled by AgendaController and accessible without login
Route::get('/agenda/public', [AgendaController::class, 'indexPublic'])->name('agenda.public');

// Root redirects to the public agenda landing (keeps Jetstream auth routes intact)
Route::get('/', function () {
    return redirect()->route('agenda.public');
});

Route::post('/appointments/store-public', [AppointmentController::class, 'storePublic'])->name('appointments.storePublic');
Route::post('patients/store-public', [PatientController::class, 'storePublic'])->name('patients.storePublic');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard route expected by Jetstream/Fortify
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/home', [AppointmentController::class, 'adminHome'])->name('home');

    Route::resource('patients', PatientController::class);
    Route::resource('doctors', DoctorController::class)->scoped(['doctor' => 'slug']);

    Route::resource('appointments', AppointmentController::class);
    Route::post('/appointments/{appointment}/approve', [AppointmentController::class, 'approve'])->name('appointments.approve');
    Route::post('/appointments/{appointment}/deny', [AppointmentController::class, 'deny'])->name('appointments.deny');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
});

