<script setup>
/**
 * =========================================================================
 * PAGE: Profile/DeleteAccount.vue — Premium Account Deletion Center
 * =========================================================================
 * Lokasi  : resources/js/Pages/Profile/DeleteAccount.vue
 * Route   : /profile/delete-account (profile.delete-account)
 * Menu    : Mobile Web → Menu / Jelajahi → Akun → Delete Account
 *
 * Fungsi  : Halaman premium untuk menghapus akun secara permanen.
 *           Terpisah dari halaman Profile (Identity Only).
 *
 * Fitur Utama:
 *   1. ← Back header kembali ke Menu/Jelajahi
 *   2. Semi-circle gradient full-width (no white side strips)
 *   3. Dual opposing SVG patterns (curved arcs + diagonal)
 *   4. Danger information card (compact, subtle red accent)
 *   5. Realtime date/time display (actual server/app time)
 *   6. Textarea alasan penghapusan (bukan radio/card)
 *   7. Character counter (0 / 500)
 *   8. Checkbox acknowledgement (PrimeVue-style binary)
 *   9. Delete button states (DISABLED → ACTIVE → LOADING)
 *  10. Confirmation bottom sheet (password + reason summary)
 *  11. Loading state (spinner, no fake percentages)
 *  12. Success state → logout → redirect login
 *  13. Error state → stay on modal, show error
 *  14. Audit history (5 terbaru)
 *  15. Archive popup (grouped by date, internal scroll)
 *  16. Email masking (fitri*****@gmail.com)
 *  17. Empty state (ShieldAlert icon)
 *  18. Full i18n support (all text via translation keys)
 *
 * Cara Kerja:
 *   User mengisi alasan → centang acknowledgement → klik DELETE →
 *   bottom sheet muncul → masukkan password → backend validasi →
 *   audit record dibuat → session dicabut → logout → redirect login
 *
 * Scope Lock:
 *   HANYA file ini yang diubah. TIDAK menyentuh Dashboard, Navbar,
 *   Bottom Navigation, Profile, Desktop UI, atau file lain.
 * =========================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { useI18n } from '@/composables/useI18n';
import {
    ArrowLeft,
    Trash2, TriangleAlert, ShieldAlert,
    Eye, EyeOff, ChevronRight, X,
    Clock, FileText, LockKeyhole
} from 'lucide-vue-next';

const { t } = useI18n();
const page = usePage();

defineOptions({ layout: AppLayout });

// ── Props dari Backend (ProfileController@deleteAccountPage) ─────────
// Cara Kerja: Backend mengirim 5 riwayat terbaru + arsip (skip 5, take 100)
const props = defineProps({
    recentDeletions: { type: Array, default: () => [] },
    archivedDeletions: { type: Array, default: () => [] },
});

// ── Form State (Inertia useForm untuk auto-reset & error handling) ───
// Cara Kerja: useForm menyediakan reactive form + processing state + errors
const form = useForm({
    reason: '',
    password: '',
});

// ── UI State ─────────────────────────────────────────────────────────
const acknowledgement = ref(false);       // Checkbox acknowledgement
const showArchivePopup = ref(false);       // Archive popup visibility
const confirmingUserDeletion = ref(false); // Confirmation modal visibility
const passwordInput = ref(null);           // Ref untuk focus password field
const showPassword = ref(false);           // Toggle show/hide password
const deleteSuccess = ref(false);          // Success state setelah deletion

// ── Batas karakter alasan (sinkron dengan backend max:500) ───────────
const REASON_MAX = 500;

// ── Realtime Clock ───────────────────────────────────────────────────
// Fungsi: Menampilkan tanggal dan waktu aktual secara realtime.
// Cara Kerja: setInterval setiap 1 detik memperbarui currentTime ref.
//             Intl.DateTimeFormat memformat sesuai locale.
const currentTime = ref(new Date());
let clockInterval = null;
onMounted(() => {
    clockInterval = setInterval(() => { currentTime.value = new Date(); }, 1000);
});
onUnmounted(() => { clearInterval(clockInterval); });

// Format: "Thursday, 27 August 2026" (sesuai Design.MD §10)
const formattedDate = computed(() => {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(currentTime.value);
});

// Format: "11:56 WIB"
const formattedTime = computed(() => {
    const h = currentTime.value.getHours().toString().padStart(2, '0');
    const m = currentTime.value.getMinutes().toString().padStart(2, '0');
    return `${h}:${m} WIB`;
});

// ── Validasi Reason ──────────────────────────────────────────────────
// Fungsi: Cek apakah alasan sudah diisi (min 1 karakter non-spasi)
const isReasonValid = computed(() => form.reason.trim().length > 0);

// Fungsi: Gabungan validasi — checkbox DAN reason harus valid
const canSubmit = computed(() => acknowledgement.value && isReasonValid.value);

// ── Back Navigation ──────────────────────────────────────────────────
// Fungsi: Kembali ke halaman Menu / Jelajahi / Akun
// Cara Kerja: Menggunakan window.history.back() (reuse existing pattern)
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
}

// ── Confirmation Bottom Sheet ────────────────────────────────────────
// Fungsi: Membuka modal konfirmasi dan fokus ke field password.
// Cara Kerja: Set state → delay 250ms → focus password input
const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
};

// ── Delete Transaction ───────────────────────────────────────────────
// Fungsi: Mengirim request POST ke backend dengan password + reason.
// Cara Kerja:
//   1. form.post() → backend validates password (current_password rule)
//   2. Jika valid → buat audit record → logout → hapus akun → redirect
//   3. Jika gagal → tampilkan error di modal, user tetap di halaman
//   4. onFinish → reset password field (keamanan)
const deleteUser = () => {
    form.post(route('profile.delete-account.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            deleteSuccess.value = true;
            // Backend handles redirect via Redirect::to('/')
        },
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset('password'),
    });
};

// ── Close Modal ──────────────────────────────────────────────────────
// Fungsi: Menutup modal konfirmasi dan membersihkan state
const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset('password');
    form.clearErrors();
};

// ── Format Helper: Tanggal audit history ─────────────────────────────
// Fungsi: Convert ISO date string ke format "Thursday, 27 August 2026"
const formatDate = (dateString) => {
    return new Intl.DateTimeFormat('en-US', {
        day: 'numeric', month: 'long', year: 'numeric'
    }).format(new Date(dateString));
};

// ── Format Helper: Waktu audit history ───────────────────────────────
// Fungsi: Convert ISO date string ke format "11:56 WIB"
const formatTime = (dateString) => {
    const d = new Date(dateString);
    return `${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')} WIB`;
};

// ── Masking Email ────────────────────────────────────────────────────
// Fungsi: Menyembunyikan sebagian email untuk keamanan UI
// Contoh: fitri*****@gmail.com
const maskEmail = (email) => {
    if (!email) return '';
    const [name, domain] = email.split('@');
    if (!name || !domain) return email;
    return name.substring(0, Math.min(4, name.length)) + '*****@' + domain;
};

// ── Archive: Grouping by date ────────────────────────────────────────
// Fungsi: Mengelompokkan arsip berdasarkan tanggal untuk batch display
// Cara Kerja: Loop semua arsip → buat key tanggal → push ke group
const groupedArchives = computed(() => {
    const groups = {};
    props.archivedDeletions.forEach(item => {
        const d = new Date(item.created_at);
        const key = new Intl.DateTimeFormat('en-US', {
            day: 'numeric', month: 'long', year: 'numeric'
        }).format(d);
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
    });
    return groups;
});
</script>

<template>
    <Head :title="t('accountDelete.title')" />

    <div class="da-page">

        <!-- ============================================================
             SEMI-CIRCLE GRADIENT BACKGROUND — FULL WIDTH
             Design.MD §6: Semi-circle must reach full left/right edge.
             NO WHITE SIDE STRIPS.
             Design.MD §7: Dual opposing patterns inside.
        ============================================================ -->
        <div class="da-hero" aria-hidden="true">
            <!-- Pattern A: Smooth curved horizontal arcs (Design.MD §7) -->
            <svg class="da-pattern da-pattern-a" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0,0 C30,40 70,20 100,50 L100,0 Z" fill="currentColor"/>
                <path d="M0,30 C40,80 80,40 100,90 L100,0 L0,0 Z" fill="currentColor"/>
            </svg>
            <!-- Pattern B: Opposing curved diagonal arcs (Design.MD §7) -->
            <svg class="da-pattern da-pattern-b" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                <path d="M100,40 C50,90 20,50 0,100 L0,0 L100,0 Z" fill="currentColor"/>
            </svg>
        </div>

        <!-- ============================================================
             HEADER: ← Delete Account (Design.MD §4, §5)
             Back action returns to Menu / Jelajahi / Akun.
             Icon: ArrowLeft from Lucide.
        ============================================================ -->
        <div class="da-header">
            <div class="da-header-row">
                <button @click="goBack" class="da-back-btn" :aria-label="'Back'">
                    <ArrowLeft :size="22" stroke-width="2.5" />
                </button>
                <div>
                    <h1 class="da-header-title">{{ t('accountDelete.title') }}</h1>
                    <p class="da-header-sub">{{ t('accountDelete.subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- ============================================================
             SCROLLABLE CONTENT AREA
        ============================================================ -->
        <div class="da-content">

            <!-- ========================================================
                 DANGER HEADER — 🗑 Delete Account (Design.MD §8)
                 Icon: Trash2. Description: permanent action warning.
            ======================================================== -->
            <div class="da-danger-header">
                <Trash2 class="text-red-500" :size="22" stroke-width="2.5" />
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ t('accountDelete.title') }}
                    </h2>
                    <p class="text-[13px] text-gray-500 dark:text-gray-400 leading-snug mt-0.5">
                        {{ t('accountDelete.subtitle') }}
                    </p>
                </div>
            </div>

            <!-- ========================================================
                 DANGER INFORMATION CARD (Design.MD §9)
                 Compact warning card. Subtle red accent.
                 TriangleAlert icon. Permanent deletion policy text.
            ======================================================== -->
            <div class="da-danger-card">
                <div class="da-danger-card-icon">
                    <TriangleAlert class="text-red-600 dark:text-red-400" :size="18" stroke-width="2.5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ t('accountDelete.dangerTitle') }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5 leading-relaxed">
                        {{ t('accountDelete.description') }}
                    </p>
                </div>
            </div>

            <!-- ========================================================
                 REALTIME DATE/TIME (Design.MD §10)
                 Format: Thursday, 27 August 2026 • 11:56 WIB
                 Actual application time, NOT hardcoded.
            ======================================================== -->
            <div class="da-datetime">
                <Clock :size="14" stroke-width="2.5" class="text-blue-500 shrink-0" />
                <span class="font-medium uppercase">{{ formattedDate }}</span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <span class="font-bold text-gray-700 dark:text-gray-300">{{ formattedTime }}</span>
            </div>

            <!-- ========================================================
                 DELETE REASON — Textarea (Design.MD §11)
                 ONE textarea. Label: DELETION REASON.
                 3-4 visible lines. Max 500 chars. No radio/card.
            ======================================================== -->
            <div class="da-section">
                <InputLabel
                    :value="t('accountDelete.reason')"
                    class="font-bold text-gray-600 dark:text-gray-400 text-[11px] uppercase tracking-wider mb-1.5"
                />
                <textarea
                    v-model="form.reason"
                    rows="4"
                    :maxlength="REASON_MAX"
                    :placeholder="t('accountDelete.reasonPlaceholder')"
                    class="da-textarea"
                ></textarea>
                <!-- Character Counter (Design.MD §12) -->
                <div class="flex justify-between items-center mt-1">
                    <InputError :message="form.errors.reason" />
                    <span :class="[
                        'text-xs font-medium tabular-nums ml-auto',
                        form.reason.length >= REASON_MAX ? 'text-red-500' : 'text-gray-400'
                    ]">
                        {{ form.reason.length }} / {{ REASON_MAX }}
                    </span>
                </div>
            </div>

            <!-- ========================================================
                 ACKNOWLEDGEMENT CHECKBOX (Design.MD §13)
                 PrimeVue-style binary checkbox.
                 Label: "I understand that this action is permanent..."
                 Accessible, properly associated, mobile friendly.
            ======================================================== -->
            <div class="da-section">
                <div class="da-checkbox-wrapper">
                    <div class="flex items-center gap-2.5">
                        <div
                            @click="acknowledgement = !acknowledgement"
                            :class="[
                                'da-checkbox',
                                acknowledgement ? 'da-checkbox-checked' : 'da-checkbox-unchecked'
                            ]"
                            role="checkbox"
                            :aria-checked="acknowledgement"
                            tabindex="0"
                            @keydown.space.prevent="acknowledgement = !acknowledgement"
                            @keydown.enter.prevent="acknowledgement = !acknowledgement"
                        >
                            <!-- Checkmark SVG (visible only when checked) -->
                            <svg
                                v-if="acknowledgement"
                                class="w-3 h-3 text-white"
                                viewBox="0 0 12 12"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M2.5 6L5 8.5L9.5 3.5" />
                            </svg>
                        </div>
                        <label
                            @click="acknowledgement = !acknowledgement"
                            class="text-sm text-gray-800 dark:text-gray-200 leading-snug select-none cursor-pointer"
                        >
                            {{ t('accountDelete.acknowledgement') }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 PRIMARY DELETE BUTTON (Design.MD §14, §15)
                 States: DISABLED → ACTIVE → LOADING
                 Destructive red, rounded, full width, compact height.
                 Disabled when: reason empty OR checkbox unchecked.
            ======================================================== -->
            <div class="da-section">
                <button
                    @click="confirmUserDeletion"
                    :disabled="!canSubmit"
                    :class="[
                        'da-delete-btn',
                        canSubmit
                            ? 'da-delete-btn-active'
                            : 'da-delete-btn-disabled'
                    ]"
                    :aria-label="t('accountDelete.delete')"
                >
                    <Trash2 :size="16" stroke-width="2.5" />
                    {{ t('accountDelete.delete') }}
                </button>
            </div>

            <!-- ========================================================
                 DELETION HISTORY — Max 5 newest (Design.MD §22, §23)
                 Order: Newest → Oldest.
                 View Archive link if archived records exist.
            ======================================================== -->
            <div class="da-section" v-if="props.recentDeletions.length > 0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[11px] uppercase tracking-wider font-bold text-gray-600 dark:text-gray-400 flex items-center gap-2">
                        <Clock class="text-blue-500" :size="14" stroke-width="2.5" />
                        {{ t('accountDelete.history') }}
                    </h3>
                    <button
                        v-if="props.archivedDeletions.length > 0"
                        @click="showArchivePopup = true"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-0.5"
                    >
                        {{ t('accountDelete.viewArchive') }}
                        <ChevronRight :size="14" />
                    </button>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="item in props.recentDeletions"
                        :key="item.id"
                        class="da-history-item"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ maskEmail(item.email) }}</p>
                            <span class="text-[11px] text-gray-400 font-medium shrink-0 ml-2">{{ formatTime(item.created_at) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <FileText :size="12" class="shrink-0" />
                            <span class="truncate">{{ item.reason }}</span>
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1">{{ formatDate(item.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 EMPTY STATE (Design.MD §29)
                 ShieldAlert icon + "No account deletion history."
            ======================================================== -->
            <div class="da-section da-empty" v-else>
                <ShieldAlert class="mx-auto text-gray-300 dark:text-gray-600 mb-2" :size="28" stroke-width="1.5" />
                <p class="text-sm text-gray-400 dark:text-gray-500 font-medium">{{ t('accountDelete.empty') }}</p>
            </div>
        </div>

        <!-- ============================================================
             CONFIRMATION BOTTOM SHEET / MODAL (Design.MD §16–§18)
             Reuses existing Modal component (no duplicate modal system).
             Animation: translateY(100%) → translateY(0), 220-300ms.
             Contains: reason summary + password field + actions.
        ============================================================ -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-5">
                <!-- ── Success State (Design.MD §32) ── -->
                <div v-if="deleteSuccess" class="text-center py-8">
                    <div class="w-14 h-14 mx-auto rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center mb-4">
                        <ShieldAlert class="text-green-600 dark:text-green-400" :size="28" stroke-width="2" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('accountDelete.success') }}</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ t('accountDelete.redirecting') }}</p>
                </div>

                <!-- ── Confirmation Form ── -->
                <template v-else>
                    <!-- Title (Design.MD §17) -->
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <TriangleAlert class="text-red-600" :size="20" stroke-width="2.5" />
                        {{ t('accountDelete.confirmTitle') }}
                    </h2>

                    <!-- Confirmation message -->
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        {{ t('accountDelete.confirmDesc') }}
                    </p>

                    <!-- Reason summary (Design.MD §17) -->
                    <div class="mt-4 p-3 bg-red-50/80 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-xl">
                        <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold mb-1">
                            {{ t('accountDelete.reason') }}
                        </p>
                        <p class="text-sm text-red-700 dark:text-red-400 font-medium leading-snug">{{ form.reason }}</p>
                    </div>

                    <!-- Password field (Design.MD §17, §19) -->
                    <div class="mt-5">
                        <InputLabel :for="'delete-password'" :value="t('accountDelete.password')" class="text-sm font-semibold mb-1.5" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <LockKeyhole :size="16" class="text-gray-400" />
                            </div>
                            <TextInput
                                id="delete-password"
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pl-9 pr-10"
                                :placeholder="t('accountDelete.password')"
                                @keyup.enter="deleteUser"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <Eye v-if="!showPassword" :size="18" />
                                <EyeOff v-else :size="18" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" class="mt-1.5" />
                    </div>

                    <!-- Actions (Design.MD §17) -->
                    <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                        <button
                            @click="closeModal"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center"
                        >
                            {{ t('accountDelete.cancel') }}
                        </button>
                        <button
                            @click="deleteUser"
                            :disabled="form.processing || !form.password"
                            :class="[
                                'w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-bold transition text-center flex items-center justify-center gap-2',
                                form.processing || !form.password
                                    ? 'bg-red-300 dark:bg-red-800/50 text-red-100 cursor-not-allowed'
                                    : 'bg-red-600 hover:bg-red-700 text-white shadow-md shadow-red-600/20'
                            ]"
                        >
                            <!-- Loading state (Design.MD §31) — spinner, no fake % -->
                            <template v-if="form.processing">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ t('accountDelete.processing') }}
                            </template>
                            <template v-else>
                                <Trash2 :size="14" stroke-width="2.5" />
                                {{ t('accountDelete.deleteConfirm') }}
                            </template>
                        </button>
                    </div>
                </template>
            </div>
        </Modal>

        <!-- ============================================================
             ARCHIVE POPUP (Design.MD §26, §27)
             Internal scrolling ONLY. No browser/page scrollbar.
             Grouped by date. Max 5 per batch.
        ============================================================ -->
        <Modal :show="showArchivePopup" @close="showArchivePopup = false" maxWidth="lg">
            <div class="da-archive-container">
                <!-- Header -->
                <div class="da-archive-header">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Clock class="text-blue-500" :size="18" stroke-width="2.5" />
                        {{ t('accountDelete.archive') }}
                    </h2>
                    <button
                        @click="showArchivePopup = false"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        :aria-label="'Close'"
                    >
                        <X :size="18" />
                    </button>
                </div>

                <!-- Scrollable Content (internal scroll) -->
                <div class="da-archive-body">
                    <div v-for="(items, batch) in groupedArchives" :key="batch" class="mb-5 last:mb-0">
                        <!-- Batch Date Header -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest whitespace-nowrap">{{ batch }}</span>
                            <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                        </div>

                        <!-- Archive Items -->
                        <div class="space-y-2">
                            <div
                                v-for="item in items"
                                :key="item.id"
                                class="da-history-item"
                            >
                                <div class="flex justify-between items-start mb-1">
                                    <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ maskEmail(item.email) }}</p>
                                    <span class="text-[11px] text-gray-400 font-medium shrink-0 ml-2">{{ formatTime(item.created_at) }}</span>
                                </div>
                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                    <FileText :size="12" class="shrink-0" />
                                    <span class="truncate">{{ item.reason }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
/**
 * =========================================================================
 * STYLE: Premium Account Deletion Center — Mobile-First
 * =========================================================================
 * Arsitektur CSS:
 *   .da-page         — Container utama, full-height
 *   .da-hero          — Semi-circle gradient background (Design.MD §6)
 *   .da-pattern-a/b   — Dual opposing patterns (Design.MD §7)
 *   .da-header         — ← Back header (Design.MD §4)
 *   .da-content        — Scrollable content area
 *   .da-danger-card    — Warning card (Design.MD §9)
 *   .da-checkbox       — PrimeVue-style checkbox (Design.MD §13)
 *   .da-delete-btn     — Primary delete button (Design.MD §14)
 *   .da-archive-*      — Archive popup (Design.MD §26)
 *
 * Responsive: 320px–480px safe, no overflow, no clipping (Design.MD §35)
 * =========================================================================
 */

