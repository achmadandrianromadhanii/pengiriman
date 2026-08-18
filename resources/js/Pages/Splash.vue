<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

defineOptions({ layout: false });

const props = defineProps({
    intended_url: { type: String, default: '/dashboard' },
});

const page = usePage();

const appState = ref('idle');
const statusText = ref('');
const progress = ref(0);
const targetProg = ref(0);
const fadeOut = ref(false);
const errorMsg = ref('');
const logoPhase = ref(0);

const initSteps = [
    {
        label: 'Initializing application...',
        weight: 15,
        run: async () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (!meta?.content) throw new Error('Runtime not ready');
        },
    },
    {
        label: 'Checking secure session...',
        weight: 20,
        run: async () => {
            await nextTick();
            if (page.props?.auth === undefined) throw new Error('Session unavailable');
        },
    },
    {
        label: 'Preparing services...',
        weight: 20,
        run: async () => {
            if ('serviceWorker' in navigator) {
                try { await navigator.serviceWorker.register('/sw.js'); } catch { /* non-fatal */ }
            }
        },
    },
    {
        label: 'Loading configuration...',
        weight: 20,
        run: async () => {
            const dark = localStorage.getItem('darkMode');
            if (dark === 'true' || (dark === null && window.matchMedia?.('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        },
    },
    {
        label: 'Preparing workspace...',
        weight: 15,
        run: async () => { await nextTick(); },
    },
    {
        label: 'Almost ready...',
        weight: 10,
        run: async () => { await nextTick(); },
    },
];

let rafId = null;

function startProgressAnimation() {
    function tick() {
        const current = progress.value;
        const target = targetProg.value;
        const diff = target - current;

        if (diff > 0.05) {
            progress.value = current + diff * 0.04;
        } else if (diff > 0) {
            progress.value = target;
        }

        rafId = requestAnimationFrame(tick);
    }
    rafId = requestAnimationFrame(tick);
}

function stopProgressAnimation() {
    if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
}

function pause(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function runSplash() {
    appState.value = 'idle';

    await pause(150);
    logoPhase.value = 1;
    await pause(700);

    logoPhase.value = 2;
    await pause(800);

    logoPhase.value = 3;
    await pause(600);

    appState.value = 'initializing';
    statusText.value = initSteps[0].label;

    startProgressAnimation();

    const startTime = Date.now();

    try {
        let accumulated = 0;

        for (let i = 0; i < initSteps.length; i++) {
            const step = initSteps[i];
            statusText.value = step.label;

            await step.run();

            accumulated += step.weight;
            targetProg.value = Math.min(accumulated, 95);

            const pauses = [500, 500, 480, 460, 440, 400];
            await pause(pauses[i] ?? 400);
        }

        const elapsed = Date.now() - startTime;
        if (elapsed < 1200) await pause(1200 - elapsed);

        statusText.value = 'Ready';
        targetProg.value = 100;
        appState.value = 'ready';

        await pause(800);

        stopProgressAnimation();
        progress.value = 100;

        await pause(500);

        fadeOut.value = true;
        await pause(350);

        const user = page.props?.auth?.user;
        const dest = user ? (props.intended_url || '/dashboard') : '/login';
        router.visit(dest, { replace: true });

    } catch (err) {
        stopProgressAnimation();
        appState.value = 'error';
        errorMsg.value = err.message || 'Initialization failed. Please try again.';
    }
}

function retryInit() {
    fadeOut.value = false;
    logoPhase.value = 0;
    progress.value = 0;
    targetProg.value = 0;
    errorMsg.value = '';
    stopProgressAnimation();
    runSplash();
}

onMounted(() => { runSplash(); });
onUnmounted(() => { stopProgressAnimation(); });
</script>

<template>
    <div class="splash-root" :class="{ 'splash-fadeout': fadeOut }" style="
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        ">
        <div class="bg-base" aria-hidden="true"></div>
        <div class="bg-glow-tl" aria-hidden="true"></div>
        <div class="bg-glow-br" aria-hidden="true"></div>
        <div class="bg-drift" aria-hidden="true"></div>
        <svg class="bg-arcs" viewBox="0 0 400 800" fill="none" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <circle cx="200" cy="220" r="150" stroke="#93C5FD" stroke-width="1" />
            <circle cx="200" cy="220" r="230" stroke="#93C5FD" stroke-width="0.8" />
            <circle cx="200" cy="220" r="310" stroke="#93C5FD" stroke-width="0.6" />
            <path d="M-50,620 Q150,520 350,620 T750,600" stroke="#93C5FD" stroke-width="1" />
            <path d="M-50,660 Q150,560 350,660 T750,650" stroke="#93C5FD" stroke-width="0.7" />
        </svg>

        <div class="spacer"></div>

        <div class="main-content">
            <div class="logo-wrapper" :class="{
                'logo-phase-1': logoPhase === 1,
                'logo-phase-2': logoPhase === 2,
                'logo-phase-3': logoPhase >= 3,
            }">
                <div class="logo-glow-inner" aria-hidden="true"></div>
                <div class="logo-glow-outer" aria-hidden="true"></div>
                <img src="/images/logo-softsend-hd.png" alt="SoftSend" class="logo-img"
                    :class="{ 'logo-float': logoPhase >= 3 }" width="210" height="210" fetchpriority="high"
                    draggable="false" />
                <div v-if="logoPhase >= 3" class="logo-sweep" aria-hidden="true"></div>
            </div>

            <div class="brand-text"
                :class="{ 'brand-visible': appState === 'initializing' || appState === 'ready' || appState === 'error' }">
                <h1 class="brand-name">SoftSend</h1>
                <p class="brand-sub">Premium Delivery Management</p>
            </div>

            <div class="loader-section"
                :class="{ 'loader-visible': appState === 'initializing' || appState === 'ready' || appState === 'error' }">
                <p class="status-text" aria-live="polite" aria-atomic="true">
                    {{ statusText }}
                </p>

                <div class="progress-track" role="progressbar" :aria-valuenow="Math.round(progress)" aria-valuemin="0"
                    aria-valuemax="100">
                    <div class="progress-fill" :style="{ width: Math.min(progress, 100).toFixed(1) + '%' }"></div>
                </div>

                <p v-if="appState === 'initializing' || appState === 'ready'" class="progress-pct">
                    {{ Math.round(Math.min(progress, 100)) }}%
                </p>

                <div v-if="appState === 'error'" class="error-state">
                    <p class="error-msg">{{ errorMsg }}</p>
                    <button class="retry-btn" @click="retryInit">
                        Retry
                    </button>
                </div>
            </div>
        </div>

        <div class="spacer"></div>

        <div class="footer-brand">
            <p class="footer-powered">Powered by</p>
            <p class="footer-name">NOCTRYNX CORP</p>
        </div>
    </div>
</template>

<style scoped>
.splash-root {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    user-select: none;
    -webkit-user-select: none;
    opacity: 1;
    transition: opacity 350ms ease-out;
}

.splash-root.splash-fadeout {
    opacity: 0;
    pointer-events: none;
}

.bg-base {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #0c1836 0%, #1a3580 45%, #2d2f82 100%);
}

.bg-glow-tl {
    position: absolute;
    top: -15%;
    left: -15%;
    width: 65%;
    height: 55%;
    border-radius: 50%;
    background: radial-gradient(ellipse, rgba(37, 99, 235, 0.12), transparent 70%);
    pointer-events: none;
}

.bg-glow-br {
    position: absolute;
    bottom: -12%;
    right: -12%;
    width: 55%;
    height: 48%;
    border-radius: 50%;
    background: radial-gradient(ellipse, rgba(99, 102, 241, 0.09), transparent 70%);
    pointer-events: none;
}

.bg-drift {
    position: absolute;
    inset: -5%;
    background: radial-gradient(ellipse at 30% 40%, rgba(59, 130, 246, 0.07) 0%, transparent 60%);
    animation: bgDrift 16s ease-in-out infinite;
    pointer-events: none;
}

@keyframes bgDrift {

    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    33% {
        transform: translate(2%, -1%) scale(1.02);
    }

    66% {
        transform: translate(-1%, 2%) scale(0.99);
    }
}

.bg-arcs {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0.04;
    pointer-events: none;
}

.spacer {
    flex: 1;
}

.main-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 380px;
    padding: 0 32px;
}

.logo-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 210px;
    height: 210px;
    opacity: 0;
}

.logo-phase-1 {
    animation: logoMaterialize 700ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes logoMaterialize {
    0% {
        opacity: 0;
        transform: scale(0.92);
        filter: blur(4px);
    }

    60% {
        opacity: 0.7;
        transform: scale(0.97);
        filter: blur(1px);
    }

    100% {
        opacity: 1;
        transform: scale(1);
        filter: blur(0);
    }
}

.logo-phase-2 {
    opacity: 1;
    animation: logoAssemble 800ms cubic-bezier(0.25, 1, 0.5, 1) forwards;
}

@keyframes logoAssemble {
    0% {
        transform: scale(1) translateY(0);
        filter: brightness(0.85);
    }

    40% {
        transform: scale(1.01) translateY(-2px);
        filter: brightness(1.05);
    }

    70% {
        transform: scale(1) translateY(-1px);
        filter: brightness(1.1);
    }

    100% {
        transform: scale(1) translateY(0);
        filter: brightness(1);
    }
}

.logo-phase-3 {
    opacity: 1;
    animation: logoLock 600ms cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
}

@keyframes logoLock {
    0% {
        transform: scale(1);
        filter: brightness(1);
    }

    50% {
        transform: scale(1.015);
        filter: brightness(1.12);
    }

    100% {
        transform: scale(1);
        filter: brightness(1);
    }
}

.logo-img {
    position: relative;
    z-index: 3;
    width: 210px;
    height: auto;
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
    filter: drop-shadow(0 8px 30px rgba(0, 0, 0, 0.35)) drop-shadow(0 2px 8px rgba(59, 130, 246, 0.18));
}

.logo-float {
    animation: logoFloat 6s ease-in-out infinite;
    animation-delay: 200ms;
}

@keyframes logoFloat {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-7px);
    }
}

