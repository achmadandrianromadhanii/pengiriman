<template>
    <div class="turnstile-container flex flex-col items-center justify-center my-2.5 w-full">
        <!-- 1. State Loading (Aktif saat mengunduh skrip Cloudflare) -->
        <div
            v-if="isLoading && !errorMessage"
            class="flex items-center gap-2 py-2 px-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200/80 dark:border-slate-800 animate-pulse my-1"
        >
            <svg class="w-3.5 h-3.5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-[11.5px] font-medium">Memuat verifikasi Cloudflare...</span>
        </div>

        <!-- 2. State Error / Domain Mismatch -->
        <div
            v-if="errorMessage"
            class="flex flex-col items-center text-center p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-xl max-w-sm w-full my-1.5 shadow-sm"
        >
            <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400 text-xs font-semibold">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Kendala Verifikasi Cloudflare</span>
            </div>
            <p class="text-[11px] text-rose-600/90 dark:text-rose-400/90 mt-1 leading-snug">
                {{ errorMessage }}
            </p>

            <div class="flex flex-wrap items-center justify-center gap-2 mt-2.5">
                <button
                    type="button"
                    @click="retryRender"
                    class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-rose-300 dark:border-rose-800 text-[11px] font-medium text-rose-600 dark:text-rose-300 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-all flex items-center gap-1 cursor-pointer"
                >
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6"></path>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    <span>Coba Lagi</span>
                </button>

                <!-- Tombol Fallback Pengujian Lokal jika di localhost -->
                <button
                    v-if="isLocalEnvironment && currentSiteKey !== '1x00000000000000000000AA'"
                    type="button"
                    @click="useTestKey"
                    class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-medium rounded-lg transition-all flex items-center gap-1 cursor-pointer shadow-sm"
                    title="Gunakan kunci pengujian Cloudflare yang selalu valid di localhost"
                >
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Mode Pengujian Lokal</span>
                </button>
            </div>
        </div>

        <!-- 3. Wadah Widget Turnstile Asli (TIDAK BOLEH DISPLAY:NONE SAAT MEMUAT) -->
        <div
            ref="widgetContainer"
            class="cf-turnstile-slot flex justify-center w-full min-h-[65px]"
            :class="{ hidden: errorMessage }"
        ></div>

        <!-- 4. Pesan Status Sukses -->
        <p
            v-if="verified"
            class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1 flex items-center gap-1"
        >
            <svg class="w-3.5 h-3.5 stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span>Verifikasi keamanan berhasil</span>
        </p>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
        default: 'normal', // 'normal', 'compact'
    },
});

const emit = defineEmits(['update:modelValue', 'verify', 'expire', 'error']);

const widgetContainer = ref(null);
const isLoading = ref(true);
const verified = ref(false);
const errorMessage = ref('');
const currentSiteKey = ref(props.siteKey || '1x00000000000000000000AA');

let widgetId = null;
let pollInterval = null;
let retryCount = 0;

const isLocalEnvironment = computed(() => {
    if (typeof window === 'undefined') return false;
    return (
        window.location.hostname === 'localhost' ||
        window.location.hostname === '127.0.0.1' ||
        window.location.hostname.endsWith('.test') ||
        window.location.hostname.endsWith('.local')
    );
});

function ensureScriptLoaded(callback) {
    if (window.turnstile && typeof window.turnstile.render === 'function') {
        callback();
        return;
    }

    if (!document.getElementById('cf-turnstile-script')) {
        const script = document.createElement('script');
        script.id = 'cf-turnstile-script';
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            isLoading.value = false;
            errorMessage.value = 'Gagal memuat skrip Cloudflare Turnstile. Periksa koneksi internet atau matikan adblocker Anda.';
        };
        document.head.appendChild(script);
    }

    // Polling non-blocking sampai window.turnstile.render siap
    let elapsed = 0;
    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(() => {
        elapsed += 50;
        if (window.turnstile && typeof window.turnstile.render === 'function') {
            clearInterval(pollInterval);
            pollInterval = null;
            callback();
        } else if (elapsed >= 8000) {
            clearInterval(pollInterval);
            pollInterval = null;
            isLoading.value = false;
            errorMessage.value = 'Waktu tunggu memuat Cloudflare habis. Silakan klik tombol Coba Lagi.';
        }
    }, 50);
}

