<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with aggregated data for charts.
     */
    public function index(Request $request)
    {
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

        // Citas por fecha (últimos 7 días)
        $end = Carbon::now()->toDateString();
        $start = Carbon::now()->subDays(6)->toDateString();

        $byDate = Appointment::selectRaw('date, count(*) as total')
            ->whereBetween('date', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = collect();
        for ($d = Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) {
            $dates->push($d->toDateString());
        }
        $dateTotals = $dates->map(function ($dt) use ($byDate) {
            $found = $byDate->firstWhere('date', $dt);
            return $found ? (int) $found->total : 0;
        });

        $citasPorFecha = [
            'labels' => $dates->toArray(),
            'datasets' => [
                [
                    'label' => 'Citas diarias',
                    'data' => $dateTotals->toArray(),
                    'backgroundColor' => '#f6c23e',
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
            // occupancy = percentage of appointments that are approved (agendadas)
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
