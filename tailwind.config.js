// tailwind.config.js
import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",

        // Vue + JS (Inertia)
        "./resources/js/**/*.vue",
        "./resources/js/**/*.js",
    ],

    darkMode: "class",

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: "#1E3A8A", // Deep Metallic Blue
                    light: "#3B82F6",   // Hover/Bright Blue
                    dark: "#0F172A",    // Onyx Dark Blue
                    glow: "#06B6D4",    // Electric Cyan
                },
                secondary: "#8B5CF6",
                accent: "#F59E0B",
                sidebar: {
                    light: "#FFFFFF",
                    dark: "#111827",
                },
                body: {
                    light: "#F8FAFC",
                    dark: "#0E1117",
                },
                card: {
                    light: "#FFFFFF",
                    dark: "#161B2E",
                },

                // Tambahan aman (tidak mengubah yang lama)
                "card-dark": "#0B1220",
            },

            fontFamily: {
                // tetap standar Tailwind/Laravel: pakai defaultTheme sebagai fallback
                sans: ["Plus Jakarta Sans", "Inter", ...defaultTheme.fontFamily.sans],
                heading: ["Outfit", "Sora", ...defaultTheme.fontFamily.sans],
                mono: [...defaultTheme.fontFamily.mono],
            },

            boxShadow: {
                soft: "0 20px 50px -20px rgba(0,0,0,0.25)",
                glass: "0 4px 30px rgba(0, 0, 0, 0.05)",
                "glass-hover": "0 10px 40px rgba(6, 182, 212, 0.15)", // Cyan glow
                premium: "0 10px 40px -10px rgba(30, 58, 138, 0.2)",
            },

            transitionTimingFunction: {
                spring: "cubic-bezier(0.25, 1, 0.5, 1)",
                soft: "cubic-bezier(0.4, 0, 0.2, 1)",
            },

            animation: {
                // Punya kamu (dipertahankan)
                "slide-up": "slideUp 0.35s ease-out both",
                "fade-in": "fadeIn 0.3s ease-out both",
                "spin-slow": "spin 2s linear infinite",
                "pulse-slow": "pulse 2s ease-in-out infinite",
                "bounce-sm": "bounceY 1s ease-in-out infinite",
                truck: "truckMove 0.6s ease-in-out infinite alternate",
                counter: "counter 1s ease-out",

                // Tambahan baru (nama aman, tidak bentrok)
                "fade-in-soft": "fadeInSoft 0.35s ease-out both",
                "slide-in-left": "slideInLeft 0.25s ease-out both",
                shimmer: "shimmer 1.2s ease-in-out infinite",
                "pulse-soft": "pulseSoft 1.6s ease-in-out infinite",
                floaty: "floaty 3s ease-in-out infinite",
                "spin-slower": "spinSlower 2.5s linear infinite",
            },

            keyframes: {
                // Punya kamu (dipertahankan)
                slideUp: {
                    from: { opacity: "0", transform: "translateY(20px)" },
                    to: { opacity: "1", transform: "translateY(0)" },
                },
                fadeIn: {
                    from: { opacity: "0" },
                    to: { opacity: "1" },
                },
                bounceY: {
                    "0%,100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-8px)" },
                },
                truckMove: {
                    "0%": { transform: "translateX(0)" },
                    "100%": { transform: "translateX(8px)" },
                },
                counter: {
                    from: { opacity: "0", transform: "scale(0.8)" },
                    to: { opacity: "1", transform: "scale(1)" },
                },

                // Tambahan baru (tidak mengganggu yang lama)
                fadeInSoft: {
                    "0%": { opacity: "0", transform: "translateY(6px)" },
                    "100%": { opacity: "1", transform: "translateY(0)" },
                },
                slideInLeft: {
                    "0%": { opacity: "0", transform: "translateX(-10px)" },
                    "100%": { opacity: "1", transform: "translateX(0)" },
                },
                shimmer: {
                    "0%": { transform: "translateX(-100%)" },
                    "100%": { transform: "translateX(100%)" },
                },
                pulseSoft: {
                    "0%,100%": { opacity: "1" },
                    "50%": { opacity: "0.55" },
                },
                floaty: {
                    "0%,100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-4px)" },
                },
                spinSlower: {
                    "0%": { transform: "rotate(0deg)" },
                    "100%": { transform: "rotate(360deg)" },
                },
            },
        },
    },

    plugins: [forms],
};