function renderTurnstile() {
    if (!widgetContainer.value) return;

    // Pastikan elemen sudah terpasang di DOM dokumen
    if (!document.body.contains(widgetContainer.value)) {
        if (retryCount < 25) {
            retryCount++;
            setTimeout(renderTurnstile, 60);
        }
        return;
    }

    // Pastikan API Turnstile sudah tersedia
    if (!window.turnstile || typeof window.turnstile.render !== 'function') {
        ensureScriptLoaded(renderTurnstile);
        return;
    }

    // Bersihkan widget sebelumnya jika ada
    if (widgetId !== null) {
        try {
            window.turnstile.remove(widgetId);
        } catch (e) {}
        widgetId = null;
    }

    widgetContainer.value.innerHTML = '';
    errorMessage.value = '';

    const effectiveKey = currentSiteKey.value || props.siteKey || '1x00000000000000000000AA';

    try {
        widgetId = window.turnstile.render(widgetContainer.value, {
            sitekey: effectiveKey,
            theme: props.theme || 'auto',
            size: props.size || 'normal',
            callback: (token) => {
                isLoading.value = false;
                verified.value = true;
                errorMessage.value = '';
                emit('update:modelValue', token);
                emit('verify', token);
            },
            'expired-callback': () => {
                verified.value = false;
                emit('update:modelValue', '');
                emit('expire');
            },
            'error-callback': (code) => {
                isLoading.value = false;
                verified.value = false;
                emit('update:modelValue', '');

                const isLocal = isLocalEnvironment.value;

                if (code === '600010' || code === '300030' || String(code).includes('domain')) {
                    if (isLocal) {
                        errorMessage.value =
                            "Domain 'localhost' belum didaftarkan di Cloudflare Turnstile dashboard Anda. Anda dapat mendaftarkan 'localhost' di Cloudflare atau klik 'Mode Pengujian Lokal' di bawah.";
                    } else {
                        errorMessage.value =
                            'Domain ini belum diizinkan di Cloudflare Turnstile (Error ' + code + '). Pastikan domain telah didaftarkan pada Dashboard Cloudflare.';
                    }
                } else if (code === '110200') {
                    // Widget already rendered, abaikan
                    return;
                } else {
                    errorMessage.value = 'Verifikasi Cloudflare gagal (Kode: ' + (code || 'ERR') + '). Silakan klik Coba Lagi.';
                }

                emit('error', code);
            },
        });

        // Widget sudah berhasil dirender ke DOM
        isLoading.value = false;
    } catch (err) {
        console.error('Turnstile render exception:', err);
        isLoading.value = false;
        errorMessage.value = 'Terjadi kendala saat merender widget Cloudflare.';
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

function retryRender() {
    errorMessage.value = '';
    isLoading.value = true;
    retryCount = 0;
    renderTurnstile();
}

function useTestKey() {
    currentSiteKey.value = '1x00000000000000000000AA';
    errorMessage.value = '';
    isLoading.value = true;
    retryCount = 0;
    renderTurnstile();
}

defineExpose({
    reset,
    retry: retryRender,
});

onMounted(() => {
    nextTick(() => {
        renderTurnstile();
    });
});

watch(
    () => props.theme,
    () => {
        renderTurnstile();
    }
);

watch(
    () => props.siteKey,
    (newVal) => {
        if (newVal) {
            currentSiteKey.value = newVal;
            renderTurnstile();
        }
    }
);

onBeforeUnmount(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    if (window.turnstile && widgetId !== null) {
        try {
            window.turnstile.remove(widgetId);
        } catch (e) {}
        widgetId = null;
    }
});
</script>

<style scoped>
.turnstile-container {
    min-height: 65px;
}
</style>
