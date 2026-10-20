<!--
  =========================================================================
  HALAMAN BAHASA — LANGUAGE CENTER (Mobile Web)
  =========================================================================
  Lokasi  : resources/js/Pages/Settings/Language.vue
  Fungsi  : Halaman pemilihan bahasa aplikasi mobile web.
             Menampilkan bahasa aktif, daftar A-Z, dan active state.

  Fitur:
    - Current language card di atas
    - Daftar bahasa dikelompokkan per huruf (A-Z section)
    - Active state dengan check icon + accent color
    - Smooth scroll saat klik huruf di alphabet index
    - Persist pilihan ke localStorage
    - RTL otomatis untuk bahasa Arab
    - Tidak memerlukan reload halaman

  Design:
    - Hero header Royal Blue → Indigo dengan pattern garis
    - White/light surface cards dengan rounded corners
    - Lucide icons (ArrowLeft, Languages, Check)
    - Safe area support
  =========================================================================
-->

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Languages, Check } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n.js';
import {
    sortedLanguages,
    getGroupedLanguages,
    findLanguage,
    DEFAULT_LOCALE,
} from '@/i18n/registry.js';

// ── i18n composable ───────────────────────────────────────────────────
const { t, locale, setLocale, isLoading } = useI18n();

// ── Grouped languages untuk A-Z sections ──────────────────────────────
// Fungsi: Mengelompokkan bahasa berdasarkan huruf pertama nativeName
const groupedLanguages = computed(() => getGroupedLanguages());

// ── Current language info ─────────────────────────────────────────────
// Fungsi: Mendapatkan metadata bahasa yang sedang aktif
const currentLang = computed(() => findLanguage(locale.value) || findLanguage(DEFAULT_LOCALE));

// ── Alphabet index letters ────────────────────────────────────────────
// Fungsi: Mengambil semua huruf yang tersedia dari grouped languages
const availableLetters = computed(() => groupedLanguages.value.map(g => g.letter));

// ── Scroll container ref ──────────────────────────────────────────────
const scrollContainer = ref(null);

/**
 * Fungsi: Mengganti bahasa aktif
 * Cara Kerja:
 *   1. Panggil setLocale() dari composable (lazy-load + persist)
 *   2. UI otomatis update karena messages reactive
 *   3. Tidak perlu reload halaman
 * @param {string} code - Language code dari registry
 */
async function selectLanguage(code) {
    if (code === locale.value) return;
    await setLocale(code);
}

/**
 * Fungsi: Smooth scroll ke section huruf tertentu
 * Cara Kerja:
 *   1. Cari element dengan id 'lang-section-{letter}'
 *   2. scrollIntoView dengan behavior smooth
 * @param {string} letter - Huruf yang dipilih (A, B, C, dst)
 */
