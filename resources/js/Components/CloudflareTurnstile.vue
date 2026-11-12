<template>
    <div class="turnstile-container flex flex-col items-center justify-center my-2.5 w-full">
        <!-- 1. State Loading -->
        <div
            v-if="isLoading"
            class="flex items-center gap-2 py-2 px-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200/80 dark:border-slate-800 animate-pulse"
        >
            <svg class="w-3.5 h-3.5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-[11.5px] font-medium">Memuat verifikasi Cloudflare...</span>
        </div>

        <!-- 2. State Error / Domain Mismatch -->
        <div
            v-else-if="errorMessage"
            class="flex flex-col items-center text-center p-2.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-xl max-w-sm w-full"
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
            <button
                type="button"
                @click="retryRender"
                class="mt-2 px-3 py-1 bg-white dark:bg-slate-900 border border-rose-300 dark:border-rose-800 text-[11px] font-medium text-rose-600 dark:text-rose-300 rounded-lg hover:bg-rose-100/50 transition-all flex items-center gap-1"
            >
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"></path>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                <span>Coba Lagi</span>
            </button>
        </div>

        <!-- 3. Wadah Widget Turnstile Asli -->
        <div
            ref="widgetContainer"
            class="cf-turnstile-slot flex justify-center w-full min-h-[65px]"
            :class="{ hidden: isLoading || errorMessage }"
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
        default: 'normal', // 'normal', 'compact'
    },
});

const emit = defineEmits(['update:modelValue', 'verify', 'expire', 'error']);

const widgetContainer = ref(null);
const isLoading = ref(true);
const verified = ref(false);
const errorMessage = ref('');
let widgetId = null;
let retryTimeout = null;

// Pendaftaran callback global resmi Cloudflare Turnstile
if (typeof window !== 'undefined') {
    window.__cfTurnstileLoaded = window.__cfTurnstileLoaded || false;
    window.__cfTurnstileCallbacks = window.__cfTurnstileCallbacks || [];

    window.cfTurnstileOnloadCallback = function () {
        window.__cfTurnstileLoaded = true;
        while (window.__cfTurnstileCallbacks && window.__cfTurnstileCallbacks.length > 0) {
            const cb = window.__cfTurnstileCallbacks.shift();
            try {
                cb();
            } catch (e) {
                console.error('Error invoking turnstile callback:', e);
            }
        }
    };
}

function renderTurnstile() {
    if (!widgetContainer.value) return;

    // Pastikan elemen sudah terlihat di DOM (tidak display: none atau sedang dalam animasi transisi)
    if (widgetContainer.value.offsetParent === null) {
        if (retryTimeout) clearTimeout(retryTimeout);
        retryTimeout = setTimeout(renderTurnstile, 80);
        return;
    }

    // Pastikan API turnstile sudah siap
    if (!window.turnstile || typeof window.turnstile.render !== 'function') {
        if (retryTimeout) clearTimeout(retryTimeout);
        retryTimeout = setTimeout(renderTurnstile, 100);
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

    const effectiveSiteKey = props.siteKey || '1x00000000000000000000AA';

    try {
        widgetId = window.turnstile.render(widgetContainer.value, {
            sitekey: effectiveSiteKey,
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

                const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

                if (code === '600010' || code === '300030' || String(code).includes('domain')) {
                    if (isLocal) {
                        errorMessage.value =
                            "Domain 'localhost' belum didaftarkan di Cloudflare Turnstile. Silakan tambahkan 'localhost' dan '127.0.0.1' di pengaturan domain situs Cloudflare Anda.";
                    } else {
                        errorMessage.value = 'Domain ini belum diizinkan di Cloudflare Turnstile (Error ' + code + ').';
                    }
                } else if (code === '110200') {
                    // Widget already rendered, abaikan
                    return;
                } else {
                    errorMessage.value = 'Verifikasi Cloudflare gagal (Kode: ' + (code || 'ERR') + '). Silakan coba lagi.';
                }

                emit('error', code);
            },
        });

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
    renderTurnstile();
}

defineExpose({
    reset,
    retry: retryRender,
});

function initScript() {
    // Jika window.turnstile sudah siap, langsung render (JANGAN panggil turnstile.ready() jika ada async/defer)
    if (window.turnstile && typeof window.turnstile.render === 'function') {
        renderTurnstile();
        return;
    }

    // Daftarkan callback render jika script sedang/akan dimuat
    window.__cfTurnstileCallbacks.push(renderTurnstile);

    if (!document.getElementById('cf-turnstile-script')) {
        const script = document.createElement('script');
        script.id = 'cf-turnstile-script';
        // Gunakan parameter resmi onload=cfTurnstileOnloadCallback yang otomatis dipanggil Cloudflare saat siap
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=cfTurnstileOnloadCallback';
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            isLoading.value = false;
            errorMessage.value = 'Gagal memuat skrip Cloudflare. Periksa koneksi internet atau adblocker Anda.';
        };
        document.head.appendChild(script);
    }
}

onMounted(() => {
    nextTick(() => {
        initScript();
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
    () => {
        renderTurnstile();
    }
);

onUnmounted(() => {
    if (retryTimeout) clearTimeout(retryTimeout);
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
</style>
