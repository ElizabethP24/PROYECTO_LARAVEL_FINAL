<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentNotification;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments (admin view).
     */
    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor'])->orderBy('date', 'desc')->orderBy('time', 'desc')->get();

        // Normalizar datos para la vista Inertia
        $data = $appointments->map(function ($a) {
            return [
                'id' => $a->getKey(),
                'date' => $a->date,
                'time' => $a->time,
                'status' => $a->status,
                'notes' => $a->notes ?? null,
                'patient' => $a->patient ? ['name' => $a->patient->name, 'document' => $a->patient->document] : null,
                'doctor' => $a->doctor ? ['name' => $a->doctor->name] : null,
            ];
        });

        return \Inertia\Inertia::render('Appointments/Index', [
            'appointments' => $data,
        ]);
    }
    /**
     * Formulario público para confirmar datos y reservar (GET /appointments/new?doctor={slug}&start={datetime})
     */
    public function createPublic(Request $request)
    {
        $slug = $request->query('doctor');
        $start = $request->query('start');

        $doctor = null;
        if ($slug) {
            $doctor = Doctor::where('slug', $slug)->first();
        }

        return Inertia::render('Appointments/New', [
            'doctor' => $doctor,
            'start' => $start,
        ]);
    }

    /**
     * Admin home: resumen de pendientes y próximas confirmadas.
     */
    public function adminHome()
    {
        $pending = Appointment::where('status', 'pending')->count();
        $upcoming = Appointment::where('status', 'approved')->where('date', '>=', date('Y-m-d'))->count();

        return Inertia::render('Home', [
            'pending' => $pending,
            'upcoming' => $upcoming,
        ]);
    }
    /**
     * Store a newly created appointment (public).
     */
    public function store(Request $request)
    {
        $data = $request->only(['doctor_id', 'date', 'time', 'document']);

        $validator = Validator::make($data, [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id_doctor'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'document' => ['required', 'string'],
        ], [
            'doctor_id.required' => 'Seleccione un médico.',
            'date.required' => 'Seleccione una fecha.',
            'time.required' => 'Seleccione una hora.',
            'document.required' => 'Ingrese su documento de identidad.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Evitar colisiones: mismo médico, misma fecha y hora con estado Pendiente de aprobación/Confirmada
        $exists = Appointment::where('id_doctor', $data['doctor_id'])
            ->where('date', $data['date'])
            ->where('time', $data['time'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'El horario seleccionado ya no está disponible.')->withInput();
        }

        // Buscar o crear paciente por documento
        $patient = Patient::firstOrCreate(
            ['document' => $data['document']],
            ['name' => 'Paciente ' . Str::limit($data['document'], 10), 'email' => null]
        );

        $doctor = Doctor::find($data['doctor_id']);

        $appointment = Appointment::create([
            'id_patient' => $patient->id_patient ?? $patient->getKey(),
            'id_doctor' => $doctor->id_doctor,
            'id_specialty' => $doctor->id_specialty,
            'date' => $data['date'],
            'time' => $data['time'],
            'status' => 'pending',
        ]);

        // Enviar correo de notificación si paciente tiene email
        if (!empty($patient->email)) {
            try {
                Mail::to($patient->email)->send(new AppointmentNotification($appointment, 'pending'));
            } catch (\Exception $e) {
                // Log or ignore for now - do not break user flow
                // logger()->error('Mail send failed: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Cita creada correctamente. Revise su correo si proporcionó uno.');
    }

    /**
     * Public wrapper for store so the route can point explicitly to `storePublic`.
     * This reuses the same validation and creation logic from `store`.
     */
    public function storePublic(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Update an appointment (admin).
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $data = $request->only(['date', 'time', 'notes']);
        $validator = Validator::make($data, [
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'date.required' => 'Seleccione una fecha.',
            'time.required' => 'Seleccione una hora.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $appointment->date = $data['date'];
        $appointment->time = $data['time'];
        if (array_key_exists('notes', $data)) $appointment->notes = $data['notes'];
        $appointment->save();

        return response()->json(['appointment' => $appointment]);
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['deleted' => true]);
    }

    /** Approve appointment */
    public function approve($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'approved';
        $appointment->save();
        // Notify patient if email available
        if ($appointment->patient && !empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new AppointmentNotification($appointment, 'approved'));
            } catch (\Exception $e) {
                // logger()->error('Mail send failed: '.$e->getMessage());
            }
        }

        return response()->json(['status' => $appointment->status]);
    }

    /** Complete appointment */
    public function complete($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'completed';
        $appointment->save();
        return response()->json(['status' => $appointment->status]);
    }

    /** Deny (reject) appointment */
    public function deny($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'rejected';
        $appointment->save();
        // Notify patient if email available
        if ($appointment->patient && !empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new AppointmentNotification($appointment, 'rejected'));
            } catch (\Exception $e) {
                // logger()->error('Mail send failed: '.$e->getMessage());
            }
        }

        return response()->json(['status' => $appointment->status]);
    }
}

