@props(['appearance' => null, 'accent' => null])

{{-- Runs before first paint (and before Vite): mirrors resolveTheme() in js/lib/theme.js. --}}
<script>
    (() => {
        const A = ['system', 'light', 'dark'], C = ['iris', 'blue', 'teal', 'green', 'amber', 'rose'];
        const server = { appearance: @js($appearance), accent: @js($accent) };
        let stored = {};
        try { localStorage.removeItem('mary-theme'); localStorage.removeItem('atreyu-theme'); } catch (e) {}
        try { stored = JSON.parse(localStorage.getItem('ob-theme')) || {}; } catch (e) {}
        const pick = (key, allowed, fallback) => [server[key], stored[key]].find((v) => allowed.includes(v)) || fallback;
        const appearance = pick('appearance', A, 'system'), accent = pick('accent', C, 'iris');
        const dark = appearance === 'dark' || (appearance === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        const root = document.documentElement;
        root.dataset.appearance = appearance;
        root.dataset.mode = dark ? 'dark' : 'light';
        root.dataset.accent = accent;
    })();
</script>
