<script setup>
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import { onBeforeUnmount, ref, watch } from 'vue';
    import { fireToast } from '@/lib/alert';
    import { Mail, LockKeyhole, Eye, EyeOff, Loader2, LogIn, AlertCircle } from 'lucide-vue-next';

    defineOptions({ layout: AuthLayout });

    const props = defineProps({
        loginSuccess: { type: Boolean, default: false },
    });

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const showPassword = ref(false);
    const localEmailError = ref('');
    const lastToast = ref('');

    let redirectTimer = null;

    function toast(icon, title) {
        const key = `${icon}:${title}`;
        if (lastToast.value === key) return;
        lastToast.value = key;
        fireToast(icon, title);
    }

    function isGmail(email) {
        return /@gmail\.com$/i.test((email || '').trim());
    }

    function submit() {
        localEmailError.value = '';

        if (!form.email || !isGmail(form.email)) {
            localEmailError.value = 'Email harus menggunakan @gmail.com';
            toast('warning', localEmailError.value);
            return;
        }

        form.post('/login', {
            preserveScroll: true,
            onFinish: () => {
                form.reset('password');
            },
        });
    }

    watch(
        () => form.errors,
        (errs) => {
            const msg = localEmailError.value || errs?.email || errs?.password;
            if (msg) toast('warning', msg);
        },
        { deep: true },
    );

    watch(
        () => props.loginSuccess,
        (ok) => {
            if (!ok) return;
            toast('success', 'Login berhasil! Mengalihkan ke Dashboard...');
            redirectTimer = setTimeout(() => {
                router.visit(route('dashboard'), { replace: true });
            }, 1500);
        },
        { immediate: true },
    );

    onBeforeUnmount(() => {
        if (redirectTimer) clearTimeout(redirectTimer);
    });
</script>

<template>
    <Head title="Login Premium" />

    <!-- Container Utama: Responsive & Modern SaaS Background -->
    <div
        class="min-h-[100dvh] w-full relative flex items-center justify-center bg-slate-50 dark:bg-slate-950 font-sans overflow-hidden"
    >
        <!-- Abstract Background Effects (Ringan & CSS Only) -->
        <div
            class="absolute top-[-15%] left-[-10%] w-[50%] h-[50%] rounded-full bg-blue-500/10 dark:bg-blue-600/20 blur-[100px] pointer-events-none transform-gpu"
        ></div>
        <div
            class="absolute bottom-[-15%] right-[-10%] w-[50%] h-[50%] rounded-full bg-purple-500/10 dark:bg-purple-600/20 blur-[100px] pointer-events-none transform-gpu"
        ></div>
        <div
            class="absolute top-[30%] left-[60%] w-[300px] h-[300px] rounded-full bg-indigo-400/5 dark:bg-indigo-500/10 blur-[80px] pointer-events-none transform-gpu"
        ></div>

        <!-- Login Card -->
        <div class="relative z-10 w-full max-w-[520px] px-5 sm:px-0">
            <div
                class="login-card-anim bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xl border border-white/50 dark:border-slate-700/50 shadow-[0_24px_60px_-15px_rgba(0,0,0,0.05)] dark:shadow-[0_24px_60px_-15px_rgba(0,0,0,0.2)] rounded-[28px] p-7 sm:p-10 md:p-12 transition-transform duration-300 hover:-translate-y-1"
            >
                <!-- Logo & Title -->
                <div class="text-center flex flex-col items-center mb-8">
                    <img
                        src="/images/logo-softsend-hd.png"
                        alt="SoftSend Logo"
                        class="w-[100px] sm:w-[120px] h-auto object-contain drop-shadow-md hover:scale-105 transition-transform duration-300 ease-out"
                        fetchpriority="high"
                    />

                    <h1
                        class="mt-6 text-2xl sm:text-[28px] font-bold text-slate-900 dark:text-white tracking-tight"
                    >
                        Welcome Back
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Masuk ke akun administrator Anda
                    </p>
                </div>

                <!-- Form -->
                <form class="space-y-6" @submit.prevent="submit">
                    <!-- Email Input -->
                    <div class="relative w-full group">
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-500 transition-colors duration-300"
                            >
                                <Mail class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                id="email"
                                autocomplete="username"
                                placeholder="Alamat Email"
                                required
                                class="block w-full h-[54px] pl-11 pr-4 bg-white/60 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-[16px] text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15 transition-all duration-300 shadow-sm hover:border-slate-300 dark:hover:border-slate-600"
                            />
                        </div>
                        <Transition name="fade-slide">
                            <div
                                v-if="localEmailError || form.errors.email"
                                class="absolute -bottom-5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ localEmailError || form.errors.email }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- Password Input -->
                    <div class="relative w-full group">
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-500 transition-colors duration-300"
                            >
                                <LockKeyhole class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                autocomplete="current-password"
                                placeholder="Kata Sandi"
                                required
                                class="block w-full h-[54px] pl-11 pr-12 bg-white/60 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-[16px] text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15 transition-all duration-300 shadow-sm hover:border-slate-300 dark:hover:border-slate-600"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-blue-600 transition-colors focus:outline-none"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="w-5 h-5" />
                                <Eye v-else class="w-5 h-5" />
                            </button>
                        </div>
                        <Transition name="fade-slide">
                            <div
                                v-if="form.errors.password"
                                class="absolute -bottom-5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ form.errors.password }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- Remember Me (Custom Switch) -->
                    <div class="pt-1 flex items-center">
                        <label
                            class="flex items-center gap-3 cursor-pointer group w-fit select-none"
                        >
                            <div class="relative flex items-center">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div
                                    class="w-10 h-5 bg-slate-200 dark:bg-slate-700 rounded-full peer-focus:ring-2 peer-focus:ring-blue-500/20 peer-checked:bg-blue-600 transition-colors duration-300 shadow-inner"
                                ></div>
                                <div
                                    class="absolute left-[2px] bg-white w-4 h-4 rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-300 ease-spring"
                                ></div>
                            </div>
                            <span
                                class="text-sm font-medium text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300"
                                >Tetap Masuk</span
                            >
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="relative w-full h-[54px] flex items-center justify-center gap-2 rounded-[16px] text-white font-bold text-[15px] bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-[0_8px_16px_-4px_rgba(37,99,235,0.3)] hover:shadow-[0_12px_20px_-4px_rgba(37,99,235,0.4)] transform active:scale-[0.98] transition-all duration-300 disabled:opacity-80 disabled:cursor-wait overflow-hidden group"
                        >
                            <div
                                class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"
                            ></div>
                            <span class="relative flex items-center gap-2">
                                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                                <span :class="{ 'opacity-90': form.processing }">{{
                                    form.processing ? 'Memproses...' : 'Masuk Sekarang'
                                }}</span>
                                <LogIn
                                    v-if="!form.processing"
                                    class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"
                                />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Animasi Masuk Kartu */
    @keyframes loginCardIn {
        0% {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .login-card-anim {
        animation: loginCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    /* Animasi Error Text */
    .fade-slide-enter-active,
    .fade-slide-leave-active {
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .fade-slide-enter-from,
    .fade-slide-leave-to {
        opacity: 0;
        transform: translateY(-5px);
    }

    /* Transisi Halus (Spring) */
    .ease-spring {
        transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
    }
</style>
