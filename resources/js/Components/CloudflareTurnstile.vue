<template>
    <div class="turnstile-container flex flex-col items-center justify-center my-3 w-full">
        <!-- Status Loading Mini saat script Cloudflare menginisialisasi -->
        <div
            v-if="isLoading"
            class="flex items-center gap-2 py-2 px-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200/80 dark:border-slate-800 animate-pulse"
        >
            <svg class="w-3.5 h-3.5 animate-spin text-amber-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-[11px] font-medium">Memuat verifikasi Cloudflare...</span>
        </div>

        <!-- Wadah Widget Turnstile Asli -->
        <div ref="widgetContainer" class="cf-turnstile-slot"></div>

        <!-- Pesan status kecil -->
        <p v-if="verified" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1 flex items-center gap-1">
            <svg class="w-3 h-3 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Verifikasi keamanan berhasil
        </p>
    </div>
</template>

<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    siteKey: {
        type: String,
        default: '1x00000000000000000000AA',
    },
    theme: {
        type: String,
        default: 'auto', // 'auto', 'light', 'dark'
    },
    size: {
        type: String,
        default: 'normal', // 'normal', 'flexible', 'compact'
    },
});

const emit = defineEmits(['update:modelValue', 'verify', 'expire', 'error']);

const widgetContainer = ref(null);
const isLoading = ref(true);
const verified = ref(false);
let widgetId = null;

function renderTurnstile() {
    if (!window.turnstile || !widgetContainer.value) return;

    // Bersihkan widget sebelumnya jika ada
    if (widgetId !== null) {
        try {
            window.turnstile.remove(widgetId);
        } catch (e) {}
        widgetId = null;
    }

    try {
        widgetId = window.turnstile.render(widgetContainer.value, {
            sitekey: props.siteKey || '1x00000000000000000000AA',
            theme: props.theme,
            size: props.size,
            callback: (token) => {
                isLoading.value = false;
                verified.value = true;
                emit('update:modelValue', token);
                emit('verify', token);
            },
            'expired-callback': () => {
                verified.value = false;
                emit('update:modelValue', '');
                emit('expire');
            },
            'error-callback': (err) => {
                isLoading.value = false;
                verified.value = false;
                emit('update:modelValue', '');
                emit('error', err);
            },
        });
        isLoading.value = false;
    } catch (err) {
        console.error('Turnstile render error:', err);
        isLoading.value = false;
    }
}

function reset() {
    verified.value = false;
    emit('update:modelValue', '');
    if (window.turnstile && widgetId !== null) {
        try {
            window.turnstile.reset(widgetId);
        } catch (e) {}
    }
}

defineExpose({
    reset,
});

onMounted(() => {
    nextTick(() => {
        if (!document.getElementById('cf-turnstile-script')) {
            const script = document.createElement('script');
            script.id = 'cf-turnstile-script';
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            script.onload = () => {
                renderTurnstile();
            };
            document.head.appendChild(script);
        } else {
            if (window.turnstile) {
                renderTurnstile();
            } else {
                const interval = setInterval(() => {
                    if (window.turnstile) {
                        clearInterval(interval);
                        renderTurnstile();
                    }
                }, 100);
                setTimeout(() => {
                    clearInterval(interval);
                    isLoading.value = false;
                }, 6000);
            }
        }
    });
});

watch(
    () => props.theme,
    () => {
        renderTurnstile();
    }
);

onUnmounted(() => {
    if (window.turnstile && widgetId !== null) {
        try {
            window.turnstile.remove(widgetId);
        } catch (e) {}
    }
});
</script>

<style scoped>
.turnstile-container {
    min-height: 65px;
}
.cf-turnstile-slot {
    display: flex;
    justify-content: center;
    width: 100%;
}
</style>
