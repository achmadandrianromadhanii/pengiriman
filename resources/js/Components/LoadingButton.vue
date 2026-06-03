<script setup>
    defineProps({
        loading: { type: Boolean, default: false },
        variant: { type: String, default: 'primary' }, // primary|secondary|danger
        type: { type: String, default: 'button' },
    });
</script>

<template>
    <button
        :type="type"
        class="btn-base"
        :class="
            variant === 'primary'
                ? 'btn-primary'
                : variant === 'danger'
                  ? 'btn-danger'
                  : 'btn-secondary'
        "
        :disabled="loading"
    >
        <i v-if="loading" class="bi bi-arrow-repeat animate-spin"></i>
        <slot />
    </button>
</template>
