import { expect, test } from 'vite-plus/test';
import { paletteShortcut } from './shortcuts.js';

const ev = (key, opts = {}, target = document.body) => ({ key, metaKey: false, ctrlKey: false, altKey: false, ...opts, target });

test('cmd+k always opens', () => {
    const input = document.createElement('input');

    expect(paletteShortcut(ev('k', { metaKey: true }, input))).toBe(true);
});
test('ctrl+k opens', () => expect(paletteShortcut(ev('k', { ctrlKey: true }))).toBe(true));
test('plain k does nothing', () => expect(paletteShortcut(ev('k'))).toBe(false));
test('slash opens outside fields', () => expect(paletteShortcut(ev('/'))).toBe(true));
test('slash with a modifier is ignored', () => expect(paletteShortcut(ev('/', { ctrlKey: true }))).toBe(false));
test.each(['input', 'textarea', 'select'])('slash is ignored in %s', (tag) => expect(paletteShortcut(ev('/', {}, document.createElement(tag)))).toBe(false));
test('slash is ignored in contenteditable', () => expect(paletteShortcut(ev('/', {}, { isContentEditable: true, tagName: 'DIV' }))).toBe(false));
