<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
    title: String
})
const showingProfileDropdown = ref(false)
const isDoctor = computed(() => {
    try {
        return Boolean($page && $page.props && $page.props.auth && $page.props.auth.user && $page.props.auth.user.role === 'doctor')
    } catch (e) {
        return false
    }
})
const logout = () => {
    try {
        const logoutUrl = (typeof route === 'function') ? route('logout') : '/logout'
        console.debug('[AppLayout] logging out to', logoutUrl)
        router.post(logoutUrl)
    } catch (e) {
        console.error('[AppLayout] logout failed, falling back to /logout', e)
        router.post('/logout')
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <div class="flex flex-col min-h-screen bg-gray-100">
        <header class="bg-white shadow-md border-b border-gray-200 h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-40">
            <div class="flex items-center space-x-3">
                <h1 class="text-lg font-semibold text-gray-800">Panel de control general</h1>
            </div>

            <div v-if="$page.props && $page.props.auth && $page.props.auth.user" class="relative">
                <button @click.stop="showingProfileDropdown = !showingProfileDropdown" class="flex items-center focus:outline-none text-gray-600 hover:text-gray-800">
                    <img
                        v-if="$page.props.jetstream && $page.props.jetstream.managesProfilePhotos"
                        class="h-9 w-9 rounded-full object-cover border border-gray-300"
                        :src="$page.props.auth.user.profile_photo_url"
                        :alt="$page.props.auth.user.name || 'Usuario'"
                    />
                    <span v-else class="ml-2 text-sm font-medium">{{ $page.props.auth.user.name }}</span>
                    <svg class="ml-2 w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    v-if="showingProfileDropdown"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                >
                    <div class="py-2">
                        <Link :href="route('profile.show')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Perfil
                        </Link>
                        <form @submit.prevent="logout" @click.stop>
                            <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Enlaces públicos si no hay usuario autenticado -->
            <div v-else class="flex items-center space-x-3 absolute right-6 top-4">
                <Link
                    :href="route('login')"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    Iniciar sesión
                </Link>
                <Link
                    :href="route('register')"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                    Registrarse
                </Link>
            </div>
        </header>

        <div class="flex flex-1 pt-16">
            <aside class="w-64 bg-slate-900 text-white flex flex-col fixed top-16 left-0 bottom-0">
                <div class="p-6 text-center border-b border-slate-700">
                    <div class="w-20 h-20 mx-auto rounded-full bg-slate-700 flex items-center justify-center text-2xl font-bold">
                        <span v-if="$page.props && $page.props.auth && $page.props.auth.user">{{ ($page.props.auth.user.name || 'U').split(' ').map(n => n[0]).slice(0,2).join('') }}</span>
                        <span v-else>CL</span>
                    </div>
                    <h2 class="mt-3 font-semibold text-lg">{{ $page.props && $page.props.auth && $page.props.auth.user ? $page.props.auth.user.name : 'Invitado' }}</h2>
                    <p class="text-sm text-gray-400">{{ $page.props && $page.props.auth && $page.props.auth.user ? $page.props.auth.user.email : 'invitado@local' }}</p>
                </div>

                <nav class="flex-1 px-4 py-6 space-y-2">
                    <template v-if="isDoctor">
                        <!-- Doctor users: only show the doctor's agenda link -->
                        <Link :href="route('doctors.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">🩺 Agenda Médico</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('dashboard')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">🏠 Panel de control</Link>
                        <Link :href="route('patients.index')" :active="route().current('patients.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">👤 Pacientes</Link>
                        <Link :href="route('doctors.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">💊 Médicos</Link>
                        <Link v-if="$page.props && $page.props.doctors && $page.props.doctors.length"
                            :href="route('doctors.agenda', { doctor: $page.props.doctors[0].slug })"
                            class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">🩺 Agenda Médico</Link>
                        <Link v-else :href="route('doctors.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">🩺 Agenda Médico</Link>
                        <Link :href="route('appointments.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">📋 Citas</Link>
                        <Link :href="route('agenda.index')" class="block py-2 px-3 rounded-md hover:bg-slate-800 transition">🗓️ Agenda</Link>
                    </template>
                </nav>
                <div class="p-4 border-t border-slate-700 text-center text-xs text-gray-400">
                    © 2025 Clínica Salud SAS
                </div>
            </aside>
            <main class="flex-1 ml-64 p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
