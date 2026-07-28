<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ChatBot from '@/Components/ChatBot.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-elite-bg px-4 py-8 scanline grid-bg">
        <ChatBot />
        <div class="w-full max-w-sm">
            <Link href="/" class="flex justify-center mb-8">
                <ApplicationLogo />
            </Link>

            <div class="ucl-card p-6 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
