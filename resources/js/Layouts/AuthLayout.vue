<!--
  ============================================================================
  AUTH LAYOUT — Layout wrapper untuk halaman autentikasi
  ============================================================================
  Fungsi   : Menyediakan layout dasar tanpa sidebar/navbar untuk halaman
             autentikasi (Login, Register, Forgot Password, dll).
  Cara Kerja :
    - Mobile  : Konten full-width tanpa padding/constraint (Login.vue mengatur
                layout sendiri secara penuh).
    - Desktop : Konten dibatasi max-w-lg dan di-center secara vertikal/horizontal.
  Catatan  : Background pattern sudah diurus oleh masing-masing halaman (Login.vue
             punya hero canvas sendiri), jadi layout ini hanya menyediakan
             background warna dasar.
  ============================================================================
-->
<script setup>
    import { Head } from '@inertiajs/vue3';

    defineProps({
        title: { type: String, default: 'Login' },
    });
</script>

<template>
    <!-- Container Utama — Background dasar untuk semua halaman auth -->
    <div class="min-h-screen relative overflow-hidden bg-white dark:bg-[#0E1117] transition-colors duration-200">
        <Head :title="title" />

        <!-- Desktop: dot-pattern background (Light Mode) -->
        <div
            class="absolute inset-0 hidden md:block dark:hidden opacity-70"
            style="
                background-image: radial-gradient(rgba(99, 102, 241, 0.22) 1px, transparent 1px);
                background-size: 18px 18px;
            "
            aria-hidden="true"
        />

        <!-- Desktop: diagonal line pattern (Dark Mode) -->
        <div
            class="absolute inset-0 hidden dark:md:block opacity-25"
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

        <!-- Konten Utama -->
        <!-- Mobile: Full-width tanpa constraint (Login.vue mengatur sendiri) -->
        <!-- Desktop: Centered dengan max-width -->
        <main class="relative z-10 min-h-screen flex items-center justify-center md:px-4 md:py-12">
            <div class="w-full md:max-w-lg">
                <slot />
            </div>
        </main>
    </div>
</template>
