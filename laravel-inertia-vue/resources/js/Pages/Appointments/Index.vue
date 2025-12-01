    <script setup>
    import { ref, computed, onMounted } from 'vue'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import axios from 'axios'
    import { router } from '@inertiajs/vue3'

    const props = defineProps({
        appointments: Array,
    })

    const appointmentsList = ref(props.appointments ? [...props.appointments] : [])
    const loading = ref(false)
    const showConfirm = ref(false)
    const confirmTitle = ref('')
    const confirmText = ref('')
    let confirmAction = null
    let confirmPayload = null
    const showAlert = ref(false)
    const alertMessage = ref('')
    const showSuccess = ref(false)
    const successMessage = ref('')

    const page = ref(1)
    const perPage = ref(10)
    const totalPages = computed(() => Math.max(1, Math.ceil(appointmentsList.value.length / perPage.value)))
    const pagedAppointments = computed(() => {
    const start = (page.value - 1) * perPage.value
    return appointmentsList.value.slice(start, start + perPage.value)
    })

    function setPage(p) {
    page.value = Math.min(Math.max(1, p), totalPages.value)
    }

    function statusLabel(status) {
    return {
        pending: 'Pendiente',
        approved: 'Aprobada',
        completed: 'Completada',
        rejected: 'Rechazada',
    }[status] || status
    }

    function findIndexById(id) {
        return appointmentsList.value.findIndex(a => a.id === id)
    }

    async function approveAppointment(id) {
    loading.value = true
    try {
        await axios.post(`/appointments/${id}/approve`)
        const idx = findIndexById(id)
        if (idx !== -1) appointmentsList.value[idx].status = 'approved'
        successMessage.value = 'Cita aprobada'
        showSuccess.value = true
    } catch (e) {
        alertMessage.value = 'Error aprobando cita'
        showAlert.value = true
    } finally { loading.value = false }
    }

    async function completeAppointment(id) {
    loading.value = true
    try {
        await axios.post(`/appointments/${id}/complete`)
        const idx = findIndexById(id)
        if (idx !== -1) appointmentsList.value[idx].status = 'completed'
        successMessage.value = 'Cita marcada como completada'
        showSuccess.value = true
    } catch (e) {
        alertMessage.value = 'Error marcando cita como completada'
        showAlert.value = true
    } finally { loading.value = false }
    }

    async function denyAppointment(id) {
    loading.value = true
    try {
        await axios.post(`/appointments/${id}/deny`)
        const idx = findIndexById(id)
        if (idx !== -1) appointmentsList.value[idx].status = 'rejected'
        successMessage.value = 'Cita denegada'
        showSuccess.value = true
    } catch (e) {
        alertMessage.value = 'Error denegando cita'
        showAlert.value = true
    } finally { loading.value = false }
    }

    async function deleteAppointment(id) {
        loading.value = true
        try {
            await axios.delete(`/appointments/${id}`)
            const idx = findIndexById(id)
            if (idx !== -1) appointmentsList.value.splice(idx, 1)
            if (page.value > totalPages.value) page.value = totalPages.value
            successMessage.value = 'Cita eliminada'
            showSuccess.value = true
        } catch (e) {
            alertMessage.value = 'Error eliminando cita'
            showAlert.value = true
        } finally { loading.value = false }
    }

    function openConfirm(title, text, actionFn, payload = null) {
    confirmTitle.value = title
    confirmText.value = text
    confirmAction = actionFn
    confirmPayload = payload
    showConfirm.value = true
    }

    function performConfirm() {
    showConfirm.value = false
    if (confirmAction) {
        try {
        confirmAction(confirmPayload)
        } finally {
        confirmAction = null
        confirmPayload = null
        }
    }
    }

    function closeAlert() { showAlert.value = false }
    function closeSuccess() { showSuccess.value = false }
    onMounted(() => {
    appointmentsList.value = [...props.appointments]
    })
    </script>

    <template>
    <AppLayout title="Citas">
        <template #header>
        <h2 class="font-semibold text-xl text-gray-800">Citas</h2>
        </template>

        <div class="py-8 px-6 max-w-6xl mx-auto">
            <h1 class="text-3xl font-extrabold text-gray-800 mb-4">Gestión de Citas</h1>
            <div class="bg-white rounded-2xl shadow p-6">
            <div v-if="appointmentsList.length === 0" class="text-gray-500">No hay citas registradas.</div>

            <div v-else class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Paciente</th>
                    <th class="px-4 py-2">Doctor</th>
                    <th class="px-4 py-2">Fecha</th>
                    <th class="px-4 py-2">Hora</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="apt in pagedAppointments" :key="apt.id" class="border-t">
                    <td class="px-4 py-3">{{ apt.id }}</td>
                    <td class="px-4 py-3">{{ apt.patient?.name || apt.patient_name }}<br/><span class="text-xs text-gray-400">{{ apt.patient?.document || apt.document }}</span></td>
                    <td class="px-4 py-3">{{ apt.doctor?.name || apt.doctor_name }}</td>
                    <td class="px-4 py-3">{{ apt.date }}</td>
                    <td class="px-4 py-3">{{ apt.time }}</td>
                    <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-sm" :class="{
                        'bg-yellow-100 text-yellow-800': apt.status === 'pending',
                        'bg-blue-100 text-blue-800': apt.status === 'approved',
                        'bg-green-100 text-green-800': apt.status === 'completed',
                        'bg-red-100 text-red-800': apt.status === 'rejected'
                        }">{{ statusLabel(apt.status) }}</span>
                    </td>
                    <td class="px-4 py-3">
                    <div class="flex gap-2 flex-wrap">
                        <button @click="() => openConfirm('Eliminar cita', '¿Eliminar esta cita permanentemente?', () => deleteAppointment(apt.id))" class="px-2 py-1 bg-red-200 text-red-800 rounded text-sm">Eliminar</button>
                        <button v-if="apt.status === 'pending'" @click="() => openConfirm('Aprobar cita', '¿Aprobar esta cita?', () => approveAppointment(apt.id))" class="px-2 py-1 bg-blue-500 text-white rounded text-sm">Aprobar</button>
                        <button v-if="apt.status === 'pending'" @click="() => openConfirm('Denegar cita', '¿Denegar esta cita?', () => denyAppointment(apt.id))" class="px-2 py-1 bg-red-500 text-white rounded text-sm">Denegar</button>
                        <button v-if="apt.status === 'approved'" @click="() => openConfirm('Completar cita', '¿Marcar cita como completada?', () => completeAppointment(apt.id))" class="px-2 py-1 bg-green-500 text-white rounded text-sm">Completar</button>
                    </div>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">Mostrando página {{ page }} de {{ totalPages }}</div>
            <div class="flex items-center space-x-2">
            <button @click="setPage(page - 1)" :disabled="page <= 1" class="px-3 py-1 bg-gray-100 rounded">Anterior</button>
            <template v-for="p in totalPages" :key="p">
                <button @click="setPage(p)" :class="['px-3 py-1 rounded', page === p ? 'bg-blue-600 text-white' : 'bg-gray-50']">{{ p }}</button>
            </template>
            <button @click="setPage(page + 1)" :disabled="page >= totalPages" class="px-3 py-1 bg-gray-100 rounded">Siguiente</button>
            </div>
        </div>

        <!-- Modales: confirm, alert, success -->
        <div v-if="showConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b">
                <h3 class="font-bold">{{ confirmTitle }}</h3>
            </div>
            <div class="p-6">
                <p>{{ confirmText }}</p>
            </div>
            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button @click="showConfirm = false" class="px-4 py-2 bg-gray-200 rounded">Cancelar</button>
                <button @click="performConfirm" class="px-4 py-2 bg-red-600 text-white rounded">Confirmar</button>
            </div>
            </div>
        </div>
        <div v-if="showAlert" class="fixed inset-0 bg-black/30 flex items-center justify-center z-40">
            <div class="bg-white rounded-lg shadow p-6 max-w-sm">
            <h4 class="font-bold mb-2">Error</h4>
            <p class="mb-4">{{ alertMessage }}</p>
            <div class="text-right">
                <button @click="closeAlert" class="px-4 py-2 bg-gray-200 rounded">Cerrar</button>
            </div>
            </div>
        </div>
        <div v-if="showSuccess" class="fixed inset-0 bg-black/30 flex items-center justify-center z-40">
            <div class="bg-white rounded-lg shadow p-6 max-w-sm">
            <h4 class="font-bold mb-2">Éxito</h4>
            <p class="mb-4">{{ successMessage }}</p>
            <div class="text-right">
                <button @click="closeSuccess" class="px-4 py-2 bg-blue-600 text-white rounded">Aceptar</button>
            </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
