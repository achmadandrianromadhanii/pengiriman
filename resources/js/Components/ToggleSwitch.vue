<!-- =========================================================================
     KOMPONEN: ToggleSwitch — Native Premium Toggle Switch
     =========================================================================
     Fungsi: Komponen toggle switch reusable untuk halaman Keamanan.
             Menggantikan toggle custom dengan komponen terpisah yang:
             - Native-feel (seperti iOS/Android toggle asli)
             - Premium (shadow, border, animasi halus)
             - Ringan (pure CSS, tanpa library eksternal)
             - Accessible (role="switch", aria-checked, keyboard support)

     Cara Kerja:
       1. Menerima prop `modelValue` (boolean) dari parent
       2. Saat diklik, emit 'update:modelValue' ke parent
       3. Parent yang menentukan logika (simpan ke DB, revert jika gagal)
       4. Prop `disabled` mencegah interaksi saat loading/unsupported
       5. Prop `loading` menampilkan visual loading state

     Spesifikasi Ukuran:
       - Track: 44px lebar × 24px tinggi, border-radius 999px
       - Thumb: 20px diameter, bulat sempurna
       - Padding track: 2px (jarak thumb ke tepi track)
       - Tap area: 44×44px (via pseudo-element, tidak mengubah visual)

     Spesifikasi Warna:
       - ON:       Track #2F54EB, Thumb #FFFFFF
       - OFF:      Track #DDE4EE, Thumb #FFFFFF
       - Disabled: Track #EEF2F7, Thumb #FFFFFF (opacity 50%)

     Spesifikasi Animasi:
       - Durasi: 200ms
       - Timing: ease-out
       - Thumb bergeser halus, tidak lompat/berkedip

     Aksesibilitas:
       - role="switch"
       - aria-checked (true/false)
       - aria-label (dari prop)
       - Keyboard: Space/Enter untuk toggle
       - Focus ring untuk navigasi keyboard
     ========================================================================= -->
<script setup>
// ── Props: Konfigurasi toggle dari parent ────────────────────────────────
// modelValue : Status ON/OFF (boolean) — v-model compatible
// disabled   : Apakah toggle tidak bisa diklik (misal: perangkat tidak support)
// loading    : Apakah sedang menunggu response server
// label      : Teks untuk screen reader (accessibility)
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    label: { type: String, default: 'Toggle' },
});

// ── Emit: Kirim perubahan nilai ke parent ─────────────────────────────────
const emit = defineEmits(['update:modelValue']);

// ── Handler: Dipanggil saat user klik atau tekan Space/Enter ─────────────
// Cara Kerja: Jika tidak disabled dan tidak loading, emit nilai kebalikan
function handleToggle() {
    if (props.disabled || props.loading) return;
    emit('update:modelValue', !props.modelValue);
}
</script>

<template>
    <!-- ================================================================
         TOGGLE SWITCH BUTTON
         ================================================================
         Visual: Track 44×24px dengan thumb 20px yang bergeser
         Tap area: 44×44px via pseudo-element (tidak mengubah visual)
         ================================================================ -->
    <button type="button" role="switch" :aria-checked="modelValue" :aria-label="label" :disabled="disabled || loading"
        @click="handleToggle" class="toggle-switch" :class="{
            'toggle-switch--on': modelValue && !disabled,
            'toggle-switch--off': !modelValue && !disabled,
            'toggle-switch--disabled': disabled,
            'toggle-switch--loading': loading,
        }">
        <!-- Screen reader text -->
        <span class="sr-only">{{ label }}</span>

        <!-- ============================================================
             THUMB (Bulatan putih yang bergeser)
             ============================================================
             - 20px × 20px, bulat sempurna
             - Shadow ringan: 0 2px 6px rgba(0,0,0,.16)
             - Posisi OFF: translateX(0)
             - Posisi ON:  translateX(20px)
             - Animasi: 200ms ease-out
             ============================================================ -->
        <span aria-hidden="true" class="toggle-switch__thumb"
            :class="modelValue && !disabled ? 'toggle-switch__thumb--on' : 'toggle-switch__thumb--off'"></span>
    </button>
</template>

<style scoped>
/* ==================================================================
   TOGGLE SWITCH — CSS MURNI
   ==================================================================
   Track: 44px × 24px (visual)
   Thumb: 20px diameter
   Tap area: 44×44px (via ::before pseudo-element)
   ================================================================== */

/* ── Track (Background toggle) ──────────────────────────────────── */
.toggle-switch {
    /* Ukuran visual track: lebar 44px, tinggi 24px */
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 44px;
    height: 24px;
    padding: 2px;
    flex-shrink: 0;
    cursor: pointer;
    box-sizing: border-box;

    /* Pill shape */
    border-radius: 999px;

    /* Border default: transparan */
    border: 1px solid transparent;

    /* Animasi warna track: 200ms ease-out */
    transition: background-color 200ms ease-out,
        border-color 200ms ease-out;

    /* Reset browser button styles */
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background: none;
}

/* ── Tap area: 44×44px tanpa mengubah ukuran visual ─────────────── */
/* Pseudo-element transparan yang memperluas area sentuh             */
/* Ini membuat toggle nyaman disentuh di mobile (WCAG 2.5.5)        */
.toggle-switch::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 44px;
    height: 44px;
}

/* ── State: ON — Track biru ─────────────────────────────────────── */
.toggle-switch--on {
    background-color: #2F54EB;
    border-color: transparent;
}

/* ── State: OFF — Track abu terang ──────────────────────────────── */
.toggle-switch--off {
    background-color: #DDE4EE;
    border-color: rgba(0, 0, 0, 0.06);
}

/* ── State: Disabled — Track sangat terang, opacity rendah ──────── */
.toggle-switch--disabled {
    background-color: #EEF2F7;
    border-color: transparent;
    cursor: not-allowed;
    opacity: 0.5;
}

/* ── State: Loading — Cursor wait, sedikit redup ────────────────── */
.toggle-switch--loading {
    cursor: wait;
    opacity: 0.7;
}

/* ── Focus ring (keyboard navigation) ───────────────────────────── */
.toggle-switch:focus-visible {
    outline: 2px solid #2F54EB;
    outline-offset: 2px;
}

/* ── Dark mode: Tailwind .dark class pada <html> ────────────────── */
:root.dark .toggle-switch--off {
    background-color: #374151;
    border-color: rgba(255, 255, 255, 0.08);
}

:root.dark .toggle-switch--disabled {
    background-color: #1F2937;
}

/* ── Thumb (Bulatan putih) ──────────────────────────────────────── */
.toggle-switch__thumb {
    display: block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #FFFFFF;
    pointer-events: none;

    /* Shadow ringan — memberikan kesan premium/terangkat */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);

    /* Animasi geser thumb: 200ms ease-out */
    transition: transform 200ms ease-out;
}

/* ── Thumb posisi OFF: menempel di kiri ─────────────────────────── */
.toggle-switch__thumb--off {
    transform: translateX(0);
}

/* ── Thumb posisi ON: geser ke kanan 20px ───────────────────────── */
/* Perhitungan: 44px track - 2px border - 4px padding - 20px thumb = 18px */
/* Namun karena border-box + border 1px: 44 - 2(border) - 4(padding) - 20(thumb) = 18px */
.toggle-switch__thumb--on {
    transform: translateX(18px);
}
</style>
