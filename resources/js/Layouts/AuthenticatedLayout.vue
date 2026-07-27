<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gaming-dark">
        <!-- Nav -->
        <nav class="border-b border-white/5 bg-gaming-card/60 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <!-- Left -->
                    <div class="flex items-center gap-8">
                        <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gaming-cyan to-gaming-purple flex items-center justify-center text-lg font-black text-black">
                                F
                            </div>
                            <span class="font-display font-bold text-white hidden sm:block">FIFA Torneo</span>
                        </Link>

                        <div class="hidden sm:flex items-center gap-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </NavLink>
                            <NavLink :href="route('tournaments.create')" :active="route().current('tournaments.create')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nuevo Torneo</span>
                            </NavLink>
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-3">
                            <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg bg-white/5">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-gaming-cyan to-gaming-purple flex items-center justify-center text-xs font-black text-black">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-sm text-white/70 font-medium">{{ $page.props.auth.user.name }}</span>
                            </div>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-white/50 hover:text-white hover:bg-white/5 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Perfil
                                        </div>
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        <div class="flex items-center gap-2 text-gaming-red">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Cerrar Sesión
                                        </div>
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger mobile -->
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="sm:hidden p-2 rounded-lg text-white/50 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{
                                    hidden: showingNavigationDropdown,
                                    'inline-flex': !showingNavigationDropdown,
                                }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{
                                    hidden: !showingNavigationDropdown,
                                    'inline-flex': showingNavigationDropdown,
                                }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-t border-white/5">
                <div class="space-y-1 px-4 py-3">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </div>
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('tournaments.create')" :active="route().current('tournaments.create')">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo Torneo
                        </div>
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-white/5 px-4 py-3">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gaming-cyan to-gaming-purple flex items-center justify-center text-sm font-black text-black">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $page.props.auth.user.name }}</div>
                            <div class="text-xs text-white/40">{{ $page.props.auth.user.email }}</div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Perfil
                            </div>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            <div class="flex items-center gap-2 text-gaming-red">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Cerrar Sesión
                            </div>
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Header -->
        <header v-if="$slots.header" class="border-b border-white/5 bg-gaming-card/40">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