.logo-glow-inner {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.07) 0%, transparent 70%);
    animation: glowPulse 5s ease-in-out infinite;
    pointer-events: none;
}

@keyframes glowPulse {

    0%,
    100% {
        transform: scale(0.85);
        opacity: 0.5;
    }

    50% {
        transform: scale(1.15);
        opacity: 1;
    }
}

.logo-glow-outer {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 65%);
    animation: glowPulseOuter 7s ease-in-out infinite;
    pointer-events: none;
}

@keyframes glowPulseOuter {

    0%,
    100% {
        transform: scale(1.1);
        opacity: 0.4;
    }

    50% {
        transform: scale(0.9);
        opacity: 0.8;
    }
}

.logo-sweep {
    position: absolute;
    inset: 0;
    z-index: 4;
    border-radius: 4px;
    background: linear-gradient(105deg,
            transparent 20%,
            rgba(255, 255, 255, 0.18) 50%,
            transparent 80%);
    animation: lightSweep 800ms cubic-bezier(0.4, 0, 0.2, 1) 100ms forwards;
    pointer-events: none;
}

@keyframes lightSweep {
    0% {
        transform: translateX(-100%);
        opacity: 1;
    }

    100% {
        transform: translateX(120%);
        opacity: 0.5;
    }
}

.brand-text {
    margin-top: 20px;
    text-align: center;
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 400ms ease-out, transform 400ms cubic-bezier(0.16, 1, 0.3, 1);
}