function scrollToLetter(letter) {
    const el = document.getElementById('lang-section-' + letter);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Fungsi: Navigasi kembali ke halaman sebelumnya
 * Cara Kerja: Menggunakan window.history untuk back navigation
 */
function goBack() {
    window.history.back();
}
</script>

<template>
    <div class="lang-page">
        <!-- ============================================================ -->
        <!-- HERO HEADER — Royal Blue → Indigo gradient + pattern          -->
        <!-- ============================================================ -->
        <div class="lang-hero">
            <!-- Pattern Layer 1: Curved horizontal lines -->
            <svg class="lang-pattern-1" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0,0 C30,40 70,20 100,50 L100,0 Z" fill="currentColor"/>
                <path d="M0,30 C40,80 80,40 100,90 L100,0 L0,0 Z" fill="currentColor"/>
            </svg>

            <!-- Pattern Layer 2: Curved diagonal lines (arah berbeda) -->
            <svg class="lang-pattern-2" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                <path d="M100,40 C50,90 20,50 0,100 L0,0 L100,0 Z" fill="currentColor"/>
            </svg>

            <!-- Header content: Back button + Title -->
            <div class="lang-hero-inner">
                <div class="lang-hero-row">
                    <button @click="goBack" class="lang-back-btn" aria-label="Kembali">
                        <ArrowLeft :size="22" stroke-width="2.5" />
                    </button>
                    <div class="lang-hero-text">
                        <h1 class="lang-hero-title">{{ t('language.title') }}</h1>
                        <p class="lang-hero-sub">{{ t('language.subtitle') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- SCROLLABLE CONTENT                                            -->
        <!-- ============================================================ -->
        <div class="lang-scroll" ref="scrollContainer">

            <!-- ======================================================== -->
            <!-- CURRENT LANGUAGE CARD                                     -->
            <!-- Menampilkan bahasa yang sedang aktif saat ini              -->
            <!-- ======================================================== -->
            <div class="lang-section-label">{{ t('language.currentLanguage') }}</div>

            <div class="lang-current-card" v-if="currentLang">
                <div class="lang-current-left">
                    <span class="text-xl font-black text-gray-400 mr-2 uppercase w-10 text-center">{{ currentLang.locale.substring(0, 2) }}</span>
                    <div>
                        <div class="lang-current-name">{{ currentLang.displayName }}</div>
                        <div class="lang-current-meta">
                            <template v-if="locale === DEFAULT_LOCALE">
                                {{ currentLang.locale }} • {{ t('language.default') }}
                            </template>
                            <template v-else>
                                {{ currentLang.locale }}
                            </template>
                        </div>
                    </div>
                </div>
                <div class="lang-active-badge">
                    <Check :size="16" stroke-width="3" />
                    <span>{{ t('language.active') }}</span>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ALPHABET INDEX — A-Z navigation bar                       -->
            <!-- Klik huruf → smooth scroll ke section tersebut            -->
            <!-- ======================================================== -->
            <div class="lang-section-label">{{ t('language.allLanguages') }}</div>

            <div class="lang-alpha-bar">
                <button
                    v-for="letter in availableLetters"
                    :key="letter"
                    @click="scrollToLetter(letter)"
                    class="lang-alpha-btn"
                >
                    {{ letter }}
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- LANGUAGE LIST — Grouped by first letter (A-Z)             -->
            <!-- Setiap group memiliki header huruf dan card berisi bahasa  -->
            <!-- ======================================================== -->
            <div
                v-for="group in groupedLanguages"
                :key="group.letter"
                :id="'lang-section-' + group.letter"
                class="lang-group"
            >
                <!-- Section letter header -->
                <div class="lang-group-letter">{{ group.letter }}</div>

                <!-- Language cards container -->
                <div class="lang-group-card">
                    <button
                        v-for="(lang, idx) in group.items"
                        :key="lang.locale"
                        @click="selectLanguage(lang.locale)"
                        class="lang-item"
                        :class="{
                            'lang-item-active': locale === lang.locale,
                            'lang-item-border': idx < group.items.length - 1,
                        }"
                    >
                        <div class="lang-item-left">
                            <!-- Code ISO 2 huruf di sebelah kiri nama (seperti wireframe: ID, EN, DE) -->
                            <span class="text-sm font-black text-gray-400 mr-2 uppercase w-6 text-center">{{ lang.locale.substring(0, 2) }}</span>
                            <div>
                                <div class="lang-item-name">{{ lang.displayName }}</div>
                                <div class="lang-item-code text-[11px] text-gray-500 mt-0.5">{{ lang.locale }}</div>
                            </div>
                        </div>

                        <!-- Check icon: hanya tampil jika bahasa ini aktif -->
                        <div v-if="locale === lang.locale" class="lang-check">
                            <Check :size="18" stroke-width="3" />
                        </div>
                        <!-- Circle outline: tampil jika tidak aktif -->
                        <div v-else class="lang-circle"></div>
                    </button>
                </div>
            </div>

            <!-- Bottom safe area spacer -->
            <div class="lang-bottom-spacer"></div>
        </div>
    </div>
</template>

<style scoped>
/*
  =========================================================================
  LANGUAGE PAGE — SCOPED STYLES
  =========================================================================
  Semua style terisolasi, tidak mempengaruhi halaman lain.
  Menggunakan class prefix 'lang-' untuk menghindari konflik.
  =========================================================================
*/

/* ── Page root: fullscreen tanpa scroll body ─────────────────────── */
.lang-page {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
    overflow: hidden;
}

/* Dark mode background */
:deep(.dark) .lang-page,
.dark .lang-page {
    background: #0a0c14;
}

/* ── HERO HEADER ─────────────────────────────────────────────────── */
/* Gradient Royal Blue → Indigo dengan rounded bottom corners */
.lang-hero {
    position: relative;
    width: 100%;
    background: linear-gradient(135deg, #2349F5 0%, #6A3BFF 100%);
    border-radius: 0 0 40px 40px;
    overflow: hidden;
    flex-shrink: 0;
    padding-bottom: 48px;
    box-shadow: 0 8px 30px -10px rgba(35, 73, 245, 0.3);
}

/* Pattern 1: Garis melengkung biru transparan */
.lang-pattern-1 {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    color: #93c5fd;
    opacity: 0.18;
    pointer-events: none;
}

/* Pattern 2: Garis diagonal melengkung (arah berbeda) */
.lang-pattern-2 {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    color: #a5f3fc;
    opacity: 0.12;
    pointer-events: none;
}

/* Inner content positioning */
.lang-hero-inner {
    position: relative;
    z-index: 10;
    padding: 48px 24px 0 24px;
    padding-top: calc(env(safe-area-inset-top, 0px) + 48px);
}

.lang-hero-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

/* Back button: lingkaran semi-transparan */
.lang-back-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 150ms ease;
    -webkit-tap-highlight-color: transparent;
    backdrop-filter: blur(4px);
}

.lang-back-btn:active {
    transform: scale(0.9);
}

/* Hero title */
.lang-hero-title {
    font-size: 2rem;
    font-weight: 900;
    color: white;
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin: 0;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Hero subtitle */
.lang-hero-sub {
    font-size: 11.5px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    margin: 6px 0 0 0;
}

/* ── SCROLLABLE CONTENT ──────────────────────────────────────────── */
.lang-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 0 20px;
    margin-top: -24px;
    position: relative;
    z-index: 20;
    -webkit-overflow-scrolling: touch;
}

/* Hide scrollbar */
.lang-scroll::-webkit-scrollbar {
    display: none;
}
.lang-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* ── SECTION LABEL ───────────────────────────────────────────────── */
.lang-section-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    padding: 20px 8px 10px 8px;
}

.dark .lang-section-label {
    color: #64748b;
}

/* ── CURRENT LANGUAGE CARD ───────────────────────────────────────── */
.lang-current-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-radius: 24px;
    background: white;
    box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.06);
    border: 2px solid #2349F5;
}

