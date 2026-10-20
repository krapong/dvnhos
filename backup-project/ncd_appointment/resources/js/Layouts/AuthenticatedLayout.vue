<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showingNavigationDropdown = ref(false);
const isSidebarCollapsed = ref(false);
const isDark = ref(false);

onMounted(() => {
    // Check if dark mode is preferred or saved in local storage
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
    
    // Check sidebar state from local storage
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        isSidebarCollapsed.value = true;
    }
});

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed.value);
};

const handleItemClick = (e, item) => {
    if (item.underDevelopment) {
        e.preventDefault();
        alert('กำลังพัฒนา');
    }
};

const menuGroups = [
    {
        name: '',
        items: [
            { name: 'Dashboard', route: 'appointments.daily', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', iconColor: 'text-blue-500' },
        ]
    },
    {
        name: 'การนัดหมาย',
        items: [
            { name: 'ตารางนัด', route: 'appointments.schedule', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', iconColor: 'text-indigo-500' },
            { name: 'ปฏิทินคลินิก', route: 'appointments.index', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', iconColor: 'text-emerald-500' },
        ]
    },
    {
        name: 'การให้บริการ',
        items: [
            { name: 'ติดตามสถานะบริการ', route: 'services.tracking', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', underDevelopment: true, iconColor: 'text-amber-500' },
            { name: 'ผล Lab', route: 'services.labs', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', underDevelopment: true, iconColor: 'text-cyan-500' },
        ]
    },

    {
        name: 'รายงาน',
        items: [
            { name: 'รายงาน', route: 'reports.index', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', iconColor: 'text-pink-500' },
        ]
    },
    {
        name: 'สถิติคลินิก NCD',
        requirePermission: 'manage ncd stats',
        items: [
            { name: 'บันทึกข้อมูลสถิติ', route: 'ncd-stats.data-entry', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', iconColor: 'text-orange-500' },
            { name: 'สรุปภาพรวม', route: 'ncd-stats.summary', icon: 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', iconColor: 'text-blue-400' },
        ]
    },
    {
        name: 'จัดการระบบคลินิก',
        adminOnly: true,
        items: [
            { name: 'จัดการคลินิก', route: 'clinics.index', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', iconColor: 'text-violet-500' },
            { name: 'วันหยุด', route: 'clinics.holidays', icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', iconColor: 'text-sky-500' },
            { name: 'ความจุของคลินิก', route: 'clinics.capacity', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', iconColor: 'text-indigo-600' },
        ]
    },
    {
        name: 'ผู้ดูแลระบบ',
        adminOnly: true,
        items: [
            { name: 'HOSxP Sync', route: 'admin.hosxp-sync', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', iconColor: 'text-cyan-600' },
            { name: 'ผู้ใช้งาน', route: 'admin.users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', iconColor: 'text-lime-500' },
            { name: 'สิทธิ์', route: 'admin.roles', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', iconColor: 'text-emerald-600' },
            { name: 'ตั้งค่าระบบ', route: 'admin.settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', iconColor: 'text-gray-500' },
            { name: 'Audit Log', route: 'admin.audit-log', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', iconColor: 'text-slate-400' },
        ]
    }
];

const hasAccess = (group, user, permissions) => {
    const perms = Array.isArray(permissions) ? permissions : Object.values(permissions || {});
    if (group.adminOnly && !perms.includes('manage users') && !perms.includes('manage settings')) {
        return false;
    }
    if (group.requirePermission && !perms.includes(group.requirePermission) && !perms.includes('manage settings')) {
        return false;
    }
    return true;
};
</script>

<template>
    <div class="min-h-screen flex bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans transition-colors duration-200">
        
        <!-- Sidebar for Desktop -->
        <aside :class="[
            isSidebarCollapsed ? 'w-20' : 'w-64', 
            'hidden md:flex flex-col bg-[#0e448b] border-r border-[#0e448b] min-h-screen fixed z-20 transition-all duration-300'
        ]">
            <div class="h-16 flex items-center justify-between px-4 border-b border-white/10">
                <span v-if="!isSidebarCollapsed" class="text-base font-extrabold text-white leading-tight transition-all duration-300">ระบบ Monitor ผู้ป่วยผิดนัด<br>Clinic NCD</span>
                <span v-else class="text-xl font-extrabold text-white mx-auto">NCD</span>
            </div>
            
            <div class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-6 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <template v-for="(group, idx) in menuGroups" :key="idx">
                    <div v-if="hasAccess(group, $page.props.auth.user, $page.props.auth.permissions)">
                        <h3 v-if="group.name && !isSidebarCollapsed" class="px-5 text-xs font-semibold text-white/60 uppercase tracking-wider mb-2 mt-4 whitespace-nowrap">
                            {{ group.name }}
                        </h3>
                        <div class="border-t border-white/10 my-2" v-else></div>
                        <div class="space-y-1 px-3">
                            <Link v-for="item in group.items" :key="item.route" 
                                :href="item.underDevelopment ? '#' : route(item.route)"
                                @click="handleItemClick($event, item)"
                                :title="isSidebarCollapsed ? item.name : ''"
                                :class="[
                                    (!item.underDevelopment && (route().current(item.route) || route().current(item.route + '.*')))
                                        ? 'bg-blue-600 text-white shadow-md'
                                        : 'text-white/70 hover:bg-white/10 hover:text-white',
                                    'group flex items-center py-2.5 text-sm font-medium rounded-xl transition-all duration-300 mx-3',
                                    isSidebarCollapsed ? 'px-3 justify-center' : 'px-4'
                                ]">
                                <svg :class="[
                                        (!item.underDevelopment && (route().current(item.route) || route().current(item.route + '.*')))
                                            ? 'text-white'
                                            : 'text-white/70 group-hover:text-white',
                                        'flex-shrink-0 h-5 w-5 transition-all duration-300 transform group-hover:scale-110',
                                        isSidebarCollapsed ? '' : 'mr-3'
                                    ]" 
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                <span v-if="!isSidebarCollapsed" class="whitespace-nowrap">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Sidebar Footer (Collapse Toggle) -->
            <div class="border-t border-white/10 p-4">
                <button @click="toggleSidebar" class="w-full flex items-center justify-center text-white/70 hover:text-white transition-colors">
                    <svg v-if="!isSidebarCollapsed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div :class="[
            isSidebarCollapsed ? 'md:ml-20' : 'md:ml-64',
            'flex-1 flex flex-col min-h-screen transition-all duration-300'
        ]">
            <!-- Top Navbar (Sticky) -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 min-h-16 flex items-center justify-between px-4 py-2 sm:px-6 lg:px-8 sticky top-0 z-10 transition-colors duration-200 shadow-sm">
                <div class="flex items-center">
                    <!-- Desktop sidebar toggle button -->
                    <button @click="toggleSidebar" class="hidden md:inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors mr-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <!-- Mobile menu button -->
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500 mr-2">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <!-- Header slot usually goes here, but we will place it below navbar -->
                </div>

                <div v-if="$slots.header" class="min-w-0 flex-1 px-3">
                    <slot name="header" />
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg v-if="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-white focus:outline-none transition ease-in-out duration-150">
                                    {{ $page.props.auth.user.name }}
                                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        <template #content>
                            <div class="bg-white dark:bg-gray-700">
                                <DropdownLink :href="route('profile.edit')" class="dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">Log Out</DropdownLink>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </nav>

            <!-- Mobile menu -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="md:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
                <div class="px-2 pt-2 pb-3 space-y-4 max-h-[80vh] overflow-y-auto">
                    <template v-for="(group, idx) in menuGroups" :key="idx">
                        <div v-if="hasAccess(group, $page.props.auth.user)">
                            <div class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 mt-3">
                                {{ group.name }}
                            </div>
                            <Link v-for="item in group.items" :key="item.route" 
                                :href="item.underDevelopment ? '#' : route(item.route)"
                                @click="handleItemClick($event, item)"
                                :class="[
                                    (!item.underDevelopment && (route().current(item.route) || route().current(item.route + '.*')))
                                        ? 'bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-900/40 dark:to-emerald-900/20 text-teal-700 dark:text-teal-300 border-l-4 border-teal-500'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-800 hover:text-teal-600 dark:hover:text-teal-300 hover:shadow-md border-l-4 border-transparent hover:border-teal-300',
                                    'block px-3 py-2 rounded-r-xl rounded-l-sm text-base font-medium transition-all duration-300 transform hover:-translate-y-1'
                                ]">
                                {{ item.name }}
                            </Link>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>

            <footer class="border-t border-gray-200 bg-white px-4 py-6 dark:border-gray-700 dark:bg-gray-800 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 text-sm text-gray-500 dark:text-gray-400 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Clinic NCD Monitor</p>
                        <p class="mt-1 text-xs">ระบบติดตามการนัดหมายและการเข้ารับบริการผู้ป่วย NCD</p>
                    </div>
                    <div class="text-left md:text-right">
                        <p>พัฒนาโดย นายศุภชัย นะที</p>
                        <p class="mt-1 text-xs">นักวิชาการคอมพิวเตอร์</p>
                    </div>
                </div>
                <div class="mx-auto mt-4 max-w-7xl border-t border-gray-100 pt-3 text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500">
                    <p>© {{ new Date().getFullYear() }} Clinic NCD Monitor. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>
</template>
