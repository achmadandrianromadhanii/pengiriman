<script setup>
    defineProps({
        title: { type: String, default: 'Data kosong' },
        desc: { type: String, default: 'Belum ada data untuk ditampilkan.' },
        icon: { type: String, default: 'bi bi-inbox' },
    });
</script>

<template>
    <div class="card text-center">
        <div
            class="mx-auto h-12 w-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center"
        >
            <i :class="icon" class="text-xl"></i>
        </div>
        <div class="mt-3 font-heading text-gray-900 dark:text-gray-100">{{ title }}</div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ desc }}</div>
        <div class="mt-4">
            <slot />
        </div>
    </div>
</template>
