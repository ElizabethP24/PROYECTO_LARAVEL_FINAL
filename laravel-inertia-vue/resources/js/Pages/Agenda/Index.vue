<script setup>
import { ref, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    specialties: Array,
    doctors: Array,
})

const selectedSpecialty = ref('')
const filteredDoctors = ref([])
const selectedDoctor = ref('')
const weekDays = ref([])
const weekOffset = ref(0)
const availability = ref({})
const selectedSlot = ref(null)
const loading = ref(false)
const success = ref(false)
const documentNumber = ref('')
const showPatientModal = ref(false)
const newPatient = ref({ name: '', document: '', email: '', eps: '' })
const epsOptions = ['SURA', 'Nueva EPS', 'Salud Total', 'Sanitas', 'Otra']

// Comprueba si una fecha/hora está en el pasado (no agendable)
function isSlotDisabled(dateStr, timeStr) {
    try {
        // Construir Date con formato YYYY-MM-DDTHH:MM:SS local
        const slot = new Date(dateStr + 'T' + timeStr + ':00')
        const now = new Date()

        // Bloquear slots que ya pasaron
        if (slot <= now) return true

        // Si el slot es para el día actual, bloquear si está a menos de 40 minutos
        const todayStr = now.toISOString().split('T')[0]
        if (dateStr === todayStr) {
            const diffMs = slot - now
            const min40 = 40 * 60 * 1000
            if (diffMs < min40) return true
        }

        return false
    } catch (e) {
        return false
    }
}



// 📅 Generar semana (offset: 0 = semana actual, +1 siguiente, -1 anterior)
function generateWeek(offset = 0) {
    const today = new Date()
    const day = today.getDay()
    // calcular el lunes de la semana (tratando domingo como día 0)
    const diff = today.getDate() - day + (day === 0 ? -6 : 1) + offset * 7
    const monday = new Date(today)
    monday.setDate(diff)

    // Generar solo lunes a sábado (6 días)
    weekDays.value = Array.from({ length: 6 }, (_, i) => {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)
        return {
            label: d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' }),
            value: d.toISOString().split('T')[0],
        }
    })

    // Si ya hay un doctor seleccionado, recargar disponibilidad para la semana
    if (selectedDoctor.value) {
        fetchAvailability(selectedDoctor.value)
    }
}

// 🔄 Filtrar doctores al escoger especialidad
watch(selectedSpecialty, (value) => {
    filteredDoctors.value = props.doctors.filter(d => d.specialty_id == value)
    selectedDoctor.value = ''
    availability.value = {}
})

// 📡 Cargar disponibilidad semanal
async function fetchAvailability(id) {
    const doctorId = id ?? selectedDoctor.value
    if (!doctorId) return
    loading.value = true
    try {
        const params = {}
        if (weekDays.value.length) params.start = weekDays.value[0].value
        // Intentar con el id/slug tal cual; el servidor acepta ambos ahora
        const { data } = await axios.get(`/api/doctors/${doctorId}/availability`, { params })
        availability.value = data ?? {}
    } catch (err) {
        console.error('Fetch availability error', err)
        availability.value = {}
    } finally {
        loading.value = false
    }
}

watch(selectedDoctor, (id) => {
    if (!id) return
    fetchAvailability(id)
})

function prevWeek() {
    weekOffset.value -= 1
    generateWeek(weekOffset.value)
    selectedSlot.value = null
}

function nextWeek() {
    weekOffset.value += 1
    generateWeek(weekOffset.value)
    selectedSlot.value = null
}

// 📨 Enviar cita
async function submitAppointment() {
    if (!selectedSlot.value || !selectedDoctor.value || !documentNumber.value) return
    loading.value = true
    try {
        await router.post('/appointments', {
            doctor_id: selectedDoctor.value,
            date: selectedSlot.value.date,
            time: selectedSlot.value.time,
            document: documentNumber.value,
        })
        success.value = true
        selectedSlot.value = null
    } finally {
        loading.value = false
    }
}

// Comprueba si el paciente existe; si no, abre modal para crearlo
async function checkPatientAndProceed() {
    if (!documentNumber.value) return
    loading.value = true
    try {
        const res = await axios.get('/api/patients/check', { params: { document: documentNumber.value } })
        if (res.data.exists) {
            // paciente existe, proceder a agendar
            await submitAppointment()
            return
        }
        // abrir modal para crear paciente
        newPatient.value = { name: '', document: documentNumber.value, email: '', eps: '' }
        showPatientModal.value = true
    } catch (e) {
        // Si la comprobación falla, abrir modal para crear
        newPatient.value = { name: '', document: documentNumber.value, email: '', eps: '' }
        showPatientModal.value = true
    } finally {
        loading.value = false
    }
}

