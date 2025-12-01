<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DoctorController;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Disponibilidad pública por médico (semana actual)
Route::get('/doctors/{doctor}/availability', [DoctorController::class, 'availability']);

// API: comprobar si paciente existe por documento
Route::get('/patients/check', function (Request $request) {
    $document = $request->query('document');
    if (!$document) {
        return response()->json(['exists' => false], 400);
    }
    $patient = Patient::where('document', $document)->first();
    if ($patient) {
        return response()->json(['exists' => true, 'patient' => $patient]);
    }
    return response()->json(['exists' => false]);
});

// API: crear paciente (público, usado desde el modal)
Route::post('/patients', function (Request $request) {
    $data = $request->only(['name','document','email','eps']);

    try {
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => ['required','string','max:255'],
            'document' => ['required','string','max:20','unique:patients,document'],
            'email' => ['nullable','email','max:255'],
            'eps' => ['nullable','string','max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    } catch (\Illuminate\Database\QueryException $e) {
        // Likely a missing table or DB schema issue — return a helpful message for development
        Log::error('Validation database error while creating patient: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Database error during patient validation. Ensure migrations have been run (php artisan migrate).',
            'error' => $e->getMessage(),
        ], 500);
    }

    try {
        $patient = Patient::create($data);
        return response()->json(['success' => true, 'patient' => $patient], 201);
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Patient create DB error: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Database error while creating patient. Ensure migrations have been run (php artisan migrate).',
            'error' => $e->getMessage(),
        ], 500);
    } catch (\Exception $e) {
        Log::error('Patient create error: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json(['success' => false, 'message' => 'Server error while creating patient.'], 500);
    }
});