/* ── Page Container ─────────────────────────────────────────────────── */
.da-page {
    position: relative;
    min-height: 100vh;
    max-width: 42rem;
    margin: 0 auto;
    padding-bottom: 6rem; /* clearance for bottom nav */
}

/* ── Semi-Circle Gradient Background (Design.MD §6) ─────────────────
   Full-width, no white side strips.
   Colors: Deep Navy → Royal Blue → Indigo → subtle Deep Purple */
.da-hero {
    position: absolute;
    top: 0;
    left: -1rem;     /* extend beyond content padding to fill viewport */
    right: -1rem;
    height: 200px;
    background: linear-gradient(135deg, #0f1b3d 0%, #1e3a8a 35%, #3730a3 70%, #312e81 100%);
    border-radius: 0 0 50% 50% / 0 0 50px 50px;
    overflow: hidden;
    z-index: 0;
}

/* ── Dual Pattern Layers (Design.MD §7) ──────────────────────────────
   Pattern A: Curved horizontal arcs
   Pattern B: Opposing curved/diagonal arcs
   Different directions, low opacity, visually distinguishable */
.da-pattern {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
.da-pattern-a {
    color: rgba(255, 255, 255, 0.06);
}
.da-pattern-b {
    color: rgba(255, 255, 255, 0.04);
}

/* ── Header: ← Delete Account (Design.MD §4) ────────────────────── */
.da-header {
    position: relative;
    z-index: 2;
    padding: 1rem 1.25rem 0;
}
.da-header-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.da-back-btn {
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    color: white;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    transition: background 0.2s;
    flex-shrink: 0;
}
.da-back-btn:active {
    background: rgba(255, 255, 255, 0.25);
}
.da-header-title {
    font-size: 1.125rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.025em;
}
.da-header-sub {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

/* ── Content Area ────────────────────────────────────────────────── */
.da-content {
    position: relative;
    z-index: 2;
    padding: 0 1.25rem;
    margin-top: 1.5rem;
}
.da-section {
    margin-bottom: 1rem;
}

/* ── Danger Header (Design.MD §8) ────────────────────────────────── */
.da-danger-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    padding-top: 4.5rem; /* space below semi-circle */
}

/* ── Danger Card (Design.MD §9) ──────────────────────────────────── */
.da-danger-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem;
    border-radius: 0.875rem;
    background: white;
    border: 1px solid rgba(239, 68, 68, 0.12);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    margin-bottom: 0.75rem;
}
.dark .da-danger-card {
    background: rgba(31, 41, 55, 0.8);
    border-color: rgba(127, 29, 29, 0.3);
}
.da-danger-card-icon {
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    border-radius: 0.5rem;
    background: rgba(254, 226, 226, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}
.dark .da-danger-card-icon {
    background: rgba(127, 29, 29, 0.3);
}

/* ── Realtime Date/Time (Design.MD §10) ──────────────────────────── */
.da-datetime {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.6875rem;
    color: rgb(107, 114, 128);
    padding: 0.5rem 0.75rem;
    background: rgba(249, 250, 251, 0.8);
    border-radius: 0.625rem;
    border: 1px solid rgba(229, 231, 235, 0.6);
    margin-bottom: 0.75rem;
}
.dark .da-datetime {
    color: rgb(156, 163, 175);
    background: rgba(31, 41, 55, 0.4);
    border-color: rgba(55, 65, 81, 0.5);
}

/* ── Textarea (Design.MD §11) ────────────────────────────────────── */
.da-textarea {
    width: 100%;
    border: 1px solid rgb(229, 231, 235);
    border-radius: 0.75rem;
    padding: 0.75rem;
    font-size: 0.875rem;
    resize: none;
    line-height: 1.6;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.da-textarea:focus {
    outline: none;
    border-color: rgb(239, 68, 68);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
.dark .da-textarea {
    background: rgba(31, 41, 55, 0.6);
    border-color: rgb(55, 65, 81);
    color: rgb(229, 231, 235);
}

/* ── PrimeVue-Style Checkbox (Design.MD §13) ─────────────────────── */
.da-checkbox-wrapper {
    padding: 0.75rem;
    border-radius: 0.75rem;
    background: rgb(249, 250, 251);
}
.dark .da-checkbox-wrapper {
    background: rgba(31, 41, 55, 0.4);
}
.da-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.15s ease;
}
.da-checkbox-unchecked {
    border: 2px solid rgb(209, 213, 219);
    background: white;
}
.dark .da-checkbox-unchecked {
    border-color: rgb(75, 85, 99);
    background: rgb(55, 65, 81);
}
.da-checkbox-checked {
    border: 2px solid rgb(220, 38, 38);
    background: rgb(220, 38, 38);
}
.da-checkbox:focus-visible {
    outline: 2px solid rgb(220, 38, 38);
    outline-offset: 2px;
}

/* ── Delete Button (Design.MD §14, §15) ──────────────────────────── */
.da-delete-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.75rem;
    font-weight: 700;
    font-size: 0.8125rem;
    letter-spacing: 0.025em;
    transition: all 0.2s;
}
.da-delete-btn-active {
    background: rgb(220, 38, 38);
    color: white;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
}
.da-delete-btn-active:hover {
    background: rgb(185, 28, 28);
}
.da-delete-btn-active:active {
    background: rgb(153, 27, 27);
    transform: scale(0.98);
}
.da-delete-btn-disabled {
    background: rgb(243, 244, 246);
    color: rgb(156, 163, 175);
    cursor: not-allowed;
}
.dark .da-delete-btn-disabled {
    background: rgb(31, 41, 55);
    color: rgb(107, 114, 128);
}

/* ── History Item ────────────────────────────────────────────────── */
.da-history-item {
    padding: 0.75rem;
    border-radius: 0.75rem;
    background: white;
    border: 1px solid rgba(229, 231, 235, 0.8);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.dark .da-history-item {
    background: rgba(31, 41, 55, 0.6);
    border-color: rgba(55, 65, 81, 0.5);
}

/* ── Empty State (Design.MD §29) ─────────────────────────────────── */
.da-empty {
    padding: 2rem;
    text-align: center;
    background: rgb(249, 250, 251);
    border-radius: 1rem;
    border: 1px dashed rgb(229, 231, 235);
}
.dark .da-empty {
    background: rgba(31, 41, 55, 0.2);
    border-color: rgb(55, 65, 81);
}

/* ── Archive Popup (Design.MD §26, §27) ──────────────────────────── */
.da-archive-container {
    display: flex;
    flex-direction: column;
    height: 70vh;
    max-height: 600px;
}
.da-archive-header {
    padding: 1rem;
    border-bottom: 1px solid rgb(243, 244, 246);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    position: sticky;
    top: 0;
    z-index: 10;
    border-radius: 0.5rem 0.5rem 0 0;
}
.dark .da-archive-header {
    background: rgb(31, 41, 55);
    border-bottom-color: rgb(55, 65, 81);
}
/* Internal scroll ONLY — no browser/page scrollbar (Design.MD §27) */
.da-archive-body {
    padding: 1rem;
    overflow-y: auto;
    flex: 1;
    background: rgb(249, 250, 251);
}
.dark .da-archive-body {
    background: rgb(17, 24, 39);
}
.da-archive-body::-webkit-scrollbar { width: 4px; }
.da-archive-body::-webkit-scrollbar-track { background: transparent; }
.da-archive-body::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.4);
    border-radius: 20px;
}
</style>
