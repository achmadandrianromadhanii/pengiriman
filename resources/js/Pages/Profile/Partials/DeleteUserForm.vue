<script setup>
    import DangerButton from '@/Components/DangerButton.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import Modal from '@/Components/Modal.vue';
    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { useForm } from '@inertiajs/vue3';
    import { nextTick, ref } from 'vue';

    const confirmingUserDeletion = ref(false);
    const passwordInput = ref(null);
    const showModalPassword = ref(false);

    const form = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        confirmingUserDeletion.value = true;

        nextTick(() => passwordInput.value.focus());
    };

    const deleteUser = () => {
        form.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.value.focus(),
            onFinish: () => form.reset(),
        });
    };

    const closeModal = () => {
        confirmingUserDeletion.value = false;

        form.clearErrors();
        form.reset();
    };
</script>

<template>
    <section class="space-y-6 relative">
        <!-- [UPDATE: DANGER ZONE INDICATOR] -->
        <!-- Fungsi: Garis tepi merah tebal untuk mempertegas area berbahaya secara visual -->
        <div class="absolute -left-5 md:-left-8 top-0 bottom-0 w-1.5 bg-red-500 rounded-r-md"></div>

        <header>
            <!-- [UPDATE: JUDUL DANGER ZONE] -->
            <!-- Fungsi: Teks dengan gradien merah dan ikon peringatan dramatis -->
            <h2
                class="text-xl font-black flex items-center gap-2 tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-red-600 to-red-800 dark:from-red-400 dark:to-red-600"
            >
                <i class="bi bi-exclamation-triangle-fill text-red-600 dark:text-red-500"></i> Hapus
                Akun
            </h2>

            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 font-medium leading-relaxed">
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara
                permanen. Sebelum menghapus akun Anda, harap unduh semua data yang ingin Anda
                simpan.
            </p>
        </header>

        <!-- [UPDATE: TOMBOL HAPUS AKUN] -->
        <!-- Fungsi: Desain tombol bahaya yang agresif namun tetap premium -->
        <DangerButton
            @click="confirmUserDeletion"
            class="w-full md:w-auto flex justify-center items-center gap-2 rounded-full md:rounded-lg h-12 md:h-10 text-sm bg-red-50 dark:bg-red-500/10 text-red-600 font-bold border border-red-100 dark:border-red-500/20 hover:bg-red-600 hover:text-white transition-all active:scale-95"
        >
            <i class="bi bi-trash3-fill"></i> Hapus Akun Permanen
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <!-- [UPDATE: MODAL HAPUS AKUN PREMIUM] -->
            <!-- Fungsi: Membuat tampilan peringatan lebih elegan dan serius -->
            <div class="p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-red-600"></div>

                <h2
                    class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 mt-2"
                >
                    <i class="bi bi-shield-x text-red-600 text-2xl"></i> Konfirmasi Penghapusan
                </h2>

                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 font-medium">
                    Apakah Anda yakin ingin menghapus akun ini? Masukkan kata sandi Anda untuk
                    mengonfirmasi.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="KATA SANDI"
                        class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                    />

                    <div class="relative group">
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                        >
                            <i
                                class="bi bi-shield-lock-fill text-gray-400 group-focus-within:text-red-500 transition-colors duration-300"
                            ></i>
                        </div>
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showModalPassword ? 'text' : 'password'"
                            class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-red-500 focus:ring-4 focus:ring-red-500/20 h-12 transition-all pl-12 pr-12 peer"
                            placeholder="Masukkan kata sandi..."
                            @keyup.enter="deleteUser"
                        />
                        <button
                            type="button"
                            @click="showModalPassword = !showModalPassword"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                        >
                            <i
                                :class="
                                    showModalPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'
                                "
                            ></i>
                        </button>
                    </div>

                    <InputError
                        :message="form.errors.password"
                        class="mt-2 text-red-600 font-medium"
                    />
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <SecondaryButton
                        @click="closeModal"
                        class="rounded-full h-11 px-6 font-bold text-sm"
                    >
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="w-full md:w-auto flex justify-center items-center gap-2 rounded-full h-11 px-6 text-sm bg-gradient-to-r from-red-600 to-red-700 text-white shadow-[0_8px_20px_-6px_rgba(220,38,38,0.5)] hover:from-red-500 hover:to-red-600 active:scale-95 transition-all border-none"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        <i class="bi bi-trash-fill"></i> Hapus Akun
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
