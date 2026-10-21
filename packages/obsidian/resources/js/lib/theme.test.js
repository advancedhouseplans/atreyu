import { expect, test } from 'vite-plus/test';
import { applyTheme, mergeTheme, nextAppearance, resolveTheme } from './theme.js';

test('server preference wins', () => expect(resolveTheme({ server: { appearance: 'dark', accent: 'teal' }, stored: { appearance: 'light', accent: 'rose' }, prefersDark: false })).toEqual({ appearance: 'dark', accent: 'teal', mode: 'dark' }));
test('system follows the OS', () => expect(resolveTheme({ server: {}, stored: {}, prefersDark: true }).mode).toBe('dark'));
test('legacy and unknown values fall back to defaults', () => expect(resolveTheme({ server: { appearance: 'synthwave' }, stored: { appearance: 'night', accent: 'puce' }, prefersDark: false })).toEqual({ appearance: 'system', accent: 'iris', mode: 'light' }));
test('applyTheme sets all three attributes', () => { applyTheme(document.documentElement, { appearance: 'system', accent: 'amber', mode: 'dark' }); expect(document.documentElement.dataset).toMatchObject({ appearance: 'system', mode: 'dark', accent: 'amber' }); });
test('mergeTheme keeps omitted keys from the current choice', () => {
    expect(mergeTheme({ appearance: 'dark', accent: 'iris' }, { accent: 'teal' })).toEqual({ appearance: 'dark', accent: 'teal' });
    expect(mergeTheme({ appearance: 'dark', accent: 'iris' }, undefined)).toEqual({ appearance: 'dark', accent: 'iris' });
});
test('nextAppearance cycles dark, light, system', () => {
    expect(['dark', 'light', 'system'].map(nextAppearance)).toEqual(['light', 'system', 'dark']);
    expect(nextAppearance('bogus')).toBe('dark');
});
