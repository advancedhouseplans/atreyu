import obCombobox from './data/combobox.js';
import obCommandPalette from './data/command-palette.js';
import obDock from './data/dock.js';
import obDropdown from './data/dropdown.js';
import obInlineEdit from './data/inline-edit.js';
import obModal from './data/modal.js';
import obSheet from './data/sheet.js';
import obToasts from './data/toasts.js';
import obTooltip from './data/tooltip.js';
import { createLayerStack } from './lib/layers.js';
import { applyTheme, mergeTheme, nextAppearance, reapplyThemeOnSwap, resolveTheme } from './lib/theme.js';

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

    const layers = createLayerStack();

    Alpine.store('obLayers', layers);

    // The one Esc handler: overlays register with the layer stack instead of binding their own.
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !event.isComposing && layers.top() !== null) {
            event.preventDefault();
            layers.escape();
        }
    });

    Alpine.data('obTooltip', obTooltip);
    Alpine.data('obCombobox', obCombobox);
    Alpine.data('obCommandPalette', obCommandPalette);
    Alpine.data('obDock', obDock);
    Alpine.data('obDropdown', obDropdown);
    Alpine.data('obInlineEdit', obInlineEdit);
    Alpine.data('obModal', obModal);
    Alpine.data('obSheet', obSheet);
    Alpine.data('obToasts', obToasts);

    document.addEventListener('livewire:navigating', reapplyThemeOnSwap(document.documentElement, () => Alpine.store('obTheme')));

    window.addEventListener('ob-theme-cycle', () => {
        const theme = Alpine.store('obTheme');
        theme.apply({ appearance: nextAppearance(theme.appearance) });
    });
    window.addEventListener('ob-theme-apply', (event) => Alpine.store('obTheme').apply(event.detail));
}
