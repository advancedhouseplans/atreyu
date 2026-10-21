import { applyTheme, resolveTheme } from './lib/theme.js';

const STORAGE_KEY = 'ob-theme';

function readStored() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? {};
    } catch {
        return {};
    }
}

export default function obsidian(Alpine) {
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    Alpine.store('obTheme', {
        appearance: 'system',
        accent: 'iris',
        mode: 'light',

        init() {
            // The inline theme-script already set the attributes; adopt them instead of re-deriving the server value.
            const { appearance, accent } = document.documentElement.dataset;
            this.apply({ appearance, accent }, false);
            media.addEventListener('change', () => this.apply(this, false));
        },

        apply({ appearance, accent }, persist = true) {
            Object.assign(this, resolveTheme({ server: { appearance, accent }, prefersDark: media.matches }));

            if (persist) {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify({ appearance: this.appearance, accent: this.accent }));
                } catch {
                    // Storage can be blocked; the theme still applies for this page.
                }
            }

            applyTheme(document.documentElement, this);
        },
    });

    window.addEventListener('ob-theme-apply', (event) => Alpine.store('obTheme').apply(event.detail));
}
