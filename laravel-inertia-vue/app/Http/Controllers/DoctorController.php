<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use Carbon\Carbon;


class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Doctors/Index', [
            'doctors' => Doctor::all(),
            'specialties' => Specialty::all(),
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
    public function store(StoreDoctorRequest $request)
    {
        $validated = $request->validated();

        $doctor = DB::transaction(function () use ($validated) {

            // Crear usuario primero
            $email = Str::slug($validated['name']) . '@clinicalocal.com';

            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'role' => 'doctor',
                'password' => Hash::make($validated['document']),
            ]);

            // Crear doctor con el user_id ya disponible
            $doc = Doctor::create([
                'name'        => $validated['name'],
                'document'    => $validated['document'],
                'id_specialty'=> $validated['id_specialty'],
                'user_id'     => $user->id,
                'email'       => $email,
                'status'      => $validated['status'] ?? 'active',
            ]);

            return $doc;
        });

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor creado correctamente.');
    }



    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor)
    {
        return response()->json(['success' => true, 'doctor' => $doctor]);
    }

    /**
     * Perfil público del médico (por slug) y disponibilidad próxima.
     */
    public function publicProfile($slug)
    {
        $doctor = Doctor::where('slug', $slug)->firstOrFail();

        // Reusar la función availability para obtener slots (devuelve JsonResponse)
        $availabilityResponse = $this->availability(request(), $doctor);
        $availability = json_decode($availabilityResponse->getContent(), true);

        return Inertia::render('Doctors/PublicProfile', [
            'doctor' => $doctor,
            'availability' => $availability,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Doctor $doctor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $validated = $request->validated();
        $doctor->update($validated);

        return redirect()->route('doctors.index')->with('success', 'Doctor actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()->route('doctors.index')->with('success', 'Doctor eliminado correctamente.');
    }

    /**
     * Devuelve la disponibilidad semanal del médico (API).
     * Retorna un array donde la clave es la fecha YYYY-MM-DD y el valor es lista de horas disponibles.
     */
    public function availability(Request $request, $doctorParam)
    {
        // Aceptar tanto el slug (binding por defecto) como el id numérico.
        // Si se recibió un objeto Doctor por binding, normalizarlo.
        if ($doctorParam instanceof Doctor) {
            $doctor = $doctorParam;
        } else {
            // Buscar por id_doctor (numérico) o por slug.
            $doctor = Doctor::where('id_doctor', $doctorParam)
                ->orWhere('slug', $doctorParam)
                ->firstOrFail();
        }
        // Configuración (puede ajustarse en .env)
        $start = env('WORK_START', '08:00');
        $end = env('WORK_END', '17:00');
        $duration = (int) env('APPOINTMENT_DURATION_MINUTES', 20);

        // Determinar fecha de inicio (se acepta ?start=YYYY-MM-DD para navegar semanas)
        $startParam = $request->query('start');
        if ($startParam) {
            try {
                $baseDate = Carbon::parse($startParam);
            } catch (\Exception $e) {
                $baseDate = Carbon::today();
            }
        } else {
            $baseDate = Carbon::today();
        }
        // Obtener el lunes de la semana del baseDate
        $monday = $baseDate->copy()->startOfWeek(Carbon::MONDAY);

        $availability = [];

        // Generar disponibilidad de lunes a sábado (6 días)
        for ($i = 0; $i < 6; $i++) {
            $date = $monday->copy()->addDays($i)->toDateString();

            // Obtener horas ocupadas para ese día
            $booked = Appointment::where('id_doctor', $doctor->id_doctor)
                ->where('date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('time')
                ->map(function ($t) {
                    return substr($t, 0, 5); // format HH:MM
                })->toArray();

            // Generar slots entre start y end
            $slots = [];
            $current = Carbon::createFromFormat('H:i', $start);
            $finish = Carbon::createFromFormat('H:i', $end);
            while ($current->lte($finish)) {
                $time = $current->format('H:i');
                if (!in_array($time, $booked)) {
                    $slots[] = $time;
                }
                $current->addMinutes($duration);
            }

            $availability[$date] = $slots;
        }

        return response()->json($availability);
    }
}
