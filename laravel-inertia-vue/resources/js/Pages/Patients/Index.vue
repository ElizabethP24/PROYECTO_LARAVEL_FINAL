<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import * as XLSX from 'xlsx'
import { saveAs } from 'file-saver'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const page = usePage()
const flashMessage = ref('')
const flashType = ref('success')
const showFlashModal = ref(false)
const exportToPDF = () => {
    const doc = new jsPDF()
    doc.text('Listado de Pacientes', 14, 10)

    const patientsList = (page.props && page.props.patients) ? page.props.patients : []
    const tableData = patientsList.map(p => [
        p.id_patient,
        p.name,
        p.document,
        p.email,
        p.eps
    ])

    autoTable(doc, {
        head: [['ID', 'Nombre', 'Documento', 'Correo', 'EPS']],
        body: tableData,
        startY: 20,
        styles: { fontSize: 8 }
    })

    doc.save('pacientes.pdf')
}

    onMounted(() => {
    const flash = page.props.flash || {}
    if (flash && (flash.success || flash.error)) {
        flashMessage.value = flash.success ?? flash.error
        flashType.value = flash.success ? 'success' : 'error'
        showFlashModal.value = true
        setTimeout(() => (showFlashModal.value = false), 3000)
    }
    })

    const props = defineProps({
    patients: Array
    })

    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const showDeleteModal = ref(false)
    const selectedPatient = ref(null)

    const closeAllModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    showDeleteModal.value = false
    selectedPatient.value = null
    Object.assign(newPatient.value, { name: '', document: '', email: '', eps: '' })
    }

    const onKeyDown = (e) => {
    if (e.key === 'Escape') closeAllModals()
    }
    onMounted(() => window.addEventListener('keydown', onKeyDown))
    onBeforeUnmount(() => window.removeEventListener('keydown', onKeyDown))

    const newPatient = ref({
    name: '',
    document: '',
    email: '',
    eps: ''
    })

    const epsOptions = ['SURA', 'Nueva EPS', 'Salud Total', 'Sanitas', 'Otra']

    const createPatient = () => {
    router.post(route('patients.store'), newPatient.value, {
            onSuccess: () => {
                // Close create modal and show success modal
                showCreateModal.value = false
                showResponse('success', 'Paciente creado correctamente.')
                // reset form
                Object.assign(newPatient.value, { name: '', document: '', email: '', eps: '' })
            },
            onError: (errors) => {
                // errors is an object of validation messages
                const msgs = Object.values(errors).flat().join('\n')
                showResponse('error', msgs || 'Error al crear paciente.')
            }
    })
    }

    const editPatient = (patient) => {
    selectedPatient.value = { ...patient }
    showEditModal.value = true
    }

    const updatePatient = () => {
    router.put(route('patients.update', selectedPatient.value.id_patient), selectedPatient.value, {
            onSuccess: () => {
                showEditModal.value = false
                showResponse('success', 'Paciente actualizado correctamente.')
            },
            onError: (errors) => {
                const msgs = Object.values(errors).flat().join('\n')
                showResponse('error', msgs || 'Error al actualizar paciente.')
            }
    })
    }

    const deletePatient = () => {
    router.delete(route('patients.destroy', selectedPatient.value.id_patient), {
            onSuccess: () => {
                showDeleteModal.value = false
                showResponse('success', 'Paciente eliminado correctamente.')
            },
            onError: (errors) => {
                const msgs = Object.values(errors).flat().join('\n')
                showResponse('error', msgs || 'Error al eliminar paciente.')
            }
    })
    }

    // server-side export removed: using client-side jsPDF (exportToPDF)

        // Response modal state and helpers
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
    <AppLayout title="Gestión de Pacientes">
        <Head title="Pacientes" />

        <!-- ✅ MODAL DE NOTIFICACIÓN FLASH -->
        <transition name="fade">
        <div
            v-if="showFlashModal"
            class="fixed inset-0 flex items-center justify-center bg-black/40 z-50"
        >
            <div :class="['bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-8 py-5 rounded-xl shadow-xl text-center transform transition-all scale-100', flashType === 'success' ? 'border border-green-500' : 'border border-red-500']">
            <div class="flex flex-col items-center space-y-3">
                <div :class="flashType === 'success' ? 'text-green-500 text-3xl' : 'text-red-500 text-3xl'">{{ flashType === 'success' ? '✅' : '❌' }}</div>
                <p class="text-lg font-semibold">{{ flashMessage }}</p>
            </div>
            </div>
        </div>
        </transition>

        <!-- Contenido principal -->
        <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-700">Gestión de Pacientes</h1>
            <button
            @click="showCreateModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
            >
            <i class="fa fa-user-plus mr-2"></i> Nuevo Paciente
            </button>
        </div>

        <!-- Tabla -->
        <div class="bg-white shadow rounded-lg p-4">
            <div id="listaPacientes" class="card shadow mt-4"></div>
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Listado de Pacientes</h6>
                </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                <th class="px-4 py-2 text-left">Código</th>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Documento</th>

                <th class="px-4 py-2 text-left">Correo</th>
                <th class="px-4 py-2 text-left">EPS</th>
                <th class="px-4 py-2 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="p in patients" :key="p.id_patient">
                <td class="px-4 py-2">{{ p.id_patient }}</td>
                <td class="px-4 py-2">{{ p.name }}</td>
                <td class="px-4 py-2">{{ p.document }}</td>

                <td class="px-4 py-2">{{ p.email }}</td>
                <td class="px-4 py-2">{{ p.eps }}</td>
                <td class="px-4 py-2 text-center space-x-2">
                    <button
                    @click="editPatient(p)"
                    class="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1 rounded"
                    >
                    <i class="fa fa-edit"></i>
                    </button>
                    <button
                    @click="(selectedPatient = p, showDeleteModal = true)"
                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded"
                    >
                    <i class="fa fa-trash"></i>
                    </button>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>

        <!-- Modales CRUD -->
        <!-- Crear -->
        <div
        v-if="showCreateModal"
        @click.self="closeAllModals"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        >
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Nuevo Paciente</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
            <input v-model="newPatient.name" type="text" placeholder="Nombre completo" class="w-full border rounded p-2" />
            <input v-model="newPatient.document" type="text" placeholder="Documento" class="w-full border rounded p-2" />
            <input v-model="newPatient.email" type="email" placeholder="Correo" class="w-full border rounded p-2" />
            <select v-model="newPatient.eps" class="w-full border rounded p-2">
                <option value="">Seleccione EPS</option>
                <option v-for="opt in epsOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="createPatient" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar</button>
            </div>
        </div>
        </div>

        <!-- Editar -->
        <div
        v-if="showEditModal"
        @click.self="closeAllModals"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        >
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Editar Paciente</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6 space-y-3">
            <input v-model="selectedPatient.name" type="text" class="w-full border rounded p-2" />
            <input v-model="selectedPatient.document" type="text" class="w-full border rounded p-2" />
            <input v-model="selectedPatient.email" type="email" class="w-full border rounded p-2" />
            <select v-model="selectedPatient.eps" class="w-full border rounded p-2">
                <option v-for="opt in epsOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="updatePatient" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Actualizar</button>
            </div>
        </div>
        </div>

        <!-- Eliminar -->
        <div
        v-if="showDeleteModal"
        @click.self="closeAllModals"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        >
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold text-red-600">Confirmar eliminación</h2>
            <button @click="closeAllModals" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-6">
            <p>¿Seguro que deseas eliminar al paciente <strong>{{ selectedPatient && selectedPatient.name }}</strong>?</p>
            <p class="text-sm text-gray-500 mt-2">Esta acción no se puede deshacer.</p>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
            <button @click="closeAllModals" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
            <button @click="deletePatient" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
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
