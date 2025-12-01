<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import * as XLSX from 'xlsx'
import { saveAs } from 'file-saver'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

    // ✅ Recibir props desde Laravel
    const props = defineProps({
    doctors: { type: Array, default: () => [] },
    specialties: { type: Array, default: () => [] },
    })

    // ✅ Leer mensajes flash (opcional)
    const page = usePage()
    const flashMessage = ref('')
    const flashType = ref('success')
    const showFlashModal = ref(false)

    onMounted(() => {
    const flash = page.props.flash || {}
    if (flash.success || flash.error) {
        flashMessage.value = flash.success ?? flash.error
        flashType.value = flash.success ? 'success' : 'error'
        showFlashModal.value = true
        setTimeout(() => (showFlashModal.value = false), 3000)
    }
    })

    // ---------- CRUD MODALS ----------
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const showDeleteModal = ref(false)
    const selectedDoctor = ref(null)

    const newDoctor = ref({
    name: '',
    document: '',
    id_specialty: '',
    status: 'Activo',
    })

    const closeAllModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    showDeleteModal.value = false
    selectedDoctor.value = null
    Object.assign(newDoctor.value, { name: '', document: '', id_specialty: '', status: 'Activo' })
    }

    // ---------- ACCIONES ----------
    const createDoctor = () => {
    const payload = { ...newDoctor.value, id_specialty: Number(newDoctor.value.id_specialty) }
    router.post(route('doctors.store'), payload, {
        onSuccess: () => {
        closeAllModals()
        showResponse('success', 'Médico creado correctamente.')
        },
        onError: (errors) => showResponse('error', Object.values(errors).flat().join('\n')),
    })
    }

    const editDoctor = (doctor) => {
    selectedDoctor.value = { ...doctor }
    showEditModal.value = true
    }

    const confirmDelete = (doctor) => {
    selectedDoctor.value = { ...doctor }
    showDeleteModal.value = true
    }

    const updateDoctor = () => {
    // Use slug for route binding if available (routes are scoped by slug); fall back to id
    const doctorParam = selectedDoctor.value.slug ?? selectedDoctor.value.id_doctor
    router.put(route('doctors.update', doctorParam), selectedDoctor.value, {
        onSuccess: () => {
        closeAllModals()
        showResponse('success', 'Médico actualizado correctamente.')
        },
        onError: (errors) => showResponse('error', Object.values(errors).flat().join('\n')),
    })
    }

    const deleteDoctor = () => {
    router.delete(route('doctors.destroy', selectedDoctor.value.id_doctor), {
        onSuccess: () => {
        closeAllModals()
        showResponse('success', 'Médico eliminado correctamente.')
        },
        onError: (errors) => showResponse('error', Object.values(errors).flat().join('\n')),
    })
    }

    // Response modal state and helpers (same UX as Patients page)
    const showResponseModal = ref(false)
    const responseType = ref('success')
    const responseMessage = ref('')

    const showResponse = (type, message) => {
    responseType.value = type
    responseMessage.value = message
    showResponseModal.value = true
    }

    const hideResponse = () => {
    showResponseModal.value = false
    responseMessage.value = ''
    responseType.value = 'success'
    }
    </script>

    <template>
    <AppLayout title="Gestión de Médicos">
        <Head title="Médicos" />

        <!-- Flash modal -->
        <transition name="fade">
        <div v-if="showFlashModal" class="fixed inset-0 flex items-center justify-center bg-black/40 z-50">
            <div
            :class="['bg-white dark:bg-gray-800 px-8 py-5 rounded-xl shadow-xl text-center border',
                    flashType === 'success' ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600']">
            <p class="text-lg font-semibold">{{ flashMessage }}</p>
            </div>
        </div>
        </transition>

        <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-700">Gestión de Médicos</h1>
            <button @click="showCreateModal = true"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">➕ Nuevo Médico</button>
        </div>

        <div class="bg-white shadow rounded-lg p-4">

            <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                <th class="px-4 py-2 text-left">ID</th>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Documento</th>
                <th class="px-4 py-2 text-left">Especialidad</th>
                <th class="px-4 py-2 text-left">Estado</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="d in props.doctors" :key="d.id_doctor">
                <td class="px-4 py-2">{{ d.id_doctor }}</td>
                <td class="px-4 py-2">{{ d.name }}</td>
                <td class="px-4 py-2">{{ d.document }}</td>
                <td class="px-4 py-2">
                    {{ props.specialties.find(s => s.id_specialty === d.id_specialty)?.name || '—' }}
                </td>
                <td class="px-4 py-2">{{ d.status }}</td>
                <td class="px-4 py-2">{{ d.email }}</td>
                <td class="px-4 py-2 text-center space-x-2">
                    <button @click="editDoctor(d)" class="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1 rounded">✏️</button>
                        <button @click="confirmDelete(d)"
                            class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">🗑️</button>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>

        <!-- Crear -->
        <div v-if="showCreateModal" @click.self="closeAllModals"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Nuevo Médico</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
            <input v-model="newDoctor.name" placeholder="Nombre completo" class="w-full border rounded p-2" />
            <input v-model="newDoctor.document" placeholder="Documento" class="w-full border rounded p-2" />
            <select v-model="newDoctor.id_specialty" class="w-full border rounded p-2">
                <option value="">Seleccione especialidad</option>
                <option v-for="s in props.specialties" :key="s.id_specialty" :value="s.id_specialty">{{ s.name }}</option>
            </select>
            <select v-model="newDoctor.status" class="w-full border rounded p-2">
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="createDoctor" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar</button>
            </div>
        </div>
        </div>

        <!-- Editar -->
        <div v-if="showEditModal" @click.self="closeAllModals"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Editar Médico</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
            <input v-model="selectedDoctor.name" placeholder="Nombre completo" class="w-full border rounded p-2" />
            <input v-model="selectedDoctor.document" placeholder="Documento" class="w-full border rounded p-2" />
            <select v-model="selectedDoctor.id_specialty" class="w-full border rounded p-2">
                <option value="">Seleccione especialidad</option>
                <option v-for="s in props.specialties" :key="s.id_specialty" :value="s.id_specialty">{{ s.name }}</option>
            </select>
            <select v-model="selectedDoctor.status" class="w-full border rounded p-2">
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
            <input v-model="selectedDoctor.email" placeholder="Email" class="w-full border rounded p-2" />
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="updateDoctor" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar cambios</button>
            </div>
        </div>
        </div>

        <!-- Eliminar -->
        <div v-if="showDeleteModal" @click.self="closeAllModals"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Eliminar Médico</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6">
            <p>¿Estás seguro de que deseas eliminar al médico <strong>{{ selectedDoctor?.name }}</strong>?</p>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="deleteDoctor" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
            </div>
        </div>
        </div>
            <!-- 🔹 Response Modal (success / error) -->
            <div v-if="showResponseModal" @click.self="hideResponse" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-bold" :class="responseType === 'success' ? 'text-green-600' : 'text-red-600'">{{ responseType === 'success' ? 'Éxito' : 'Error' }}</h2>
                <button @click="hideResponse" class="text-gray-400 hover:text-gray-600">✖</button>
                </div>
                <div class="p-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ responseMessage }}</pre>
                </div>
                <div class="px-6 py-4 border-t flex justify-end">
                <button @click="hideResponse" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Cerrar</button>
                </div>
            </div>
            </div>
    </AppLayout>
    </template>
