<template>
    <div class="turnstile-container flex justify-center items-center my-2.5 w-full min-h-[65px]">
        <!-- Pure Cloudflare Turnstile Official Widget -->
        <div ref="widgetContainer" class="cf-turnstile-slot flex justify-center w-full"></div>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    siteKey: {
        type: String,
        default: '0x4AAAAAAF-ETW-sVXo5zp-u',
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
let widgetId = null;
let pollInterval = null;
let retryCount = 0;
let isRendering = false;

function ensureScriptLoaded(callback) {
    if (window.turnstile && typeof window.turnstile.render === 'function') {
        callback();
        return;
    }

    const hasScript =
        document.getElementById('cf-turnstile-script') ||
        document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');

    if (!hasScript) {
        const script = document.createElement('script');
        script.id = 'cf-turnstile-script';
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    let elapsed = 0;
    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(() => {
        elapsed += 50;
        if (window.turnstile && typeof window.turnstile.render === 'function') {
            clearInterval(pollInterval);
            pollInterval = null;
            callback();
        } else if (elapsed >= 10000) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }, 50);
}

function destroyWidget() {
    if (widgetId !== null && window.turnstile && typeof window.turnstile.remove === 'function') {
        try {
            window.turnstile.remove(widgetId);
        } catch (e) {}
        widgetId = null;
    }
    if (widgetContainer.value) {
        widgetContainer.value.innerHTML = '';
    }
}

function renderTurnstile() {
    if (!widgetContainer.value) return;

    // Pastikan container sudah ada di DOM
    if (!document.body.contains(widgetContainer.value)) {
        if (retryCount < 20) {
            retryCount++;
            setTimeout(renderTurnstile, 60);
        }
        return;
    }

    if (!window.turnstile || typeof window.turnstile.render !== 'function') {
        ensureScriptLoaded(renderTurnstile);
        return;
    }

    if (isRendering) return;
    isRendering = true;

    destroyWidget();

    const effectiveKey = props.siteKey || '0x4AAAAAAF-ETW-sVXo5zp-u';

    try {
        widgetId = window.turnstile.render(widgetContainer.value, {
            sitekey: effectiveKey,
            theme: props.theme || 'auto',
            size: props.size || 'normal',
            callback: (token) => {
                isRendering = false;
                emit('update:modelValue', token);
                emit('verify', token);
            },
            'expired-callback': () => {
                emit('update:modelValue', '');
                emit('expire');
            },
            'error-callback': (code) => {
                isRendering = false;
                emit('update:modelValue', '');
                emit('error', code);
            },
        });

        isRendering = false;
    } catch (err) {
        console.error('[Cloudflare Turnstile] Render error:', err);
        isRendering = false;
    }
}

function reset() {
    emit('update:modelValue', '');
    if (window.turnstile && widgetId !== null) {
        try {
            window.turnstile.reset(widgetId);
        } catch (e) {}
    }
}

defineExpose({
    reset,
    retry: renderTurnstile,
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
    () => {
        renderTurnstile();
    }
);

onBeforeUnmount(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    destroyWidget();
});
</script>

<style scoped>
.turnstile-container {
    min-height: 65px;
}
</style>
