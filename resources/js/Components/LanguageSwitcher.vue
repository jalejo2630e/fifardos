<script setup>
import { useI18n } from 'vue-i18n';
import { setLocale } from '@/i18n';

const { locale } = useI18n();
</script>

<template>
    <div class="fd-lang" role="group" aria-label="Idioma">
        <button type="button" :class="{ active: locale === 'es' }" @click="setLocale('es')" aria-label="Español">ES</button>
        <span class="sep">/</span>
        <button type="button" :class="{ active: locale === 'en' }" @click="setLocale('en')" aria-label="English">EN</button>
    </div>
</template>

<style scoped>
.fd-lang { display: inline-flex; align-items: center; gap: 4px; font-family: 'Barlow Condensed', sans-serif; font-weight: 700; letter-spacing: .06em; }
.fd-lang button { background: none; border: none; cursor: pointer; color: rgba(255, 255, 255, .4); font: inherit; font-size: 13px; padding: 4px 5px; transition: color .15s ease; }
.fd-lang button:hover { color: rgba(255, 255, 255, .8); }
.fd-lang button.active { color: #ff5f00; }
.fd-lang .sep { color: rgba(255, 255, 255, .2); font-size: 12px; }
</style>
