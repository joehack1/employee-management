{{-- Theme bootstrap: apply saved (or system) dark mode before first paint, no flash. --}}
<script>
    (function () {
        var stored = null;
        try { stored = localStorage.getItem('theme'); } catch (e) {}
        var dark = stored === 'dark'
            || (stored !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (dark) document.documentElement.classList.add('dark');
    })();

    window.toggleTheme = function () {
        var dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
    };
</script>

<style>
    /* ---- Theme toggle icons ---- */
    .theme-toggle .icon-sun { display: none; }
    .dark .theme-toggle .icon-moon { display: none; }
    .dark .theme-toggle .icon-sun { display: block; }

    /* ---- Dark palette remap -------------------------------------------------
       The app builds every screen from light-surface utilities (bg-white,
       text-slate-900, border-slate-200, brand tints ...). Rather than
       sprinkling dark: variants through 27 views, remap those utilities
       globally under .dark with higher-specificity selectors. Solid brand
       fills (bg-*-600 buttons, bg-slate-800 buttons, badges with text-white)
       are intentionally left untouched — they read well on dark surfaces.
       Neutrals use pure blacks (no blue tint). */
    .dark { color-scheme: dark; --tw-ring-offset-color: #000; }

    /* Page-level surfaces (<html> carries bg-white / bg-slate-50) */
    .dark.bg-white, .dark.bg-slate-50 { background-color: #000; }

    /* Neutral surfaces */
    .dark .bg-white { background-color: #111; }
    .dark .bg-slate-50,
    .dark .bg-slate-50\/40,
    .dark .bg-slate-50\/50,
    .dark .bg-slate-50\/60,
    .dark .bg-slate-50\/80,
    .dark .bg-slate-100 { background-color: #161616; }
    .dark .bg-slate-200 { background-color: #1f1f1f; }
    .dark .bg-slate-300 { background-color: #2a2a2a; }
    .dark .bg-slate-900\/50 { background-color: rgba(0, 0, 0, .7); } /* modal backdrop */

    /* Neutral borders & dividers */
    .dark .border-slate-100,
    .dark .border-slate-200,
    .dark .border-slate-200\/80 { border-color: #262626; }
    .dark .border-slate-300 { border-color: #3a3a3a; }
    .dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
    .dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) { border-color: #262626; }

    /* Neutral text */
    .dark .text-slate-900 { color: #fafafa; }
    .dark .text-slate-800 { color: #f5f5f5; }
    .dark .text-slate-700 { color: #e5e5e5; }
    .dark .text-slate-600 { color: #d4d4d4; }
    .dark .text-slate-500 { color: #a3a3a3; }
    .dark .text-slate-400,
    .dark .text-slate-300 { color: #737373; }
    .dark .placeholder-slate-400::placeholder { color: #737373; }

    /* Neutral interactive states */
    .dark .hover\:text-slate-600:hover { color: #d4d4d4; }
    .dark .hover\:text-slate-700:hover,
    .dark .hover\:text-slate-800:hover { color: #f5f5f5; }
    .dark .hover\:text-slate-900:hover { color: #fafafa; }
    .dark .hover\:bg-slate-50:hover,
    .dark .hover\:bg-slate-50\/50:hover,
    .dark .hover\:bg-slate-50\/60:hover,
    .dark .hover\:bg-slate-100:hover { background-color: #161616; }
    .dark .hover\:bg-slate-200:hover { background-color: #1f1f1f; }

    /* ===== Brand (teal family): blue teal emerald indigo cyan green purple violet ===== */
    .dark .bg-blue-50, .dark .bg-teal-50, .dark .bg-emerald-50, .dark .bg-indigo-50,
    .dark .bg-cyan-50, .dark .bg-green-50, .dark .bg-purple-50, .dark .bg-violet-50,
    .dark .hover\:bg-blue-50:hover, .dark .hover\:bg-teal-50:hover,
    .dark .hover\:bg-emerald-50:hover, .dark .hover\:bg-indigo-50:hover,
    .dark .hover\:bg-cyan-50:hover, .dark .hover\:bg-green-50:hover,
    .dark .hover\:bg-purple-50:hover, .dark .hover\:bg-violet-50:hover { background-color: rgba(53, 162, 158, .14); }

    .dark .bg-blue-50\/20, .dark .bg-teal-50\/20, .dark .bg-emerald-50\/20, .dark .bg-indigo-50\/20,
    .dark .bg-cyan-50\/20, .dark .bg-green-50\/20, .dark .bg-purple-50\/20, .dark .bg-violet-50\/20,
    .dark .bg-blue-50\/30, .dark .bg-teal-50\/30, .dark .bg-emerald-50\/30, .dark .bg-indigo-50\/30,
    .dark .bg-cyan-50\/30, .dark .bg-green-50\/30, .dark .bg-purple-50\/30, .dark .bg-violet-50\/30,
    .dark .bg-blue-50\/40, .dark .bg-teal-50\/40, .dark .bg-emerald-50\/40, .dark .bg-indigo-50\/40,
    .dark .bg-cyan-50\/40, .dark .bg-green-50\/40, .dark .bg-purple-50\/40, .dark .bg-violet-50\/40,
    .dark .bg-blue-50\/50, .dark .bg-teal-50\/50, .dark .bg-emerald-50\/50, .dark .bg-indigo-50\/50,
    .dark .bg-cyan-50\/50, .dark .bg-green-50\/50, .dark .bg-purple-50\/50, .dark .bg-violet-50\/50,
    .dark .hover\:bg-blue-50\/50:hover, .dark .hover\:bg-teal-50\/50:hover,
    .dark .hover\:bg-emerald-50\/50:hover, .dark .hover\:bg-indigo-50\/50:hover,
    .dark .hover\:bg-cyan-50\/50:hover, .dark .hover\:bg-green-50\/50:hover,
    .dark .hover\:bg-purple-50\/50:hover, .dark .hover\:bg-violet-50\/50:hover { background-color: rgba(53, 162, 158, .12); }

    .dark .bg-blue-50\/70, .dark .bg-teal-50\/70, .dark .bg-emerald-50\/70, .dark .bg-indigo-50\/70,
    .dark .bg-cyan-50\/70, .dark .bg-green-50\/70, .dark .bg-purple-50\/70, .dark .bg-violet-50\/70,
    .dark .hover\:bg-blue-50\/70:hover, .dark .hover\:bg-teal-50\/70:hover,
    .dark .hover\:bg-emerald-50\/70:hover, .dark .hover\:bg-indigo-50\/70:hover,
    .dark .hover\:bg-cyan-50\/70:hover, .dark .hover\:bg-green-50\/70:hover,
    .dark .hover\:bg-purple-50\/70:hover, .dark .hover\:bg-violet-50\/70:hover { background-color: rgba(53, 162, 158, .14); }

    .dark .bg-blue-100, .dark .bg-teal-100, .dark .bg-emerald-100, .dark .bg-indigo-100,
    .dark .bg-cyan-100, .dark .bg-green-100, .dark .bg-purple-100, .dark .bg-violet-100,
    .dark .hover\:bg-blue-100:hover, .dark .hover\:bg-teal-100:hover,
    .dark .hover\:bg-emerald-100:hover, .dark .hover\:bg-indigo-100:hover,
    .dark .hover\:bg-cyan-100:hover, .dark .hover\:bg-green-100:hover,
    .dark .hover\:bg-purple-100:hover, .dark .hover\:bg-violet-100:hover { background-color: rgba(53, 162, 158, .22); }

    .dark .text-blue-500, .dark .text-teal-500, .dark .text-emerald-500, .dark .text-indigo-500,
    .dark .text-cyan-500, .dark .text-green-500, .dark .text-purple-500, .dark .text-violet-500,
    .dark .hover\:text-blue-500:hover, .dark .hover\:text-teal-500:hover,
    .dark .hover\:text-emerald-500:hover, .dark .hover\:text-indigo-500:hover,
    .dark .hover\:text-cyan-500:hover, .dark .hover\:text-green-500:hover,
    .dark .hover\:text-purple-500:hover, .dark .hover\:text-violet-500:hover { color: #58b1ac; }

    .dark .text-blue-600, .dark .text-teal-600, .dark .text-emerald-600, .dark .text-indigo-600,
    .dark .text-cyan-600, .dark .text-green-600, .dark .text-purple-600, .dark .text-violet-600,
    .dark .text-blue-700, .dark .text-teal-700, .dark .text-emerald-700, .dark .text-indigo-700,
    .dark .text-cyan-700, .dark .text-green-700, .dark .text-purple-700, .dark .text-violet-700,
    .dark .hover\:text-blue-600:hover, .dark .hover\:text-teal-600:hover,
    .dark .hover\:text-emerald-600:hover, .dark .hover\:text-indigo-600:hover,
    .dark .hover\:text-cyan-600:hover, .dark .hover\:text-green-600:hover,
    .dark .hover\:text-purple-600:hover, .dark .hover\:text-violet-600:hover,
    .dark .hover\:text-blue-700:hover, .dark .hover\:text-teal-700:hover,
    .dark .hover\:text-emerald-700:hover, .dark .hover\:text-indigo-700:hover,
    .dark .hover\:text-cyan-700:hover, .dark .hover\:text-green-700:hover,
    .dark .hover\:text-purple-700:hover, .dark .hover\:text-violet-700:hover { color: #8bcbc7; }

    .dark .text-blue-800, .dark .text-teal-800, .dark .text-emerald-800, .dark .text-indigo-800,
    .dark .text-cyan-800, .dark .text-green-800, .dark .text-purple-800, .dark .text-violet-800,
    .dark .text-blue-900, .dark .text-teal-900, .dark .text-emerald-900, .dark .text-indigo-900,
    .dark .text-cyan-900, .dark .text-green-900, .dark .text-purple-900, .dark .text-violet-900,
    .dark .text-blue-950, .dark .text-teal-950, .dark .text-emerald-950, .dark .text-indigo-950,
    .dark .text-cyan-950, .dark .text-green-950, .dark .text-purple-950, .dark .text-violet-950,
    .dark .hover\:text-blue-800:hover, .dark .hover\:text-teal-800:hover,
    .dark .hover\:text-emerald-800:hover, .dark .hover\:text-indigo-800:hover,
    .dark .hover\:text-cyan-800:hover, .dark .hover\:text-green-800:hover,
    .dark .hover\:text-purple-800:hover, .dark .hover\:text-violet-800:hover,
    .dark .hover\:text-blue-900:hover, .dark .hover\:text-teal-900:hover,
    .dark .hover\:text-emerald-900:hover, .dark .hover\:text-indigo-900:hover,
    .dark .hover\:text-cyan-900:hover, .dark .hover\:text-green-900:hover,
    .dark .hover\:text-purple-900:hover, .dark .hover\:text-violet-900:hover { color: #b8e1de; }

    .dark .border-blue-100, .dark .border-teal-100, .dark .border-emerald-100, .dark .border-indigo-100,
    .dark .border-cyan-100, .dark .border-green-100, .dark .border-purple-100, .dark .border-violet-100,
    .dark .border-blue-200, .dark .border-teal-200, .dark .border-emerald-200, .dark .border-indigo-200,
    .dark .border-cyan-200, .dark .border-green-200, .dark .border-purple-200, .dark .border-violet-200,
    .dark .border-blue-300, .dark .border-teal-300, .dark .border-emerald-300, .dark .border-indigo-300,
    .dark .border-cyan-300, .dark .border-green-300, .dark .border-purple-300, .dark .border-violet-300,
    .dark .hover\:border-blue-400:hover, .dark .hover\:border-teal-400:hover,
    .dark .hover\:border-emerald-400:hover, .dark .hover\:border-indigo-400:hover,
    .dark .hover\:border-cyan-400:hover, .dark .hover\:border-green-400:hover,
    .dark .hover\:border-purple-400:hover, .dark .hover\:border-violet-400:hover { border-color: rgba(53, 162, 158, .35); }

    /* ===== Brand (red family): rose red amber orange yellow ===== */
    .dark .bg-rose-50, .dark .bg-red-50, .dark .bg-amber-50, .dark .bg-orange-50, .dark .bg-yellow-50,
    .dark .hover\:bg-rose-50:hover, .dark .hover\:bg-red-50:hover, .dark .hover\:bg-amber-50:hover,
    .dark .hover\:bg-orange-50:hover, .dark .hover\:bg-yellow-50:hover { background-color: rgba(181, 76, 79, .14); }

    .dark .bg-rose-50\/50, .dark .bg-red-50\/50, .dark .bg-amber-50\/50, .dark .bg-orange-50\/50, .dark .bg-yellow-50\/50,
    .dark .bg-rose-50\/30, .dark .bg-red-50\/30, .dark .bg-amber-50\/30, .dark .bg-orange-50\/30, .dark .bg-yellow-50\/30,
    .dark .bg-rose-50\/40, .dark .bg-red-50\/40, .dark .bg-amber-50\/40, .dark .bg-orange-50\/40, .dark .bg-yellow-50\/40,
    .dark .bg-rose-50\/70, .dark .bg-red-50\/70, .dark .bg-amber-50\/70, .dark .bg-orange-50\/70, .dark .bg-yellow-50\/70,
    .dark .hover\:bg-rose-50\/50:hover, .dark .hover\:bg-red-50\/50:hover, .dark .hover\:bg-amber-50\/50:hover,
    .dark .hover\:bg-orange-50\/50:hover, .dark .hover\:bg-yellow-50\/50:hover { background-color: rgba(181, 76, 79, .12); }

    .dark .bg-rose-100, .dark .bg-red-100, .dark .bg-amber-100, .dark .bg-orange-100, .dark .bg-yellow-100 { background-color: rgba(181, 76, 79, .22); }
    .dark .bg-rose-100\/80, .dark .bg-red-100\/80, .dark .bg-amber-100\/80,
    .dark .bg-orange-100\/80, .dark .bg-yellow-100\/80 { background-color: rgba(181, 76, 79, .22); }

    .dark .text-rose-500, .dark .text-red-500, .dark .text-amber-500, .dark .text-orange-500, .dark .text-yellow-500 { color: #cb7476; }

    .dark .text-rose-600, .dark .text-red-600, .dark .text-amber-600, .dark .text-orange-600, .dark .text-yellow-600,
    .dark .text-rose-700, .dark .text-red-700, .dark .text-amber-700, .dark .text-orange-700, .dark .text-yellow-700,
    .dark .hover\:text-rose-700:hover, .dark .hover\:text-red-700:hover, .dark .hover\:text-amber-700:hover,
    .dark .hover\:text-orange-700:hover, .dark .hover\:text-yellow-700:hover,
    .dark .hover\:text-rose-800:hover, .dark .hover\:text-red-800:hover, .dark .hover\:text-amber-800:hover,
    .dark .hover\:text-orange-800:hover, .dark .hover\:text-yellow-800:hover { color: #dfa4a5; }

    .dark .text-rose-800, .dark .text-red-800, .dark .text-amber-800, .dark .text-orange-800, .dark .text-yellow-800,
    .dark .text-rose-900, .dark .text-red-900, .dark .text-amber-900, .dark .text-orange-900, .dark .text-yellow-900,
    .dark .text-rose-950, .dark .text-red-950, .dark .text-amber-950, .dark .text-orange-950, .dark .text-yellow-950,
    .dark .hover\:text-rose-900:hover, .dark .hover\:text-red-900:hover, .dark .hover\:text-amber-900:hover,
    .dark .hover\:text-orange-900:hover, .dark .hover\:text-yellow-900:hover { color: #e8b9ba; }

    .dark .border-rose-100, .dark .border-red-100, .dark .border-amber-100, .dark .border-orange-100, .dark .border-yellow-100,
    .dark .border-rose-200, .dark .border-red-200, .dark .border-amber-200, .dark .border-orange-200, .dark .border-yellow-200,
    .dark .border-rose-300, .dark .border-red-300, .dark .border-amber-300, .dark .border-orange-300, .dark .border-yellow-300,
    .dark .hover\:border-rose-400:hover, .dark .hover\:border-red-400:hover, .dark .hover\:border-amber-400:hover,
    .dark .hover\:border-orange-400:hover, .dark .hover\:border-yellow-400:hover { border-color: rgba(181, 76, 79, .35); }

    /* Focus rings / decorative rings */
    .dark .ring-black\/5 { --tw-ring-color: rgba(255, 255, 255, .10); }
    .dark .ring-black\/10 { --tw-ring-color: rgba(255, 255, 255, .14); }
    .dark .ring-blue-100, .dark .ring-teal-100, .dark .ring-emerald-100,
    .dark .ring-indigo-100, .dark .ring-purple-100, .dark .ring-rose-100 { --tw-ring-color: rgba(255, 255, 255, .18); }

    /* Gradients */
    .dark .from-blue-50, .dark .from-teal-50, .dark .from-emerald-50 {
        --tw-gradient-from: rgba(53, 162, 158, .14);
        --tw-gradient-to: rgb(53 162 158 / 0);
        --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
    }
    .dark .to-indigo-50\/50, .dark .to-blue-50\/50 { --tw-gradient-to: rgba(53, 162, 158, .12); }

    /* Form controls inherit dark color-scheme (native pickers, scrollbars) */
    .dark input, .dark textarea, .dark select { background-color: #161616; color: #e5e5e5; }
    .dark input::placeholder, .dark textarea::placeholder { color: #737373; }
</style>
