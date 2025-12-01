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

    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor'])->orderBy('date', 'desc')->orderBy('time', 'desc')->get();
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
     * Formulario público para confirmar datos y reservar cita.
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
     * Crear cita
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
        if (!empty($patient->email)) {
            try {
                Mail::to($patient->email)->send(new AppointmentNotification($appointment, 'pending'));
            } catch (\Exception $e) {
                logger()->error('Mail send failed: '.$e->getMessage());
            }
        }
        return redirect()->back()->with('success', 'Cita creada correctamente. Revise su correo para la confirmación.');
    }

    public function storePublic(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Eliminar cita
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['deleted' => true]);
    }

    /** Aprobar cita */
    public function approve($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'approved';
        $appointment->save();
        if ($appointment->patient && !empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new AppointmentNotification($appointment, 'approved'));
            } catch (\Exception $e) {
                logger()->error('Mail send failed: '.$e->getMessage());
            }
        }
        return response()->json(['status' => $appointment->status]);
    }

    /** Completar cita */
    public function complete($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'completed';
        $appointment->save();
        return response()->json(['status' => $appointment->status]);
    }

    /** Rechazar cita */
    public function deny($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = 'rejected';
        $appointment->save();
        if ($appointment->patient && !empty($appointment->patient->email)) {
            try {
                Mail::to($appointment->patient->email)->send(new AppointmentNotification($appointment, 'rejected'));
            } catch (\Exception $e) {
                logger()->error('Mail send failed: '.$e->getMessage());
            }
        }
        return response()->json(['status' => $appointment->status]);
    }

    /**
     * Citas por medico cada semana (API)
     */
    public function appointmentsForDoctor(Request $request, $doctorParam)
    {
        if ($doctorParam instanceof Doctor) {
            $doctor = $doctorParam;
        } else {
            if (is_numeric($doctorParam)) {
                $doctor = Doctor::where('id_doctor', (int) $doctorParam)->firstOrFail();
            } else {
                $doctor = Doctor::where('slug', $doctorParam)->firstOrFail();
            }
        }
        $start = $request->query('start');
        try {
            $startDate = $start ? \Carbon\Carbon::parse($start)->startOfDay() : \Carbon\Carbon::today()->startOfWeek(\Carbon\Carbon::MONDAY);
        } catch (\Exception $e) {
            $startDate = \Carbon\Carbon::today()->startOfWeek(\Carbon\Carbon::MONDAY);
        }
        $endDate = $startDate->copy()->addDays(5)->endOfDay();
        $appts = Appointment::with('patient')
            ->where('id_doctor', $doctor->id_doctor)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
        $grouped = [];
        foreach ($appts as $a) {
            $date = $a->date;
            if (!isset($grouped[$date])) $grouped[$date] = [];
            $grouped[$date][] = [
                'id' => $a->getKey(),
                'time' => $a->time,
                'status' => $a->status,
                'patient' => $a->patient ? ['name' => $a->patient->name, 'document' => $a->patient->document] : null,
                'notes' => $a->notes ?? null,
            ];
        }
        return response()->json($grouped);
    }
}

