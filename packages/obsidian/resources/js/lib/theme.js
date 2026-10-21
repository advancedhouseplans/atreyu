export const APPEARANCES = ['system', 'light', 'dark'];
export const ACCENTS = ['iris', 'blue', 'teal', 'green', 'amber', 'rose'];

/**
 * Resolve appearance, accent and the effective mode. The server value wins,
 * then the stored one; invalid values fall through to the defaults.
 */
export function resolveTheme({ server = {}, stored = {}, prefersDark = false }) {
    const pick = (key, allowed, fallback) =>
        [server?.[key], stored?.[key]].find((value) => allowed.includes(value)) ?? fallback;

    const appearance = pick('appearance', APPEARANCES, 'system');
    const accent = pick('accent', ACCENTS, 'iris');
    const mode = appearance === 'dark' || (appearance === 'system' && prefersDark) ? 'dark' : 'light';

    return { appearance, accent, mode };
}

/** Overlay a partial {appearance?, accent?} on the current choice so omitted keys are kept. */
export function mergeTheme(current, partial = {}) {
    return {
        appearance: partial?.appearance ?? current.appearance,
        accent: partial?.accent ?? current.accent,
    };
}

export function applyTheme(root, { appearance, accent, mode }) {
    root.dataset.appearance = appearance;
    root.dataset.mode = mode;
    root.dataset.accent = accent;
}

/**
 * Livewire's wire:navigate copies the server-rendered <html> attributes onto the page (data-mode is
 * "dark" for system appearance), so re-apply the live theme once the swap has happened.
 */
export function reapplyThemeOnSwap(root, store) {
    return (event) => event.detail.onSwap(() => applyTheme(root, store()));
}

/** Quick-toggle order: dark → light → system → dark. Unknown values restart at dark. */
export function nextAppearance(current) {
    return { dark: 'light', light: 'system', system: 'dark' }[current] ?? 'dark';
}
