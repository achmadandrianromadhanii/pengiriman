<!-- =========================================================================
     HALAMAN UBAH PASSWORD (MOBILE)
     =========================================================================
     Fungsi: Form ubah password dengan validasi, strength meter visual,
     show/hide toggle, dan animasi sukses tanpa reload halaman.
     Cara Kerja: Menggunakan Inertia router.patch() ke route profile.update
     yang sudah ada di backend (ProfileController@update).
     ========================================================================= -->
<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Eye,
    EyeOff,
    KeyRound,
    Lock,
    CheckCircle2,
    ShieldCheck
} from 'lucide-vue-next';

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

// ── Password Strength Meter ──────────────────────────────────────────────
// Cara Kerja: Menghitung kekuatan berdasarkan panjang, huruf besar/kecil,
// angka, dan simbol. Skala 0-4 (Sangat Lemah → Sangat Kuat).
const strength = computed(() => {
    const pw = form.value.password;
    if (!pw) return 0;
    let score = 0;
    if (pw.length >= 6) score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 4);
});

const strengthLabel = computed(() => {
    const labels = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
    return labels[strength.value] || '';
});

const strengthColor = computed(() => {
    const colors = ['', 'bg-red-500', 'bg-orange-500', 'bg-blue-500', 'bg-emerald-500'];
    return colors[strength.value] || '';
});

const strengthTextColor = computed(() => {
    const colors = ['', 'text-red-500', 'text-orange-500', 'text-blue-500', 'text-emerald-500'];
    return colors[strength.value] || '';
});

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
    <Head title="Ubah Password" />
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans pb-10">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-[#0A1128]/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center justify-between px-4 h-16">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Kembali">
                        <ChevronLeft :size="24" class="text-slate-700 dark:text-gray-200" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Ubah Password</h1>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-5 pt-8 space-y-6">
            <!-- Icon Header -->
            <div class="flex flex-col items-center justify-center text-center mb-2">
                <div class="w-20 h-20 rounded-[24px] bg-gradient-to-br from-[#2349F5] to-[#886BFF] flex items-center justify-center shadow-lg shadow-blue-500/20 mb-4">
                    <Lock :size="36" stroke-width="2" class="text-white" />
                </div>
                <h2 class="text-xl font-black tracking-tight">Ganti Password</h2>
                <p class="text-[12px] text-slate-400 dark:text-gray-500 mt-1 font-medium">Pastikan password baru Anda kuat dan unik.</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 space-y-5">
                <!-- Password Lama -->
                <div>
                    <label class="text-[12px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 block">Password Lama</label>
                    <div class="relative">
                        <input
                            v-model="form.current_password"
                            :type="showOld ? 'text' : 'password'"
                            class="w-full h-12 px-4 pr-12 rounded-[16px] bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 text-[14px] font-medium text-slate-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-[#2349F5]/30 focus:border-[#2349F5] transition-all placeholder:text-slate-300 dark:placeholder:text-gray-600"
                            placeholder="Masukkan password lama"
                        />
                        <button @click="showOld = !showOld" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 outline-none">
                            <Eye v-if="!showOld" :size="18" />
                            <EyeOff v-else :size="18" />
                        </button>
                    </div>
                    <p v-if="errors.current_password" class="text-red-500 text-[11px] mt-1.5 font-semibold">{{ errors.current_password }}</p>
                </div>

                <!-- Password Baru -->
                <div>
                    <label class="text-[12px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 block">Password Baru</label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showNew ? 'text' : 'password'"
                            class="w-full h-12 px-4 pr-12 rounded-[16px] bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 text-[14px] font-medium text-slate-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-[#2349F5]/30 focus:border-[#2349F5] transition-all placeholder:text-slate-300 dark:placeholder:text-gray-600"
                            placeholder="Masukkan password baru"
                        />
                        <button @click="showNew = !showNew" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 outline-none">
                            <Eye v-if="!showNew" :size="18" />
                            <EyeOff v-else :size="18" />
                        </button>
                    </div>
                    <p v-if="errors.password" class="text-red-500 text-[11px] mt-1.5 font-semibold">{{ errors.password }}</p>

                    <!-- Strength Meter -->
                    <div v-if="form.password" class="mt-3 space-y-1.5">
                        <div class="flex gap-1">
                            <div v-for="i in 4" :key="i" class="flex-1 h-1.5 rounded-full transition-all duration-300" :class="i <= strength ? strengthColor : 'bg-slate-200 dark:bg-gray-700'"></div>
                        </div>
                        <p class="text-[11px] font-bold" :class="strengthTextColor">{{ strengthLabel }}</p>
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label class="text-[12px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 block">Konfirmasi Password</label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            class="w-full h-12 px-4 pr-12 rounded-[16px] bg-slate-50 dark:bg-gray-800/50 border border-slate-200 dark:border-gray-700 text-[14px] font-medium text-slate-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-[#2349F5]/30 focus:border-[#2349F5] transition-all placeholder:text-slate-300 dark:placeholder:text-gray-600"
                            placeholder="Ulangi password baru"
                        />
                        <button @click="showConfirm = !showConfirm" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 outline-none">
                            <Eye v-if="!showConfirm" :size="18" />
                            <EyeOff v-else :size="18" />
                        </button>
                    </div>
                    <p v-if="errors.password_confirmation" class="text-red-500 text-[11px] mt-1.5 font-semibold">{{ errors.password_confirmation }}</p>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                @click="submitPassword"
                :disabled="isSubmitting || !form.current_password || !form.password || !form.password_confirmation"
                class="w-full h-14 rounded-[20px] flex items-center justify-center font-bold text-[15px] transition-all duration-300 outline-none"
                :class="(!form.current_password || !form.password || !form.password_confirmation) ? 'bg-slate-200 dark:bg-gray-800 text-slate-400 dark:text-gray-500' : 'bg-[#2349F5] dark:bg-[#886BFF] text-white active:scale-[0.98] shadow-lg shadow-blue-500/30'"
            >
                <template v-if="isSubmitting">
                    <RefreshCcw :size="18" stroke-width="2.5" class="animate-spin mr-2" /> Menyimpan...
                </template>
                <template v-else>
                    <ShieldCheck :size="18" stroke-width="2.5" class="mr-2" /> Simpan Password Baru
                </template>
            </button>

            <!-- Toast -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-y-4 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-4 opacity-0"
            >
                <div v-if="success" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Password berhasil diubah!
                </div>
            </transition>
        </main>
    </div>
</template>