.dark .lang-current-card {
    background: #11131a;
    border-color: #6A3BFF;
    box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.3);
}

.lang-current-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.lang-flag-lg {
    font-size: 32px;
    line-height: 1;
}

.lang-current-name {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.dark .lang-current-name {
    color: #f1f5f9;
}

.lang-current-meta {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin-top: 2px;
}

.dark .lang-current-meta {
    color: #94a3b8;
}

/* Active badge: check + text */
.lang-active-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(35, 73, 245, 0.1);
    color: #2349F5;
    font-size: 11px;
    font-weight: 700;
}

.dark .lang-active-badge {
    background: rgba(106, 59, 255, 0.2);
    color: #a78bfa;
}

/* ── ALPHABET INDEX BAR ──────────────────────────────────────────── */
.lang-alpha-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding: 0 4px 8px 4px;
}

.lang-alpha-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: #2349F5;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background 100ms ease;
    -webkit-tap-highlight-color: transparent;
}

.lang-alpha-btn:active {
    background: rgba(35, 73, 245, 0.1);
}

.dark .lang-alpha-btn {
    color: #818cf8;
}

.dark .lang-alpha-btn:active {
    background: rgba(129, 140, 248, 0.15);
}

/* ── LANGUAGE GROUP ──────────────────────────────────────────────── */
.lang-group {
    margin-bottom: 16px;
}

/* Section letter */
.lang-group-letter {
    font-size: 13px;
    font-weight: 800;
    color: #475569;
    padding: 4px 8px 8px 8px;
}

.dark .lang-group-letter {
    color: #94a3b8;
}

/* Group card container */
.lang-group-card {
    border-radius: 24px;
    background: white;
    box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.dark .lang-group-card {
    background: #11131a;
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.3);
}

/* ── LANGUAGE ITEM ───────────────────────────────────────────────── */
.lang-item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background 120ms ease;
    -webkit-tap-highlight-color: transparent;
    text-align: left;
}

.lang-item:active {
    background: #f8fafc;
}

.dark .lang-item:active {
    background: rgba(255, 255, 255, 0.03);
}

/* Border between items in same group */
.lang-item-border {
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.dark .lang-item-border {
    border-bottom-color: rgba(255, 255, 255, 0.04);
}

/* Active language item: subtle accent background */
.lang-item-active {
    background: rgba(35, 73, 245, 0.04);
}

.dark .lang-item-active {
    background: rgba(106, 59, 255, 0.08);
}

.lang-item-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lang-flag {
    font-size: 24px;
    line-height: 1;
}

.lang-item-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.dark .lang-item-name {
    color: #e2e8f0;
}

.lang-item-code {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    margin-top: 1px;
}

.dark .lang-item-code {
    color: #64748b;
}

/* Check icon for active language */
.lang-check {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #2349F5;
    color: white;
    animation: langCheckIn 200ms cubic-bezier(0.34, 1.2, 0.64, 1);
}

.dark .lang-check {
    background: #6A3BFF;
}

@keyframes langCheckIn {
    from { transform: scale(0.85); opacity: 0.5; }
    to { transform: scale(1); opacity: 1; }
}

/* Circle outline for inactive language */
.lang-circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
}

.dark .lang-circle {
    border-color: #475569;
}

/* ── BOTTOM SPACER ───────────────────────────────────────────────── */
.lang-bottom-spacer {
    height: 100px;
}
</style>
