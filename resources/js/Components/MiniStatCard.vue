<script setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

    const props = defineProps({
        title: { type: String, required: true },
        value: { type: Number, required: true },
        icon: { type: String, required: true },
        topBorderClass: { type: String, default: 'border-primary' },
        toneClass: { type: String, default: 'text-primary' }, // icon + number tone
        durationMs: { type: Number, default: 1000 },
    });

    const display = ref(0);
    let rafId = null;

    const formatted = computed(() =>
        new Intl.NumberFormat('id-ID').format(Math.round(display.value)),
    );

    function animateTo(target) {
        if (rafId) cancelAnimationFrame(rafId);
        const start = performance.now();
        const from = 0;
        const to = Number(target || 0);
        const dur = Math.max(250, props.durationMs);

        const step = (t) => {
            const p = Math.min(1, (t - start) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            display.value = from + (to - from) * eased;
            if (p < 1) rafId = requestAnimationFrame(step);
        };
        rafId = requestAnimationFrame(step);
    }

    watch(
        () => props.value,
        (v) => animateTo(v),
        { immediate: true },
    );

    onMounted(() => animateTo(props.value));
    onBeforeUnmount(() => {
        if (rafId) cancelAnimationFrame(rafId);
    });
</script>

<template>
    <div class="card card-hover relative overflow-hidden p-5">
        <div class="absolute inset-x-0 top-0 h-[3px]" :class="topBorderClass"></div>

        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    {{ title }}
                </div>
                <div class="mt-2 font-heading font-extrabold text-3xl" :class="toneClass">
                    {{ formatted }}
                </div>
            </div>

            <div
                class="w-11 h-11 rounded-2xl flex items-center justify-center bg-gray-100 dark:bg-white/5"
            >
                <i class="bi text-xl" :class="[icon, toneClass]"></i>
            </div>
        </div>
    </div>
</template>
