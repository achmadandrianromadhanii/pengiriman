<script setup>
import { computed } from 'vue';
import { Shield, Check, X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    value: {
        type: String,
        default: '',
    },
});

// Aturan validasi kekuatan password (persis kode referensi dengan terjemahan bahasa Indonesia)
const rules = [
    { id: 'length', label: 'Minimal 12 karakter', test: (v) => (v || '').length >= 12, weight: 20 },
    { id: 'uppercase', label: 'Mengandung huruf besar (A-Z)', test: (v) => /[A-Z]/.test(v || ''), weight: 20 },
    { id: 'lowercase', label: 'Mengandung huruf kecil (a-z)', test: (v) => /[a-z]/.test(v || ''), weight: 20 },
    { id: 'number', label: 'Mengandung angka (0-9)', test: (v) => /[0-9]/.test(v || ''), weight: 20 },
    { id: 'special', label: 'Mengandung karakter khusus (!@#$...)', test: (v) => /[^a-zA-Z0-9]/.test(v || ''), weight: 20 },
];

const score = computed(() => {
    if (!props.value) return 0;
    return rules.reduce((acc, rule) => acc + (rule.test(props.value) ? rule.weight : 0), 0);
});

const severity = computed(() => {
    if (score.value <= 20) return 'danger';
    if (score.value <= 40) return 'warn';
    if (score.value <= 60) return 'info';
    return 'success';
});

const color = computed(() => {
    switch (severity.value) {
        case 'danger':
            return '#EF4444'; // Merah
        case 'warn':
            return '#F59E0B'; // Oranye / Amber
        case 'info':
            return '#3B82F6'; // Biru
        default:
            return '#10B981'; // Hijau
    }
});

const label = computed(() => {
    if (score.value === 0) return '';
    if (score.value <= 20) return 'Sangat Lemah';
    if (score.value <= 40) return 'Lemah';
    if (score.value <= 60) return 'Cukup';
    if (score.value <= 80) return 'Kuat';
    return 'Sangat Kuat';
});

const tagClass = computed(() => {
    switch (severity.value) {
        case 'danger':
            return 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800';
        case 'warn':
            return 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800';
        case 'info':
            return 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800';
        default:
            return 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800';
    }
});
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-1 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-1 scale-95"
    >
        <div
            v-if="show"
            class="absolute left-0 right-0 top-full mt-2 z-50 p-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl font-sans"
        >
            <div class="flex flex-col gap-2.5">
                <!-- Header: Icon Shield + Title + Tag Label -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-slate-800 dark:text-slate-100">
                        <Shield class="w-4 h-4 text-[#2563EB]" />
                        <span class="font-bold text-xs tracking-tight">Kekuatan Password</span>
                    </div>
                    <span
                        v-if="label"
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="tagClass"
                    >
                        {{ label }}
                    </span>
                    <span v-else class="text-[10px] text-slate-400">—</span>
                </div>

                <!-- Progress Bar (height: 6px) -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :style="{ width: `${score}%`, backgroundColor: color }"
                    ></div>
                </div>

                <!-- Rules list -->
                <ul class="flex flex-col gap-1.5 list-none m-0 p-0">
                    <li
                        v-for="rule in rules"
                        :key="rule.id"
                        class="flex items-center gap-2 text-[11px]"
                    >
                        <Check
                            v-if="rule.test(value)"
                            class="w-3.5 h-3.5 text-emerald-500 shrink-0 stroke-[2.5]"
                        />
                        <X
                            v-else
                            class="w-3.5 h-3.5 text-rose-400 shrink-0 stroke-[2.5]"
                        />
                        <span
                            :class="rule.test(value) ? 'text-slate-700 dark:text-slate-200 font-medium' : 'text-slate-400 dark:text-slate-500'"
                        >
                            {{ rule.label }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </transition>
</template>
