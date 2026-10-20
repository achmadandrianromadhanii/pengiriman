/**
 * =========================================================================
 * LANGUAGE REGISTRY — Single Source of Truth
 * =========================================================================
 * Lokasi  : resources/js/i18n/registry.js
 * Fungsi  : Menyimpan metadata semua bahasa yang didukung aplikasi.
 *
 * Cara Kerja:
 *   - Setiap entry memiliki: locale, displayName, nativeName, direction,
 *     enabled, translationAvailability, fallbackLocale
 *   - enabled = true → bahasa ditampilkan di Language Center
 *   - translationAvailability = true → bundle translation sudah lengkap
 *   - Hanya bahasa dengan translationAvailability=true yang dapat dipilih
 *   - Array di-sort otomatis berdasarkan displayName (A-Z)
 *   - Untuk menambah bahasa baru:
 *     1. Tambahkan entry di rawLanguages
 *     2. Buat file locale di i18n/locales/
 *     3. Daftarkan loader di useI18n.js
 *     4. Set translationAvailability = true
 *
 * Catatan:
 *   - 'direction' menentukan arah teks: 'ltr' atau 'rtl'
 *   - 'fallbackLocale' dipakai jika key tertentu belum diterjemahkan
 *   - Locale ID (locale) adalah identifier unik, tidak boleh duplikat
 * =========================================================================
 */

const rawLanguages = [
    // ── BAHASA DENGAN TRANSLATION BUNDLE LENGKAP ─────────────────────
    { locale: 'id-ID', displayName: 'Bahasa Indonesia', nativeName: 'Bahasa Indonesia', direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: null },
    { locale: 'en-US', displayName: 'English (US)',     nativeName: 'English (US)',     direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'de-DE', displayName: 'Deutsch',          nativeName: 'Deutsch',          direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'fr-FR', displayName: 'Français',         nativeName: 'Français',         direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'es-ES', displayName: 'Español',          nativeName: 'Español',          direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'pt-BR', displayName: 'Português (BR)',   nativeName: 'Português',        direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'it-IT', displayName: 'Italiano',         nativeName: 'Italiano',         direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'nl-NL', displayName: 'Nederlands',       nativeName: 'Nederlands',       direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'pl-PL', displayName: 'Polski',           nativeName: 'Polski',           direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'tr-TR', displayName: 'Türkçe',           nativeName: 'Türkçe',           direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'ru-RU', displayName: 'Русский',          nativeName: 'Русский',          direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'ms-MY', displayName: 'Bahasa Melayu',    nativeName: 'Bahasa Melayu',    direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'th-TH', displayName: 'ไทย',              nativeName: 'ไทย',              direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'vi-VN', displayName: 'Tiếng Việt',       nativeName: 'Tiếng Việt',       direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'ja-JP', displayName: '日本語',            nativeName: '日本語',            direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'ko-KR', displayName: '한국어',            nativeName: '한국어',            direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'zh-CN', displayName: '简体中文',         nativeName: '简体中文',         direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'zh-TW', displayName: '繁體中文',         nativeName: '繁體中文',         direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'ar-SA', displayName: 'العربية',          nativeName: 'العربية',          direction: 'rtl', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'hi-IN', displayName: 'हिन्दी',             nativeName: 'हिन्दी',             direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },
    { locale: 'sw-KE', displayName: 'Kiswahili',        nativeName: 'Kiswahili',        direction: 'ltr', enabled: true, translationAvailability: true,  fallbackLocale: 'id-ID' },

    // ── BAHASA TERDAFTAR TAPI BELUM ADA BUNDLE (Coming Soon) ─────────
    { locale: 'en-GB', displayName: 'English (UK)',     nativeName: 'English (UK)',     direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'en-US' },
    { locale: 'uk-UA', displayName: 'Українська',       nativeName: 'Українська',       direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'he-IL', displayName: 'עברית',            nativeName: 'עברית',            direction: 'rtl', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'fa-IR', displayName: 'فارسی',            nativeName: 'فارسی',            direction: 'rtl', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'bn-BD', displayName: 'বাংলা',              nativeName: 'বাংলা',              direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'ta-IN', displayName: 'தமிழ்',             nativeName: 'தமிழ்',             direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'te-IN', displayName: 'తెలుగు',            nativeName: 'తెలుగు',            direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'ur-PK', displayName: 'اردو',             nativeName: 'اردو',             direction: 'rtl', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'af-ZA', displayName: 'Afrikaans',        nativeName: 'Afrikaans',        direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'am-ET', displayName: 'አማርኛ',             nativeName: 'አማርኛ',             direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
    { locale: 'az-AZ', displayName: 'Azərbaycan dili',  nativeName: 'Azərbaycan dili',  direction: 'ltr', enabled: true, translationAvailability: false, fallbackLocale: 'id-ID' },
];

/**
 * [DUPLICATE PREVENTION]
 * Fungsi: Memastikan tidak ada duplikasi locale.
 * Cara Kerja: Map dengan locale sebagai key unik, entry pertama yang menang.
 */
const languageMap = new Map();
rawLanguages.forEach(lang => {
    if (!languageMap.has(lang.locale)) {
        languageMap.set(lang.locale, lang);
    }
});

const languages = Array.from(languageMap.values());

/**
 * Fungsi: Mengurutkan bahasa berdasarkan displayName secara alfabetis (A-Z).
 * Cara Kerja: localeCompare memastikan Unicode diurutkan dengan benar.
 */
export const sortedLanguages = [...languages].sort((a, b) =>
    a.displayName.localeCompare(b.displayName)
);

/**
 * Fungsi: Mengelompokkan bahasa berdasarkan huruf pertama displayName.
 * Cara Kerja:
 *   - Ambil huruf pertama dari displayName (uppercase)
 *   - Hanya tampilkan bahasa yang enabled
 *   - Return array of { letter: string, items: Language[] }
 */
export function getGroupedLanguages() {
    const groups = {};
    for (const lang of sortedLanguages) {
        if (!lang.enabled) continue;
        const firstChar = lang.displayName.charAt(0).toUpperCase();
        if (!groups[firstChar]) {
            groups[firstChar] = [];
        }
        groups[firstChar].push(lang);
    }
    return Object.keys(groups)
        .sort((a, b) => a.localeCompare(b))
        .map(letter => ({ letter, items: groups[letter] }));
}

/**
 * Fungsi: Mencari metadata bahasa berdasarkan locale code.
 * Return: Object bahasa atau undefined jika tidak ditemukan.
 */
export function findLanguage(locale) {
    return languages.find(l => l.locale === locale);
}

/** Bahasa default aplikasi — selalu id-ID */
export const DEFAULT_LOCALE = 'id-ID';

export default languages;
