<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Patients/Index', [
            'patients' => Patient::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request)
    {
        $validated = $request->validated();
        Patient::create($validated);

        return redirect()->route('patients.index')->with('success', 'Paciente creado correctamente.');
    }

    /**
     * Store a patient from the public agenda (no auth required).
     * Accepts minimal data and returns JSON with the patient record.
     */
    public function storePublic(Request $request)
    {
        $data = $request->only(['name', 'document', 'email', 'eps']);

        $validator = Validator::make($data, [
            'document' => ['required', 'numeric'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'eps' => ['nullable', 'string', 'max:255'],
        ], [
            'document.required' => 'El documento es obligatorio.',
            'document.numeric' => 'El documento debe ser numérico.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // FirstOrCreate by document to avoid duplicates
        $patient = Patient::firstOrCreate(
            ['document' => $data['document']],
            [
                'name' => $data['name'] ?? ('Paciente ' . substr($data['document'], 0, 10)),
                'email' => $data['email'] ?? null,
                'eps' => $data['eps'] ?? null,
            ]
        );

        // If the record existed but we received additional info, try to update it
        $updated = false;
        if (!empty($data['name']) && $patient->name !== $data['name']) {
            $patient->name = $data['name'];
            $updated = true;
        }
        if (!empty($data['email']) && $patient->email !== $data['email']) {
            $patient->email = $data['email'];
            $updated = true;
        }
        if (!empty($data['eps']) && $patient->eps !== ($data['eps'] ?? null)) {
            $patient->eps = $data['eps'];
            $updated = true;
        }
        if ($updated) $patient->save();

        return response()->json(['success' => true, 'patient' => $patient]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        return response()->json(['success' => true, 'patient' => $patient]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $validated = $request->validated();
        $patient->update($validated);

        return redirect()->route('patients.index')->with('success', 'Paciente actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Paciente eliminado correctamente.');
    }


}
