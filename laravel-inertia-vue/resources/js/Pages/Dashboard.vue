    <script setup>
    import { ref } from 'vue'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import { BarChart, PieChart, LineChart } from 'vue-chart-3'
    import { Chart, registerables } from 'chart.js'
    Chart.register(...registerables)

    const props = defineProps({
        citasPorEspecialidad: Object,
        citasPorFecha: Object,
        estadoCitas: Object,
        ocupacionMedico: Object,
    })

    const citasPorEspecialidad = ref(props.citasPorEspecialidad)
    const citasPorFecha = ref(props.citasPorFecha)
    const estadoCitas = ref(props.estadoCitas)
    const ocupacionMedico = ref(props.ocupacionMedico)

    const citasPorFechaOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }

    const ocupacionOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
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
                <div v-if="citasPorEspecialidad?.datasets?.[0]?.data?.length">
                    <BarChart :chart-data="citasPorEspecialidad" />
                </div>
                <p v-else class="text-gray-400 text-center">Sin datos disponibles</p>
            </div>

            <!-- Citas por Fecha -->
            <div class="bg-white shadow-lg rounded-2xl p-4">
                <h3 class="font-bold text-yellow-600 mb-3">Citas por fecha</h3>
                <div v-if="citasPorFecha?.datasets?.[0]?.data?.length">
                    <LineChart :chart-data="citasPorFecha" :chart-options="citasPorFechaOptions" style="height:220px;" />
                </div>
                <p v-else class="text-gray-400 text-center">Sin datos disponibles</p>
            </div>

            <!-- Estado de las Citas -->
            <div class="bg-white shadow-lg rounded-2xl p-4">
                <h3 class="font-bold text-green-600 mb-3">Estado de las citas</h3>
                <div v-if="estadoCitas?.datasets?.[0]?.data?.length">
                    <PieChart :chart-data="estadoCitas" />
                </div>
                <p v-else class="text-gray-400 text-center">Sin datos disponibles</p>
            </div>

            <!-- Ocupación por Médico -->
            <div class="bg-white shadow-lg rounded-2xl p-4">
                <h3 class="font-bold text-indigo-600 mb-3">Ocupación por médico</h3>
                <div v-if="ocupacionMedico?.datasets?.[0]?.data?.length">
                    <BarChart :chart-data="ocupacionMedico" :chart-options="ocupacionOptions" style="height:260px;" />
                </div>
                <p v-else class="text-gray-400 text-center">Sin datos disponibles</p>
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
