/**
 * =========================================================================
 * useI18n — COMPOSABLE UNTUK SISTEM INTERNASIONALISASI (i18n)
 * =========================================================================
 * Lokasi  : resources/js/composables/useI18n.js
 * Fungsi  : Menyediakan fungsi t() untuk mengambil translation key,
 *           locale reactive, dan fungsi setLocale() untuk ganti bahasa.
 *
 * Cara Kerja:
 *   1. currentLocale = reactive ref yang menyimpan code bahasa aktif
 *   2. messages = reactive ref yang menyimpan object translation aktif
 *   3. t(key) = fungsi yang menyelami nested object menggunakan dot notation
 *               contoh: t('nav.home') → messages.nav.home → 'Home'
 *   4. setLocale(localeCode) = ganti bahasa:
 *      - Lazy-load file locale yang diperlukan
 *      - Cache bundle yang sudah dimuat agar tidak download ulang
 *      - Merge dengan fallback (id-ID) jika ada key yang kosong
 *      - Update document direction (ltr/rtl)
 *      - Simpan ke localStorage
 *   5. initI18n() = Dipanggil saat app startup, membaca preferensi tersimpan
 *
 * Persistensi:
 *   - Disimpan di localStorage key 'app_locale'
 *   - Bertahan setelah refresh, close browser, reopen PWA
 *
 * Fallback:
 *   - Jika translation key tidak ditemukan → fallback ke id-ID
 *   - Jika id-ID juga tidak punya → return key itu sendiri (bukan undefined)
 *
 * RTL Support:
 *   - Otomatis set document.documentElement.dir = 'rtl' untuk bahasa RTL
 *   - Kembali ke 'ltr' saat ganti ke bahasa non-RTL
 *
 * Bundle Caching:
 *   - Setelah bundle dimuat via lazy-load, disimpan di memory cache
 *   - Pemilihan kedua kalinya menggunakan cache (instant, tanpa network)
 * =========================================================================
 */

import { ref, readonly } from 'vue';
import { DEFAULT_LOCALE, findLanguage } from '@/i18n/registry.js';
import fallbackMessages from '@/i18n/locales/id-ID.js';

// ── State Global (singleton pattern) ──────────────────────────────────
// Fungsi: Satu instance shared di seluruh aplikasi via composable
const currentLocale = ref(DEFAULT_LOCALE);
const messages = ref(fallbackMessages);
const isLoading = ref(false);

// ── Bundle Cache ──────────────────────────────────────────────────────
// Fungsi: Menyimpan bundle yang sudah pernah dimuat agar tidak download ulang
// Cara Kerja: Key = locale code, Value = translation object
const bundleCache = new Map();
bundleCache.set('id-ID', fallbackMessages); // id-ID sudah dimuat secara statis

/**
 * Fungsi: Menyelami nested object menggunakan dot-separated key.
 * Contoh: resolve({ nav: { home: 'Home' } }, 'nav.home') → 'Home'
 * Return: Nilai string yang ditemukan, atau null jika tidak ada.
 */
function resolve(obj, key) {
    if (!obj || !key) return null;
    const parts = key.split('.');
    let current = obj;
    for (const part of parts) {
        if (current === null || current === undefined) return null;
        current = current[part];
    }
    return typeof current === 'string' ? current : null;
}

/**
 * Fungsi: Deep merge dua object — source menimpa target.
 * Cara Kerja: Rekursif menggabungkan nested object.
 *             Key dari source menimpa target.
 *             Key dari target yang tidak ada di source tetap dipertahankan.
 */
function deepMerge(target, source) {
    const result = { ...target };
    for (const key of Object.keys(source)) {
        if (
            source[key] &&
            typeof source[key] === 'object' &&
            !Array.isArray(source[key]) &&
            target[key] &&
            typeof target[key] === 'object'
        ) {
            result[key] = deepMerge(target[key], source[key]);
        } else {
            result[key] = source[key];
        }
    }
    return result;
}

/**
 * =========================================================================
 * LOCALE LOADERS — Lazy-load per locale
 * =========================================================================
 * Fungsi: Mendaftarkan dynamic import() untuk setiap locale yang tersedia.
 * Cara Kerja: Vite akan memisahkan setiap file menjadi chunk terpisah.
 *             Hanya file yang dipilih user yang di-download.
 * Menambah bahasa baru: Buat file locale lalu tambahkan entry di sini.
 * =========================================================================
 */
