
<template>
	<AppLayout title="Agenda por Médico">
		<template #header>
			<h2 class="font-semibold text-2xl text-gray-800">Agenda por Médico</h2>
		</template>

		<div class="py-10 px-6 max-w-6xl mx-auto">
			<div class="mb-4 flex items-center gap-3">
				<label class="font-semibold">Médico:</label>
				<select v-model="selectedDoctorSlug" @change="onDoctorChange" class="border px-2 py-1 rounded">
					<option v-for="d in doctors" :key="d.slug || d.id" :value="d.slug ?? d.id">{{ d.name }}</option>
				</select>

				<div class="ml-auto flex items-center gap-2">
					<button @click="prevWeek" class="px-3 py-1 bg-gray-200 rounded">← Semana anterior</button>
					<div class="text-sm text-gray-600">Semana: {{ weekRangeLabel }}</div>
					<button @click="nextWeek" class="px-3 py-1 bg-gray-200 rounded">Semana siguiente →</button>
				</div>
			</div>

			<div class="bg-white rounded-2xl shadow-lg p-4">
				<div v-if="loading" class="text-center py-8 text-gray-500">Cargando calendario…</div>

				<div v-else>
					<div class="overflow-x-auto">
						<div :style="gridStyle" class="grid gap-2">
							<!-- Header row: empty cell for times column + day headers -->
							<div class="p-2"></div>
							<div v-for="day in weekDays" :key="day.value" class="p-2 text-center font-semibold text-sm text-blue-700 border-b">{{ day.label }}</div>

							<!-- Rows: time slots in first column, then cells for each day -->
							<template v-for="time in timeSlots" :key="time">
								<div class="p-2 text-sm text-gray-700 font-medium border-r">{{ time }}</div>
								<div v-for="day in weekDays" :key="day.value + '-' + time" class="min-h-[56px] border rounded p-2">
									<div v-if="appointmentAt(day.value, time)" class="bg-blue-50 p-2 rounded h-full flex flex-col justify-between">
										<div class="text-sm font-medium">{{ appointmentAt(day.value, time).patient?.name ?? 'Paciente' }}</div>
										<div class="text-xs text-gray-600">{{ appointmentAt(day.value, time).status }}</div>
										<div class="text-xs text-gray-500">{{ appointmentAt(day.value, time).time }}</div>
									</div>
									<div v-else class="h-full flex items-center justify-center text-xs text-gray-300">-</div>
								</div>
							</template>
						</div>
					</div>
				</div>
			</div>
		</div>
	</AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
	doctor: { type: Object, required: false },
	doctors: { type: Array, default: () => [] },
	initialAppointments: { type: Array, default: () => [] },
	initialDate: { type: String, default: () => new Date().toISOString().slice(0, 10) },
})

const selectedDate = ref(props.initialDate)
const appointments = ref([])
const loading = ref(false)
const selectedDoctorSlug = ref(props.doctor?.slug ?? (props.doctors && (props.doctors[0]?.slug ?? props.doctors[0]?.id)) ?? null)

const doctors = props.doctors || []

const selectedDoctorName = computed(() => {
	if (!selectedDoctorSlug.value) return '—'
	const d = (doctors || []).find(x => (x.slug ?? String(x.id)) === selectedDoctorSlug.value)
	return d ? d.name : selectedDoctorSlug.value
})

// Week calendar state
const weekDays = ref([])
const weekOffset = ref(0)

const workStart = ref(props.work_start ?? '08:00')
const workEnd = ref(props.work_end ?? '17:00')
const slotMinutes = ref(Number(props.slot_minutes ?? 20))

const timeSlots = ref([])
const appointmentsByDate = ref({})

const gridStyle = computed(() => ({ gridTemplateColumns: `160px repeat(${weekDays.value.length}, minmax(0, 1fr))` }))

const weekRangeLabel = computed(() => {
	if (!weekDays.value.length) return ''
	return `${weekDays.value[0].label} — ${weekDays.value[weekDays.value.length - 1].label}`
})

