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

export function applyTheme(root, { appearance, accent, mode }) {
    root.dataset.appearance = appearance;
    root.dataset.mode = mode;
    root.dataset.accent = accent;
}
