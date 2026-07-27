<template>
    <button
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-elite-secondary to-orange-700
               px-6 py-3 text-xs font-bold uppercase tracking-widest text-black
               shadow-lg shadow-elite-secondary/20 transition-all duration-200
               hover:shadow-xl hover:shadow-elite-secondary/30 hover:brightness-110
               focus:outline-none focus:ring-2 focus:ring-elite-secondary focus:ring-offset-2 focus:ring-offset-elite-bg
               active:brightness-90 disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <slot />
    </button>
</template>
