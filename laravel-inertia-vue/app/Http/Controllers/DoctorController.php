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
    public function index()
    {
        return Inertia::render('Doctors/Index', [
            'doctors' => Doctor::all(),
            'specialties' => Specialty::all(),
        ]);
    }

    /**
     * Crear doctor
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


    public function show(Doctor $doctor)
    {
        return response()->json(['success' => true, 'doctor' => $doctor]);
    }

    /**
     * Perfil público (disponibilidad del médico).
     */
    public function publicProfile($slug)
    {
        $doctor = Doctor::where('slug', $slug)->firstOrFail();
        $availabilityResponse = $this->availability(request(), $doctor);
        $availability = json_decode($availabilityResponse->getContent(), true);

        return Inertia::render('Doctors/PublicProfile', [
            'doctor' => $doctor,
            'availability' => $availability,
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $validated = $request->validated();
        $doctor->update($validated);

        return redirect()->route('doctors.index')->with('success', 'Doctor actualizado correctamente.');
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()->route('doctors.index')->with('success', 'Doctor eliminado correctamente.');
    }

    /**
     * Agenda para un médico
     */
    public function showAgenda(Doctor $doctor)
    {
        $doctors = Doctor::where('status', 'Activo')->get()->map(function ($d) {
            return [
                'id' => $d->id_doctor,
                'name' => $d->name,
                'slug' => $d->slug ?? null,
            ];
        });
        $workStart = env('WORK_START', '08:00');
        $workEnd = env('WORK_END', '17:00');
        $slotMinutes = (int) env('APPOINTMENT_DURATION_MINUTES', 20);

        return Inertia::render('Doctors/Agenda', [
            'doctor' => $doctor,
            'doctors' => $doctors,
            'work_start' => $workStart,
            'work_end' => $workEnd,
            'slot_minutes' => $slotMinutes,
        ]);
    }

    /**
     * Devuelve la disponibilidad semanal del médico
     */
    public function availability(Request $request, $doctorParam)
    {
        if ($doctorParam instanceof Doctor) {
            $doctor = $doctorParam;
        } else {
            $doctor = Doctor::where('id_doctor', $doctorParam)
                ->orWhere('slug', $doctorParam)
                ->firstOrFail();
        }
        $start = env('WORK_START', '08:00');
        $end = env('WORK_END', '17:00');
        $duration = (int) env('APPOINTMENT_DURATION_MINUTES', 20);
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
        $monday = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $availability = [];
        for ($i = 0; $i < 6; $i++) {
            $date = $monday->copy()->addDays($i)->toDateString();
            $booked = Appointment::where('id_doctor', $doctor->id_doctor)
                ->where('date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('time')
                ->map(function ($t) {
                    return substr($t, 0, 5);
                })->toArray();
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
