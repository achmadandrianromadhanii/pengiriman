<script setup>
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { Link, useForm, usePage } from '@inertiajs/vue3';

    defineProps({
        mustVerifyEmail: {
            type: Boolean,
        },
        status: {
            type: String,
        },
    });

    const user = usePage().props.auth.user;

    // =========================================================================
    // INISIALISASI FORM
    // =========================================================================
    // Menggunakan nilai fallback (|| '') untuk memastikan bahwa tipe datanya
    // selalu string yang valid, bukan `undefined` atau `null`.
    // Ini mencegah error/warning "Invalid prop: type check failed" di konsol Vue.
    const form = useForm({
        name: user?.name || '',
        email: user?.email || '',
    });
</script>

<template>
    <section>
        <header>
            <!-- [UPDATE: JUDUL PREMIUM] -->
            <!-- Fungsi: Menambahkan ikon person-gear untuk identitas visual yang lebih profesional -->
            <h2
                class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 tracking-tight"
            >
                <i class="bi bi-person-gear text-blue-600"></i> Profile Information
            </h2>

            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 font-medium">
                Perbarui informasi profil dan alamat email akun Anda.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-8">
            <!-- [UPDATE: INPUT NAMA DENGAN IKON] -->
            <!-- Fungsi: Input form premium dengan ikon interaktif (group-focus-within) -->
            <div>
                <InputLabel
                    for="name"
                    value="NAME"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                    >
                        <i
                            class="bi bi-person-fill text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        ></i>
                    </div>
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 h-12 transition-all pl-12 pr-4 peer"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- [UPDATE: INPUT EMAIL DENGAN IKON] -->
            <!-- Fungsi: Input form premium dengan ikon interaktif -->
            <div>
                <InputLabel
                    for="email"
                    value="EMAIL"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                    >
                        <i
                            class="bi bi-envelope-at-fill text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        ></i>
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full bg-gray-100/50 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 h-12 transition-all pl-12 pr-4 peer"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- [UPDATE: TOMBOL SIMPAN PREMIUM] -->
                <!-- Fungsi: Tombol dengan teks deskriptif dan ikon ceklis -->
                <PrimaryButton
                    :disabled="form.processing"
                    class="w-full md:w-auto flex justify-center items-center gap-2 rounded-full md:rounded-lg h-12 md:h-10 text-sm font-bold tracking-wide bg-gradient-to-r from-blue-600 to-indigo-600 border-none shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] text-white hover:from-blue-500 hover:to-indigo-500 active:scale-95 transition-all"
                >
                    <i class="bi bi-check2-circle text-lg leading-none"></i>
                    Simpan Profil
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