.brand-text.brand-visible {
    opacity: 1;
    transform: translateY(0);
}

.brand-name {
    font-size: 24px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.02em;
    line-height: 1;
    font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
    margin: 0;
}

.brand-sub {
    font-size: 12px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.58);
    letter-spacing: 0.04em;
    margin: 6px 0 0 0;
}

.loader-section {
    width: 100%;
    margin-top: 36px;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 350ms ease-out 100ms, transform 350ms ease-out 100ms;
}

.loader-section.loader-visible {
    opacity: 1;
    transform: translateY(0);
}

.status-text {
    font-size: 12px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.65);
    text-align: center;
    margin: 0 0 14px 0;
    min-height: 18px;
    transition: opacity 200ms ease;
}

.progress-track {
    width: 100%;
    height: 3px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.85) 0%, #93c5fd 100%);
    will-change: width;
}

.progress-pct {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.45);
    text-align: center;
    margin: 10px 0 0 0;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
}

.error-state {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.error-msg {
    font-size: 12px;
    font-weight: 500;
    color: #fca5a5;
    text-align: center;
    margin: 0;
}

.retry-btn {
    padding: 9px 28px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.04em;
    cursor: pointer;
    transition: background 150ms ease, transform 150ms ease;
    -webkit-tap-highlight-color: transparent;
}

.retry-btn:active {
    transform: scale(0.96);
    background: rgba(255, 255, 255, 0.15);
}

.footer-brand {
    position: relative;
    z-index: 10;
    padding-bottom: 24px;
    text-align: center;
    animation: splashFadeIn 400ms ease-out 1100ms both;
}

.footer-powered {
    font-size: 9px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.28);
    letter-spacing: 0.16em;
    text-transform: uppercase;
    margin: 0;
}

.footer-name {
    font-size: 11px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.38);
    letter-spacing: 0.2em;
    text-transform: uppercase;
    margin: 3px 0 0 0;
}

@keyframes splashFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}
</style>
