<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ChatBot from '@/Components/ChatBot.vue';
import { Link } from '@inertiajs/vue3';

const showingNav = ref(false);
</script>

<template>
    <div class="min-h-screen bg-elite-bg relative overflow-hidden">
        <!-- Dynamic Background -->
        <div class="fixed inset-0 pointer-events-none z-0">
            <div class="absolute inset-0 bg-gradient-to-br from-elite-bg via-elite-bg to-purple-950/10 animate-gradient-shift" />
            <div class="absolute top-0 -left-1/4 w-1/2 h-1/2 bg-elite-secondary/5 rounded-full blur-[120px] animate-float-slow" />
            <div class="absolute bottom-0 -right-1/4 w-1/2 h-1/2 bg-purple-600/5 rounded-full blur-[120px] animate-float-slow-reverse" />
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-white/[0.02] to-transparent" />
        </div>

        <!-- Content -->
        <div class="relative z-10">
        <!-- Header -->
        <header class="sticky top-0 z-40 border-b border-white/5 bg-elite-bg/80 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 sm:h-16 items-center justify-between">
                    <!-- Logo + Desktop Nav -->
                    <div class="flex items-center gap-6 sm:gap-10">
                        <Link :href="route('dashboard')" class="flex items-center gap-2.5 shrink-0">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-elite-secondary to-orange-700
                                        flex items-center justify-center text-black shadow-lg shadow-elite-secondary/20">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="3"/>
                                    <circle cx="8" cy="12" r="1.5"/>
                                    <circle cx="16" cy="12" r="1.5"/>
                                </svg>
                            </div>
                            <span class="hidden sm:block font-condensed font-bold text-base tracking-wider text-white/90">
                                FIFARDOS
                            </span>
                        </Link>

                        <!-- Desktop Nav -->
                        <nav class="hidden md:flex items-center gap-1">
                            <Link :href="route('dashboard')"
                                  class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                  :class="route().current('dashboard') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/50 hover:text-white hover:bg-white/5'">
                                DASHBOARD
                            </Link>
                            <Link :href="route('tournaments.index')"
                                  class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                  :class="route().current('tournaments.index*') || route().current('tournaments.show*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/50 hover:text-white hover:bg-white/5'">
                                TORNEOS
                            </Link>
                            <Link :href="route('tournaments.create')"
                                  class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                  :class="route().current('tournaments.create*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/50 hover:text-white hover:bg-white/5'">
                                NUEVO
                            </Link>
                            <Link v-if="$page.props.auth.user?.is_admin"
                                  :href="route('dashboard.api-docs')"
                                  class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                  :class="route().current('dashboard.api-docs') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/50 hover:text-white hover:bg-white/5'">
                                CÓMO USAR
                            </Link>
                            <Link v-if="$page.props.auth.user?.is_admin"
                                  :href="route('admin.chat-config.edit')"
                                  class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                  :class="route().current('admin.chat-config.*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/50 hover:text-white hover:bg-white/5'">
                                CHATBOT
                            </Link>
                        </nav>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Desktop User -->
                        <div class="hidden sm:flex items-center gap-3">
                            <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg bg-white/5">
                                <div class="w-7 h-7 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-xs overflow-hidden flex-shrink-0">
                                    <img v-if="$page.props.auth.user.avatar_url && $page.props.auth.user.avatar_url.startsWith('http')" :src="$page.props.auth.user.avatar_url" class="w-full h-full object-cover" />
                                    <span v-else-if="$page.props.auth.user.avatar" class="text-sm">{{ $page.props.auth.user.avatar }}</span>
                                    <span v-else class="text-xs font-bold text-black bg-gradient-to-br from-elite-secondary to-orange-700 w-full h-full flex items-center justify-center">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
                                </div>
                                <span class="text-sm text-white/60 font-medium max-w-[100px] truncate">{{ $page.props.auth.user.name }}</span>
                            </div>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="min-h-touch min-w-touch flex items-center justify-center rounded-lg text-white/40 hover:text-white hover:bg-white/5 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        <div class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Perfil
                                        </div>
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        <div class="flex items-center gap-2.5 text-red-400">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Cerrar Sesión
                                        </div>
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger Mobile -->
                        <button @click="showingNav = !showingNav"
                                class="md:hidden min-h-touch min-w-touch flex items-center justify-center rounded-lg text-white/50 hover:text-white hover:bg-white/5 transition-all">
                            <svg class="w-5 h-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNav, 'inline-flex': !showingNav }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNav, 'inline-flex': showingNav }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Nav -->
            <div :class="{ block: showingNav, hidden: !showingNav }" class="md:hidden border-t border-white/5">
                <div class="px-4 py-3 space-y-1">
                    <Link :href="route('dashboard')"
                          class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-all"
                          :class="route().current('dashboard') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                          @click="showingNav = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </Link>
                    <Link :href="route('tournaments.index')"
                          class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-all"
                          :class="route().current('tournaments.index*') || route().current('tournaments.show*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                          @click="showingNav = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Mis Torneos
                    </Link>
                    <Link :href="route('tournaments.create')"
                          class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-all"
                          :class="route().current('tournaments.create*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                          @click="showingNav = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Torneo
                    </Link>
                    <Link v-if="$page.props.auth.user?.is_admin"
                          :href="route('dashboard.api-docs')"
                          class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-all"
                          :class="route().current('dashboard.api-docs') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                          @click="showingNav = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Cómo Usar
                    </Link>
                    <Link v-if="$page.props.auth.user?.is_admin"
                          :href="route('admin.chat-config.edit')"
                          class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-all"
                          :class="route().current('admin.chat-config.*') ? 'text-elite-secondary bg-elite-secondary/10' : 'text-white/60 hover:text-white hover:bg-white/5'"
                          @click="showingNav = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        ChatBot
                    </Link>
                </div>
                <div class="border-t border-white/5 px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/10 border border-white/10 flex items-center justify-center text-sm overflow-hidden shrink-0">
                        <img v-if="$page.props.auth.user.avatar_url && $page.props.auth.user.avatar_url.startsWith('http')" :src="$page.props.auth.user.avatar_url" class="w-full h-full object-cover" />
                        <span v-else-if="$page.props.auth.user.avatar" class="text-base">{{ $page.props.auth.user.avatar }}</span>
                        <span v-else class="text-sm font-bold text-black bg-gradient-to-br from-elite-secondary to-orange-700 w-full h-full flex items-center justify-center">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-white truncate">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-white/40 truncate">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <Link :href="route('logout')" method="post" as="button"
                          class="min-h-touch min-w-touch flex items-center justify-center rounded-lg text-red-400 hover:bg-white/5 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <header v-if="$slots.header" class="border-b border-white/[0.02]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
                <slot name="header" />
            </div>
        </header>

        <!-- Content -->
        <main>
            <slot />
        </main>

        <!-- FAB Mobile: Nuevo Torneo -->
        <Link :href="route('tournaments.create')"
              class="ucl-fab sm:hidden"
              :class="{ hidden: route().current('tournaments.create*') }">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </Link>
            <ChatBot />
        </div>
    </div>
</template>
