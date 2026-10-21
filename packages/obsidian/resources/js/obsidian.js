import { applyTheme, mergeTheme, resolveTheme } from './lib/theme.js';

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
            // The inline theme-script normally set the attributes already; fall back to storage when it is absent.
            const { appearance, accent } = document.documentElement.dataset;
            this.resolve({ server: { appearance, accent }, stored: readStored() });
            media.addEventListener('change', () => this.apply(this, false));
        },

        resolve({ server, stored }) {
            Object.assign(this, resolveTheme({ server, stored, prefersDark: media.matches }));
            applyTheme(document.documentElement, this);
        },

        apply(partial, persist = true) {
            this.resolve({ server: mergeTheme(this, partial) });

            if (persist) {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify({ appearance: this.appearance, accent: this.accent }));
                } catch {
                    // Storage can be blocked; the theme still applies for this page.
                }
            }
        },
    });

    window.addEventListener('ob-theme-apply', (event) => Alpine.store('obTheme').apply(event.detail));
}
