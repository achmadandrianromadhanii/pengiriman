<script setup>
    defineProps({
        className: { type: String, default: '' },
    });
</script>

<template>
    <div class="skeleton" :class="className" aria-hidden="true"></div>
</template>
