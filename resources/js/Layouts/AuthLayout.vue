<script setup>
    import { Head } from '@inertiajs/vue3';

    defineProps({
        title: { type: String, default: 'Login' },
    });
</script>

<template>
    <div
        class="min-h-screen relative overflow-hidden bg-white dark:bg-[#0E1117] transition-colors duration-200"
    >
        <Head :title="title" />

        <!-- Light: dot-pattern indigo -->
        <div
            class="absolute inset-0 dark:hidden opacity-70"
            style="
                background-image: radial-gradient(rgba(99, 102, 241, 0.22) 1px, transparent 1px);
                background-size: 18px 18px;
            "
            aria-hidden="true"
        />

        <!-- Dark: diagonal line pattern -->
        <div
            class="absolute inset-0 hidden dark:block opacity-25"
            style="
                background-image: repeating-linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.06) 0px,
                    rgba(255, 255, 255, 0.06) 1px,
                    transparent 1px,
                    transparent 12px
                );
            "
            aria-hidden="true"
        />

        <main class="relative z-10 min-h-screen flex items-center justify-center px-4 py-0 md:py-12">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>
    </div>
</template>