function statusClass(status) {
	const s = String(status || '').toLowerCase()
	if (s.includes('approved') || s.includes('acept')) return 'text-green-700'
	if (s.includes('rejected') || s.includes('rechaz')) return 'text-red-700'
	if (s.includes('pending') || s.includes('pend')) return 'text-yellow-700'
	return 'text-gray-700'
}

// Generate time slots from workStart to workEnd
function generateTimeSlots() {
	const slots = []
	const [h0, m0] = (workStart.value || '08:00').split(':').map(Number)
	const [h1, m1] = (workEnd.value || '17:00').split(':').map(Number)
	let dt = new Date()
	dt.setHours(h0, m0, 0, 0)
	const end = new Date()
	end.setHours(h1, m1, 0, 0)
	while (dt <= end) {
		const hh = String(dt.getHours()).padStart(2, '0')
		const mm = String(dt.getMinutes()).padStart(2, '0')
		slots.push(`${hh}:${mm}`)
		dt.setMinutes(dt.getMinutes() + slotMinutes.value)
	}
	timeSlots.value = slots
}

// Week generation (Monday..Saturday)
function generateWeek(offset = 0) {
	const today = new Date()
	const day = today.getDay()
	const diff = today.getDate() - day + (day === 0 ? -6 : 1) + offset * 7
	const monday = new Date(today)
	monday.setDate(diff)
	weekDays.value = Array.from({ length: 6 }, (_, i) => {
		const d = new Date(monday)
		d.setDate(monday.getDate() + i)
		return {
			label: d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' }),
			value: d.toISOString().split('T')[0],
		}
	})
}

function prevWeek() {
	weekOffset.value -= 1
	generateWeek(weekOffset.value)
	fetchAppointmentsWeek()
}

function nextWeek() {
	weekOffset.value += 1
	generateWeek(weekOffset.value)
	fetchAppointmentsWeek()
}

function appointmentAt(date, time) {
	const list = appointmentsByDate.value[date] || []
	// Normalize time comparison to HH:MM
	return list.find(a => {
		const t = (a.time || '').toString()
		// try common formats: HH:MM:SS or HH:MM
		const norm = t.length >= 5 ? t.slice(0,5) : t
		return norm === time
	}) || null
}

async function fetchAppointmentsWeek() {
	if (!selectedDoctorSlug.value || !weekDays.value.length) return
	loading.value = true
	try {
		const start = weekDays.value[0].value
		const res = await axios.get(`/api/doctors/${selectedDoctorSlug.value}/appointments`, { params: { start } })
		appointmentsByDate.value = res.data ?? {}
	} catch (e) {
		console.error('Error fetching week appointments', e)
		appointmentsByDate.value = {}
	} finally {
		loading.value = false
	}
}

function formatTime(value) {
	if (!value) return '-'
	// value may be "HH:MM" or full datetime; try to extract time
	const t = String(value)
	if (/^\d{2}:\d{2}/.test(t)) return t.slice(0,5)
	try {
		const d = new Date(t)
		return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
	} catch (e) {
		return t
	}
}

async function fetchAppointments() {
	// keep for backward compatibility (single-day fetch)
	if (!selectedDoctorSlug.value) return
	loading.value = true
	try {
		const doctorId = selectedDoctorSlug.value
		const url = `/doctors/${doctorId}/agenda`
		const res = await axios.get(url, { params: { date: selectedDate.value } })
		appointments.value = res.data.appointments ?? res.data ?? []
	} catch (err) {
		console.error('Error fetching appointments', err)
		appointments.value = []
	} finally {
		loading.value = false
	}
}

function onDoctorChange() {
	// when doctor changes, reset appointments and fetch for new doctor
	appointments.value = []
	fetchAppointments()
}

onMounted(() => {
	generateWeek(weekOffset.value)
	generateTimeSlots()
	fetchAppointmentsWeek()
})

// Re-fetch when selected doctor changes
watch(selectedDoctorSlug, () => {
	fetchAppointmentsWeek()
})
</script>

<style scoped>
.status {
	font-weight: 600;
}
</style>