const localeLoaders = {
    'id-ID': () => import('@/i18n/locales/id-ID.js'),
    'en-US': () => import('@/i18n/locales/en-US.js'),
    'de-DE': () => import('@/i18n/locales/de-DE.js'),
    'fr-FR': () => import('@/i18n/locales/fr-FR.js'),
    'es-ES': () => import('@/i18n/locales/es-ES.js'),
    'pt-BR': () => import('@/i18n/locales/pt-BR.js'),
    'it-IT': () => import('@/i18n/locales/it-IT.js'),
    'nl-NL': () => import('@/i18n/locales/nl-NL.js'),
    'pl-PL': () => import('@/i18n/locales/pl-PL.js'),
    'tr-TR': () => import('@/i18n/locales/tr-TR.js'),
    'ru-RU': () => import('@/i18n/locales/ru-RU.js'),
    'ms-MY': () => import('@/i18n/locales/ms-MY.js'),
    'th-TH': () => import('@/i18n/locales/th-TH.js'),
    'vi-VN': () => import('@/i18n/locales/vi-VN.js'),
    'ja-JP': () => import('@/i18n/locales/ja-JP.js'),
    'ko-KR': () => import('@/i18n/locales/ko-KR.js'),
    'zh-CN': () => import('@/i18n/locales/zh-CN.js'),
    'zh-TW': () => import('@/i18n/locales/zh-TW.js'),
    'ar-SA': () => import('@/i18n/locales/ar-SA.js'),
    'hi-IN': () => import('@/i18n/locales/hi-IN.js'),
    'sw-KE': () => import('@/i18n/locales/sw-KE.js'),
};

/**
 * Composable useI18n — digunakan di seluruh komponen Vue
 */
export function useI18n() {

    /**
     * t(key) — Translation function
     * @param {string} key - Dot-separated key, contoh: 'nav.home'
     * @returns {string} - Terjemahan yang ditemukan, atau key itu sendiri
     *
     * Cara Kerja:
     *   1. Cari key di messages aktif (locale saat ini)
     *   2. Jika tidak ditemukan, cari di fallback (id-ID)
     *   3. Jika tetap tidak ditemukan, return key apa adanya
     */
    function t(key) {
        const found = resolve(messages.value, key);
        if (found !== null) return found;

        // Fallback ke id-ID jika key tidak ada di locale aktif
        const fallback = resolve(fallbackMessages, key);
        if (fallback !== null) return fallback;

        // Last resort: return key itu sendiri (bukan undefined/null/blank)
        return key;
    }

    /**
     * setLocale(localeCode) — Ganti bahasa aktif
     * @param {string} localeCode - Locale code dari registry (contoh: 'en-US')
     *
     * Cara Kerja:
     *   1. Cek apakah locale sama dengan yang aktif → skip jika ya
     *   2. Cek cache → gunakan jika sudah pernah dimuat
     *   3. Jika belum, lazy-load file locale via dynamic import
     *   4. Deep merge dengan fallback agar tidak ada key kosong
     *   5. Simpan ke cache untuk pemakaian berikutnya
     *   6. Update state reactive
     *   7. Update document.dir untuk RTL support
     *   8. Simpan ke localStorage
     *   9. Jika gagal → pertahankan locale sebelumnya, log warning
     */
    async function setLocale(localeCode) {
        if (localeCode === currentLocale.value) return;

        isLoading.value = true;
        try {
            const langInfo = findLanguage(localeCode);
            let loadedMessages;

            // Cek cache terlebih dahulu (instant tanpa network)
            if (bundleCache.has(localeCode)) {
                loadedMessages = bundleCache.get(localeCode);
            } else if (localeLoaders[localeCode]) {
                // Lazy-load dari file dan simpan ke cache
                const mod = await localeLoaders[localeCode]();
                loadedMessages = mod.default;
                bundleCache.set(localeCode, loadedMessages);
            }

            if (loadedMessages) {
                // Deep merge: fallback (id-ID) sebagai base, locale baru menimpa
                messages.value = deepMerge(fallbackMessages, loadedMessages);
            } else {
                // Locale belum memiliki file translation → gunakan fallback
                messages.value = { ...fallbackMessages };
            }

            currentLocale.value = localeCode;

            // RTL: otomatis set direction berdasarkan metadata registry
            document.documentElement.dir = langInfo?.direction || 'ltr';

            // Persist pilihan ke localStorage
            localStorage.setItem('app_locale', localeCode);

        } catch (err) {
            // Jika gagal load, tetap di locale sekarang (tidak crash)
            console.warn('[i18n] Failed to load locale:', localeCode, err);
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * initI18n() — Inisialisasi saat app startup
     * Cara Kerja:
     *   - Baca preferensi dari localStorage
     *   - Jika ada locale tersimpan, load locale tersebut
     *   - Jika tidak ada atau invalid, gunakan default (id-ID)
     */
    async function initI18n() {
        const saved = localStorage.getItem('app_locale');
        if (saved && saved !== DEFAULT_LOCALE) {
            await setLocale(saved);
        }
    }

    return {
        locale: readonly(currentLocale),
        isLoading: readonly(isLoading),
        t,
        setLocale,
        initI18n,
    };
}
