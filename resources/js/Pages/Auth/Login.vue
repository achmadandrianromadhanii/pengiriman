<script setup>
import { ref, onMounted } from 'vue';
import DesktopLogin from '@/Pages/Desktop/Auth/Login.vue';
import MobileLogin from '@/Pages/Mobile/Auth/Login.vue';

const props = defineProps({
    loginSuccess: { type: Boolean, default: false },
    biometric_active: { type: Boolean, default: false },
});

const isMobile = ref(false);

onMounted(() => {
    isMobile.value =
        window.innerWidth < 1024 ||
        /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth < 1024;
    });
});
</script>

<template>
    <div class="w-full min-h-screen">
        <!-- Tampilan Desktop (Split-Screen dengan Logistics Map) -->
        <div class="hidden lg:block">
            <DesktopLogin v-bind="props" />
        </div>

        <!-- Tampilan Mobile (Optimized Mobile Login) -->
        <div class="lg:hidden">
            <MobileLogin v-bind="props" />
        </div>
    </div>
</template>
