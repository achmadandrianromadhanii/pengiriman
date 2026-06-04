<script setup>
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { useForm } from '@inertiajs/vue3';
    import { ref } from 'vue';

    const passwordInput = ref(null);
    const currentPasswordInput = ref(null);

    // [UPDATE: STATE UNTUK TOGGLE PASSWORD]
    // Fungsi: Mengatur visibilitas kata sandi secara reaktif
    const showCurrentPassword = ref(false);
    const showNewPassword = ref(false);
    const showConfirmPassword = ref(false);

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = () => {
        form.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: () => {
                if (form.errors.password) {
                    form.reset('password', 'password_confirmation');
                    passwordInput.value.focus();
                }
                if (form.errors.current_password) {
                    form.reset('current_password');
                    currentPasswordInput.value.focus();
                }
            },
        });
    };
</script>

<template>
    <section>
        <header>
            <!-- [UPDATE: JUDUL PREMIUM] -->
            <!-- Fungsi: Identitas visual halaman update password -->
            <h2
                class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 tracking-tight"
            >
                <i class="bi bi-key-fill text-blue-600"></i> Update Password
            </h2>

            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 font-medium">
                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-8">
            <!-- [UPDATE: INPUT KATA SANDI SAAT INI] -->
            <!-- Fungsi: Input dengan ikon dan toggle mata -->
            <div>
                <InputLabel
                    for="current_password"
                    value="CURRENT PASSWORD"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                    >
                        <i
                            class="bi bi-shield-lock-fill text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        ></i>
                    </div>
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 h-12 transition pl-12 pr-12 peer"
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        @click="showCurrentPassword = !showCurrentPassword"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                    >
                        <i
                            :class="showCurrentPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"
                        ></i>
                    </button>
                </div>

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <!-- [UPDATE: INPUT KATA SANDI BARU] -->
            <!-- Fungsi: Input dengan ikon dan toggle mata -->
            <div>
                <InputLabel
                    for="password"
                    value="NEW PASSWORD"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                    >
                        <i
                            class="bi bi-shield-lock-fill text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        ></i>
                    </div>
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showNewPassword ? 'text' : 'password'"
                        class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 h-12 transition pl-12 pr-12 peer"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showNewPassword = !showNewPassword"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                    >
                        <i :class="showNewPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
                    </button>
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <!-- [UPDATE: INPUT KONFIRMASI KATA SANDI] -->
            <!-- Fungsi: Input dengan ikon, toggle mata, dan perbaikan label agar seragam -->
            <div>
                <InputLabel
                    for="password_confirmation"
                    value="CONFIRM PASSWORD"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                    >
                        <i
                            class="bi bi-shield-lock-fill text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        ></i>
                    </div>
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 h-12 transition pl-12 pr-12 peer"
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                    >
                        <i
                            :class="showConfirmPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"
                        ></i>
                    </button>
                </div>

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <!-- [UPDATE: TOMBOL SIMPAN KATA SANDI] -->
                <!-- Fungsi: Tombol dengan teks deskriptif dan ikon ceklis -->
                <PrimaryButton
                    :disabled="form.processing"
                    class="w-full md:w-auto flex justify-center items-center gap-2 rounded-full md:rounded-lg h-12 md:h-10 text-sm font-bold tracking-wide bg-gradient-to-r from-blue-600 to-indigo-600 border-none shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] text-white hover:from-blue-500 hover:to-indigo-500 active:scale-95 transition"
                >
                    <i class="bi bi-key-fill text-lg leading-none"></i>
                    Perbarui Kata Sandi
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-green-500 flex items-center gap-1"
                    >
                        <i class="bi bi-check-circle-fill"></i> Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