// Crear paciente vía API y luego agendar
async function createPatientAndSchedule() {
    loading.value = true
    try {
        const payload = { ...newPatient.value }
        const res = await axios.post('/api/patients', payload)
        if (res.data && res.data.patient) {
            showPatientModal.value = false
            // asegurar documentNumber y proceder a agendar
            documentNumber.value = res.data.patient.document
            await submitAppointment()
        }
    } catch (err) {
        // mostrar errores (simple alert por ahora)
        const msgs = err.response?.data?.errors
        if (msgs) {
            alert(Object.values(msgs).flat().join('\n'))
        } else {
            alert('Error creando paciente')
        }
    } finally {
        loading.value = false
    }
}

onMounted(generateWeek)

function selectSlot(date, time) {
    if (isSlotDisabled(date, time)) return
    selectedSlot.value = { date, time }
}
</script>

<template>
<AppLayout title="Agendar Cita Médica">
    <template #header>
        <h2 class="font-semibold text-2xl text-gray-800">Agendamiento de Citas</h2>
    </template>

    <div class="py-10 px-6 max-w-5xl mx-auto">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-4">Agendamiento de citas — Agenda semanal</h1>
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div v-if="success" class="mb-6 bg-green-100 text-green-800 p-4 rounded-xl text-center">
                ✅ Cita agendada correctamente
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="font-semibold text-gray-700">Documento del paciente</label>
                    <input v-model="documentNumber" placeholder="Ingrese documento"
                           class="w-full mt-2 mb-4 p-3 border rounded-xl focus:ring-2 focus:ring-blue-400" />

                    <label class="font-semibold text-gray-700">Especialidad</label>
                    <select v-model="selectedSpecialty" class="w-full mt-2 mb-4 p-3 border rounded-xl">
                        <option value="">Seleccione especialidad</option>
                        <option v-for="s in props.specialties" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <label class="font-semibold text-gray-700">Doctor</label>
                    <select v-model="selectedDoctor" :disabled="filteredDoctors.length === 0"
                            class="w-full mt-2 mb-4 p-3 border rounded-xl">
                        <option value="">Seleccione doctor</option>
                        <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- 📅 Calendario semanal -->
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Seleccione día y hora</h3>
                    <div v-if="loading" class="text-center text-gray-500 py-8">Cargando disponibilidad...</div>

                    <div v-else class="grid grid-cols-6 gap-2">
                            <div v-for="day in weekDays" :key="day.value" class="border rounded-xl p-2 text-center">
                                <div class="font-bold text-sm text-blue-700 mb-2">{{ day.label }}</div>
                                    <div v-if="availability[day.value]?.length">
                                        <button
                                            v-for="slot in availability[day.value]"
                                            :key="slot"
                                            @click="selectSlot(day.value, slot)"
                                            :disabled="isSlotDisabled(day.value, slot)"
                                            :class="[
                                                'w-full my-1 py-1.5 text-sm rounded-lg transition',
                                                selectedSlot?.time === slot && selectedSlot?.date === day.value
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-blue-100 hover:bg-blue-200 text-blue-700',
                                                isSlotDisabled(day.value, slot) ? 'opacity-50 cursor-not-allowed' : ''
                                            ]">
                                            {{ slot }}
                                        </button>
                                    </div>
                            <div v-else class="text-xs text-gray-400">Sin horarios</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Rango de semana (debajo del calendario) -->
            <div class="text-center mt-4 text-sm text-gray-600">
                <span v-if="weekDays.length">Semana: {{ weekDays[0].label }} — {{ weekDays[weekDays.length - 1].label }}</span>
            </div>

            <!-- Botones de navegación de semana (debajo del rango) -->
            <div class="flex justify-center mt-3 space-x-3">
                <button @click="prevWeek" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">← Semana anterior</button>
                <button @click="nextWeek" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Semana siguiente →</button>
            </div>

            <button
                @click="checkPatientAndProceed"
                :disabled="!selectedSlot || loading"
                class="mt-8 w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold py-3 rounded-xl text-lg hover:scale-[1.02] transition">
                {{ loading ? 'Agendando...' : 'Confirmar Cita' }}
            </button>

            <!-- Modal crear paciente (si no existe) -->
            <div
                v-if="showPatientModal"
                @click.self="showPatientModal = false"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            >
                <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-bold">Nuevo Paciente</h2>
                        <button @click="showPatientModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <div class="p-6 space-y-3">
                        <input v-model="newPatient.name" type="text" placeholder="Nombre completo" class="w-full border rounded p-2" />
                        <input v-model="newPatient.document" type="text" placeholder="Documento" class="w-full border rounded p-2" />
                        <!-- El campo teléfono fue eliminado -->
                        <input v-model="newPatient.email" type="email" placeholder="Correo" class="w-full border rounded p-2" />
                        <select v-model="newPatient.eps" class="w-full border rounded p-2">
                            <option value="">Seleccione EPS</option>
                            <option v-for="opt in epsOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <div class="px-6 py-4 border-t flex justify-end space-x-2">
                        <button @click="showPatientModal = false" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
                        <button @click="createPatientAndSchedule" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
