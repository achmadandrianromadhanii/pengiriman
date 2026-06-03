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
            <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-8">
            <div>
                <InputLabel
                    for="name"
                    value="Name"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-gray-100 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 h-12 transition-all px-4"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel
                    for="email"
                    value="Email"
                    class="text-[10px] uppercase font-extrabold tracking-widest text-slate-500 dark:text-gray-400 mb-1.5"
                />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-gray-100 shadow-inner dark:bg-white/5 border-transparent md:border-gray-200 rounded-2xl md:rounded-lg focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 h-12 transition-all px-4"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

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
                <PrimaryButton
                    :disabled="form.processing"
                    class="w-full md:w-auto flex justify-center rounded-full md:rounded-lg h-12 md:h-10 text-sm bg-gradient-to-r from-blue-600 to-indigo-600 border-none shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] text-white hover:from-blue-500 hover:to-indigo-500 active:scale-95 transition-all"
                    >Save</PrimaryButton
                >

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
