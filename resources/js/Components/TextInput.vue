<script setup>
    import { onMounted, ref } from 'vue';

    const model = defineModel({
        // Menghapus type: String yang kaku untuk mencegah pesan error di Vue:
        // "Invalid prop: type check failed for prop modelValue Expected String"
        // Terutama ketika form menerima nilai awal null atau undefined dari database
        required: false,
    });

    const input = ref(null);

    onMounted(() => {
        if (input.value.hasAttribute('autofocus')) {
            input.value.focus();
        }
    });

    defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        v-model="model"
        ref="input"
    />
</template>
