<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        // Redirect doctors to their own agenda instead of showing the admin dashboard
        $user = Auth::user();
        if ($user && $user->role === 'doctor') {
            $doctor = Doctor::where('user_id', $user->id)->first();
            $param = $doctor ? $doctor->slug : $user->id;
            return redirect()->route('doctors.agenda', $param);
        }
        // Citas por especialidad
        $bySpecialty = Appointment::selectRaw('specialties.name as name, count(*) as total')
            ->join('specialties', 'appointments.id_specialty', '=', 'specialties.id_specialty')
            ->groupBy('specialties.name')
            ->orderByDesc('total')
            ->get();

        $citasPorEspecialidad = [
            'labels' => $bySpecialty->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Cantidad de citas',
                    'data' => $bySpecialty->pluck('total')->toArray(),
                    'backgroundColor' => ['#4e73df', '#1cc88a', '#36b9cc'],
                ],
            ],
        ];

        // Citas por mes (últimos 12 meses)
        $endMonth = Carbon::now()->endOfMonth();
        $startMonth = Carbon::now()->subMonths(11)->startOfMonth();

        $months = collect();
        $cursor = $startMonth->copy();
        while ($cursor->lte($endMonth)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        // Labels in Spanish (e.g., "Noviembre 2025")
        $monthLabels = $months->map(function ($m) {
            return ucfirst($m->locale('es')->isoFormat('MMMM YYYY'));
        })->toArray();
        $monthTotals = $months->map(function ($m) {
            $start = $m->copy()->startOfMonth()->toDateString();
            $end = $m->copy()->endOfMonth()->toDateString();
            return Appointment::whereBetween('date', [$start, $end])->count();
        })->toArray();

        $citasPorFecha = [
            'labels' => $monthLabels,
            'datasets' => [
                [
                    'label' => 'Citas por mes',
                    'data' => $monthTotals,
                    'backgroundColor' => '#f6c23e',
                    'fill' => false,
                ],
            ],
        ];

        // Estado de las citas
        $byStatus = Appointment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()->keyBy('status');

        $statusOrder = ['approved' => 'Aprobadas', 'pending' => 'Pendientes', 'rejected' => 'Rechazadas', 'completed' => 'Completadas'];
        $estadoData = [];
        foreach ($statusOrder as $key => $label) {
            $estadoData[] = isset($byStatus[$key]) ? (int) $byStatus[$key]->total : 0;
        }

        $estadoCitas = [
            'labels' => array_values($statusOrder),
            'datasets' => [
                [
                    'label' => 'Estado de citas',
                    'data' => $estadoData,
                    'backgroundColor' => ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
                ],
            ],
        ];

        // Ocupación por médico: total de citas en agenda y % agendadas (approved)
        $doctors = Doctor::withCount(['appointments', 'appointments as approved_count' => function ($q) {
            $q->where('status', 'approved');
        }])->get();

        $labels = $doctors->pluck('name')->toArray();
        $percentApproved = [];
        foreach ($doctors as $d) {
            $total = (int) $d->appointments_count;
            $appr = (int) $d->approved_count;
            $percentApproved[] = $total > 0 ? (int) round(($appr / $total) * 100) : 0;
        }
        $ocupacionMedico = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ocupación (%)',
                    'data' => $percentApproved,
                    'backgroundColor' => $doctors->map(fn($d, $i) => ['#4e73df', '#36b9cc', '#1cc88a'][$i % 3])->toArray(),
                ],
            ],
        ];
        return Inertia::render('Dashboard', compact('citasPorEspecialidad', 'citasPorFecha', 'estadoCitas', 'ocupacionMedico'));
    }
}
