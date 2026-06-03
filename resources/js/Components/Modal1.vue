<script setup>
    import { computed, watch } from 'vue';

    const props = defineProps({
        show: { type: Boolean, default: false },
        title: { type: String, default: '' },
        maxWidth: { type: String, default: 'max-w-2xl' },
    });

    const emit = defineEmits(['close']);

    function close() {
        emit('close');
    }

    watch(
        () => props.show,
        (v) => {
            if (v) document.body.style.overflow = 'hidden';
            else document.body.style.overflow = '';
        },
    );

    const panelClass = computed(() => `${props.maxWidth} w-full`);
</script>

<template>
    <teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[60]">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div
                    class="bg-white dark:bg-card-dark rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden w-full transition-all duration-200"
                    :class="panelClass"
                    style="transform: translateY(0)"
                >
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between"
                    >
                        <div class="font-heading text-lg text-gray-900 dark:text-gray-100">
                            {{ title }}
                        </div>
                        <button
                            class="btn-secondary !px-3 !py-2"
                            type="button"
                            @click="close"
                            aria-label="Close"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>
