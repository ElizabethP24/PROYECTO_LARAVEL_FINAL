<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { BarChart, PieChart, LineChart } from 'vue-chart-3'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

// Accept props from Laravel (Inertia). Fall back to example values when not provided.
const props = defineProps({
    citasPorEspecialidad: Object,
    citasPorFecha: Object,
    estadoCitas: Object,
    ocupacionMedico: Object,
})

const citasPorEspecialidad = ref(props.citasPorEspecialidad ?? {
    labels: ['Cardiología', 'Pediatría', 'Neurología'],
    datasets: [
        {
            label: 'Cantidad de citas',
            data: [10, 5, 8],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
        },
    ],
})

const citasPorFecha = ref(props.citasPorFecha ?? {
    labels: ['2025-11-01', '2025-11-02', '2025-11-03'],
    datasets: [
        {
            label: 'Citas diarias',
            data: [4, 7, 3],
            backgroundColor: '#f6c23e',
            fill: false,
        },
    ],
})

// Force Y axis from 0 to 20 for citasPorFecha and show integer ticks
const citasPorFechaOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            min: 0,
            suggestedMax: 20,
            ticks: {
                stepSize: 1,
                callback: function(value) { return Number.isInteger(value) ? value : null }
            }
        }
    }
}

const estadoCitas = ref(props.estadoCitas ?? {
    labels: ['Aprobadas', 'Pendientes', 'Rechazadas'],
    datasets: [
        {
            label: 'Estado de citas',
            data: [12, 4, 2],
            backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
        },
    ],
})

// Expect controller to provide percentages (0-100). Fallback uses example percentages.
const ocupacionMedico = ref(props.ocupacionMedico ?? {
    labels: ['Dr. Pérez', 'Dra. Gómez', 'Dr. Ruiz'],
    datasets: [
        {
            label: 'Ocupación (%)',
            data: [75, 50, 80],
            backgroundColor: ['#4e73df', '#36b9cc', '#1cc88a'],
        },
    ],
})

const ocupacionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            min: 0,
            max: 100,
            ticks: { stepSize: 10 }
        }
    }
}
    </script>

    <template>
    <AppLayout title="Dashboard">
        <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard de Citas Médicas
        </h2>
        </template>

        <div class="py-6 px-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Citas por Especialidad -->
        <div class="bg-white shadow-lg rounded-2xl p-4">
            <h3 class="font-bold text-blue-600 mb-3">Citas por especialidad</h3>
            <BarChart :chart-data="citasPorEspecialidad" />
        </div>

        <!-- Citas por Fecha -->
        <div class="bg-white shadow-lg rounded-2xl p-4">
            <h3 class="font-bold text-yellow-600 mb-3">Citas por fecha</h3>
            <LineChart :chart-data="citasPorFecha" :chart-options="citasPorFechaOptions" style="height:220px;" />
        </div>

        <!-- Estado de las Citas -->
        <div class="bg-white shadow-lg rounded-2xl p-4">
            <h3 class="font-bold text-green-600 mb-3">Estado de las citas</h3>
            <PieChart :chart-data="estadoCitas" />
        </div>

        <!-- Ocupación por Médico -->
        <div class="bg-white shadow-lg rounded-2xl p-4">
            <h3 class="font-bold text-indigo-600 mb-3">Ocupación por médico</h3>
            <BarChart :chart-data="ocupacionMedico" :chart-options="ocupacionOptions" style="height:260px;" />
        </div>
        </div>
    </AppLayout>
    </template>

    <style scoped>
    .bg-white {
    background-color: #fff;
    }
    .shadow-lg {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .rounded-2xl {
    border-radius: 1rem;
    }
</style>
