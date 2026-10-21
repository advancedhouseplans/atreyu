<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<script>
    (() => {
        const allowedThemes = @js(\App\Enums\Theme::values());
        const savedTheme = @js(auth()->user()?->theme?->value);
        const browserTheme = localStorage.getItem('atreyu-theme');
        const legacyTheme = localStorage.getItem('mary-theme')?.replaceAll('"', '');
        const fallbackTheme = allowedThemes.includes(browserTheme)
            ? browserTheme
            : (allowedThemes.includes(legacyTheme) ? legacyTheme : 'light');
        const theme = savedTheme && allowedThemes.includes(savedTheme) ? savedTheme : fallbackTheme;

        const applyTheme = (nextTheme) => {
            if (!allowedThemes.includes(nextTheme)) return;

            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('atreyu-theme', nextTheme);
        };

        applyTheme(theme);
        window.addEventListener('atreyu-theme-changed', (event) => applyTheme(event.detail.theme));
    })();
</script>

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@vite(['resources/css/app.css', 'resources/js/app.js'])
