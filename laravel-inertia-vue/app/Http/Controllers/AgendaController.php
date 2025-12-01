<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialty;
use App\Models\Doctor;
use Illuminate\Support\Facades\Route;

class AgendaController extends Controller
{
    public function index()
    {
        $specialties = Specialty::all()->map(function ($s) {
            return [
                'id' => $s->id_specialty,
                'name' => $s->name,
            ];
        });

        $doctors = Doctor::where('status', 'Activo')->get()->map(function ($d) {
            return [
                'id' => $d->id_doctor,
                'name' => $d->name,
                'specialty_id' => $d->id_specialty,
                'slug' => $d->slug ?? null,
            ];
        });

        return inertia('Agenda/Index', [
            'specialties' => $specialties,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Página pública de agendamiento.
     */
    public function indexPublic()
    {
        // Reuse the same data mapping as index()
        $specialties = Specialty::all()->map(function ($s) {
            return [
                'id' => $s->id_specialty,
                'name' => $s->name,
            ];
        });

        $doctors = Doctor::where('status', 'Activo')->get()->map(function ($d) {
            return [
                'id' => $d->id_doctor,
                'name' => $d->name,
                'specialty_id' => $d->id_specialty,
                'slug' => $d->slug ?? null,
            ];
        });

        $canLogin = Route::has('login');
        $canRegister = Route::has('register');
        $loginUrl = route('login');
        $registerUrl = route('register');

        return inertia('Agenda/Index_public', [
            'specialties' => $specialties,
            'doctors' => $doctors,
            'canLogin' => $canLogin,
            'canRegister' => $canRegister,
            'loginUrl' => $loginUrl,
            'registerUrl' => $registerUrl,
        ]);
    }

    public function calendar(Request $request)
    {
        $slug = $request->query('doctor');
        $doctor = null;
        if ($slug) {
            $doctor = Doctor::where('slug', $slug)->first();
        }
        return inertia('Agenda/Calendar', [
            'doctor' => $doctor,
        ]);
    }
}
