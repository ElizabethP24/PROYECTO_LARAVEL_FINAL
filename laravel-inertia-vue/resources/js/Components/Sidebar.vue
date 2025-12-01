<script setup>
import { Sidebar, SidebarContent, SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuItem, SidebarMenuButton } from "@/components/ui/sidebar"
import { Laptop, Gauge, IdCard, ShoppingCart, Monitor } from "lucide-vue-next"
import { ref } from "vue"

const links = ref([
    { label: "Dashboard", icon: Gauge, href: "/dashboard" },
    { label: "Empleados", icon: IdCard, href: "/empleados" },
    { label: "Ventas", icon: ShoppingCart, href: "/ventas" },
    { label: "Computadores", icon: Monitor, href: "/computadores" },
    ])
</script>

<template>
    <Sidebar class="bg-primary text-white min-h-screen">
        <SidebarContent>
        <SidebarGroup>
            <SidebarGroupLabel>
            <div class="flex items-center justify-center space-x-2 text-lg font-semibold">
                <Laptop class="w-5 h-5" />
                <span>App Computadores</span>
            </div>
            </SidebarGroupLabel>

            <SidebarMenu>
            <SidebarMenuItem v-for="link in links" :key="link.label">
                <SidebarMenuButton as-child>
                <a :href="link.href" class="flex items-center gap-2 hover:bg-white/10 px-3 py-2 rounded-md">
                    <component :is="link.icon" class="w-4 h-4" />
                    <span>{{ link.label }}</span>
                </a>
                </SidebarMenuButton>
            </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
        </SidebarContent>
    </Sidebar>
    </template>

<style scoped>
    .bg-primary {
    background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
    }
</style>
