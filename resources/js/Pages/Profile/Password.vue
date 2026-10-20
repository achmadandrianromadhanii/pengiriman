<!-- =========================================================================
     HALAMAN UBAH PASSWORD (MOBILE)
     =========================================================================
     Fungsi: Form ubah password dengan validasi, strength meter visual,
     show/hide toggle, dan animasi sukses tanpa reload halaman.
     ========================================================================= -->
<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Eye,
    EyeOff,
    LockKeyhole,
    CheckCircle2,
    TriangleAlert
} from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

// ── Form State ───────────────────────────────────────────────────────────
const form = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const showOld = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const isSubmitting = ref(false);
const success = ref(false);
const errors = ref({});

// ── Submit Form ──────────────────────────────────────────────────────────
function submitPassword() {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    errors.value = {};

    router.patch(route('profile.update'), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            success.value = true;
            form.value = { current_password: '', password: '', password_confirmation: '' };
            setTimeout(() => { success.value = false; }, 3000);
        },
        onError: (errs) => {
            isSubmitting.value = false;
            errors.value = errs;
        }
    });
}

function goBack() {
    window.history.back();
}
</script>

<template>
    <Head :title="t('menu.changePassword')" />
    
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans relative overflow-x-hidden">
        
        <!-- ============================================================ -->
        <!-- BACKGROUND PATTERN & SEMI-CIRCLE (DEEP BLUE -> INDIGO)       -->
        <!-- ============================================================ -->
        <div class="absolute top-0 left-0 w-full h-[280px] z-0 overflow-hidden rounded-b-[40px] bg-gradient-to-br from-[#1E3A8A] via-[#2563EB] to-[#4F46E5] shadow-lg">
            <!-- Pattern 1: Curved Horizontal Lines -->
            <svg class="absolute inset-0 w-full h-full opacity-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120C320 180 640 60 960 120C1280 180 1440 120 1440 120V0H0V120Z" fill="url(#paint0_linear)"/>
                <path d="M0 240C400 320 800 160 1200 240C1360 272 1440 240 1440 240V0H0V240Z" fill="url(#paint1_linear)"/>
                <defs>
                    <linearGradient id="paint0_linear" x1="720" y1="0" x2="720" y2="150" gradientUnits="userSpaceOnUse">
                        <stop stop-color="white" stop-opacity="0.8"/>
                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="paint1_linear" x1="720" y1="0" x2="720" y2="280" gradientUnits="userSpaceOnUse">
                        <stop stop-color="white" stop-opacity="0.5"/>
                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                    </linearGradient>
                </defs>
            </svg>
            <!-- Pattern 2: Diagonal Lines -->
            <div class="absolute inset-0 opacity-[0.05]" style="background-image: repeating-linear-gradient(45deg, #ffffff 0, #ffffff 1px, transparent 1px, transparent 10px);"></div>
        </div>

        <!-- ============================================================ -->
        <!-- HEADER                                                       -->
        <!-- ============================================================ -->
        <header class="relative z-10 pt-6 pb-4 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md active:bg-white/20 transition-colors outline-none" aria-label="Kembali">
                    <ChevronLeft :size="24" stroke-width="2.5" />
                </button>
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight">{{ t('menu.changePassword') }}</h1>
                    <p class="text-[12px] text-white/80 font-medium">Amankan akun Anda</p>
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- FORM CONTENT                                                 -->
        <!-- ============================================================ -->
        <main class="relative z-10 px-6 pt-4 pb-12 flex-1">
            <div class="bg-white dark:bg-[#11131A] rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.06)] dark:border dark:border-gray-800/60 mt-4">
                <form @submit.prevent="submitPassword" class="space-y-6">
                    
                    <!-- SUCCESS MESSAGE -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="success" class="flex items-center gap-3 p-4 rounded-2xl bg-green-50 dark:bg-green-500/10 border border-green-100 dark:border-green-900/30">
                            <CheckCircle2 :size="20" class="text-green-500" />
                            <p class="text-[13px] font-semibold text-green-700 dark:text-green-400">Password berhasil diperbarui!</p>
                        </div>
                    </transition>

                    <!-- CURRENT PASSWORD -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-[13px] font-bold text-slate-700 dark:text-gray-300">
                            <LockKeyhole :size="16" class="text-blue-500" />
                            Password Saat Ini
                        </label>
                        <div class="relative group">
                            <input
                                :type="showOld ? 'text' : 'password'"
                                v-model="form.current_password"
                                class="w-full bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 rounded-2xl h-14 pl-5 pr-12 text-[14px] font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder-slate-400"
                                placeholder="•••••••••••••"
                                required
                            />
                            <button type="button" @click="showOld = !showOld" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 active:scale-90 transition-transform">
                                <Eye v-if="!showOld" :size="20" />
                                <EyeOff v-else :size="20" />
                            </button>
                        </div>
                        <p v-if="errors.current_password" class="text-red-500 text-[12px] font-medium flex items-center gap-1 mt-1">
                            <TriangleAlert :size="12" /> {{ errors.current_password }}
                        </p>
                    </div>

                    <!-- NEW PASSWORD -->
                    <div class="space-y-2 pt-2">
                        <label class="flex items-center gap-2 text-[13px] font-bold text-slate-700 dark:text-gray-300">
                            <LockKeyhole :size="16" class="text-blue-500" />
                            Password Baru
                        </label>
                        <div class="relative group">
                            <input
                                :type="showNew ? 'text' : 'password'"
                                v-model="form.password"
                                class="w-full bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 rounded-2xl h-14 pl-5 pr-12 text-[14px] font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder-slate-400"
                                placeholder="•••••••••••••"
                                required
                            />
                            <button type="button" @click="showNew = !showNew" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 active:scale-90 transition-transform">
                                <Eye v-if="!showNew" :size="20" />
                                <EyeOff v-else :size="20" />
                            </button>
                        </div>
                        <p v-if="errors.password" class="text-red-500 text-[12px] font-medium flex items-center gap-1 mt-1">
                            <TriangleAlert :size="12" /> {{ errors.password }}
                        </p>
                        
                        <!-- Validation Indicators -->
                        <div class="mt-3 space-y-2 px-1">
                            <div class="flex items-center gap-2 text-[11px] font-medium" :class="form.password.length >= 8 ? 'text-green-500' : 'text-slate-400'">
                                <CheckCircle2 :size="14" /> Minimal 8 karakter
                            </div>
                            <div class="flex items-center gap-2 text-[11px] font-medium" :class="/[A-Z]/.test(form.password) ? 'text-green-500' : 'text-slate-400'">
                                <CheckCircle2 :size="14" /> Ada huruf besar
                            </div>
                            <div class="flex items-center gap-2 text-[11px] font-medium" :class="/[0-9]/.test(form.password) ? 'text-green-500' : 'text-slate-400'">
                                <CheckCircle2 :size="14" /> Ada angka
                            </div>
                        </div>
                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div class="space-y-2 pt-2">
                        <label class="flex items-center gap-2 text-[13px] font-bold text-slate-700 dark:text-gray-300">
                            <LockKeyhole :size="16" class="text-blue-500" />
                            Konfirmasi Password
                        </label>
                        <div class="relative group">
                            <input
                                :type="showConfirm ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                class="w-full bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 rounded-2xl h-14 pl-5 pr-12 text-[14px] font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder-slate-400"
                                placeholder="•••••••••••••"
                                required
                            />
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 active:scale-90 transition-transform">
                                <Eye v-if="!showConfirm" :size="20" />
                                <EyeOff v-else :size="20" />
                            </button>
                        </div>
                        <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="text-red-500 text-[12px] font-medium flex items-center gap-1 mt-1">
                            <TriangleAlert :size="12" /> Password tidak sama
                        </p>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="isSubmitting || (form.password !== form.password_confirmation && form.password_confirmation !== '')"
                            class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-[14px] tracking-wide shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] active:scale-[0.98] transition-all disabled:opacity-50 disabled:active:scale-100"
                        >
                            <span v-if="isSubmitting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else>Ubah Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
