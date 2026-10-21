import { expect, test, vi } from 'vite-plus/test';
import { createInlineEdit } from './inline-edit.js';

test('enter then blur saves once', async () => {
    const save = vi.fn().mockResolvedValue(null);
    const e = createInlineEdit({ value: 'Old', save });
    e.start();
    e.draft = 'New';
    const p = e.commit();
    e.commit();
    await p;
    expect(save).toHaveBeenCalledOnce();
    expect(e.value).toBe('New');
    expect(e.editing).toBe(false);
});

test('unchanged value does not save', async () => {
    const save = vi.fn();
    const e = createInlineEdit({ value: 'Same', save });
    e.start();
    e.draft = ' Same ';
    await e.commit();
    expect(save).not.toHaveBeenCalled();
    expect(e.editing).toBe(false);
});

test('an empty draft saves null', async () => {
    const save = vi.fn().mockResolvedValue(null);
    const e = createInlineEdit({ value: 3, save });
    e.start();
    e.draft = '';
    await e.commit();
    expect(save).toHaveBeenCalledWith(null);
});

test('a null value equals an empty draft', async () => {
    const save = vi.fn();
    const e = createInlineEdit({ value: null, save });
    e.start();
    await e.commit();
    expect(save).not.toHaveBeenCalled();
});

test('cancel restores the value', () => {
    const e = createInlineEdit({ value: 'A', save: vi.fn() });
    e.start();
    e.draft = 'B';
    e.cancel();
    expect(e.draft).toBe('A');
    expect(e.editing).toBe(false);
});

test('a server error keeps the field open', async () => {
    const e = createInlineEdit({ value: 'A', save: vi.fn().mockResolvedValue('Enter a title.') });
    e.start();
    e.draft = 'x';
    await e.commit();
    expect(e.editing).toBe(true);
    expect(e.saving).toBe(false);
    expect(e.error).toBe('Enter a title.');
    expect(e.value).toBe('A');
});

test('commit after cancel does nothing', async () => {
    const save = vi.fn();
    const e = createInlineEdit({ value: 'A', save });
    e.start();
    e.draft = 'B';
    e.cancel();
    await e.commit();
    expect(save).not.toHaveBeenCalled();
});

test('a rejected save shows a generic error and keeps the field open', async () => {
    const e = createInlineEdit({ value: 'A', save: vi.fn().mockRejectedValue(new Error('419')) });
    e.start();
    e.draft = 'x';
    await e.commit();
    expect(e.editing).toBe(true);
    expect(e.saving).toBe(false);
    expect(e.error).toBe("Couldn't save. Try again.");
    expect(e.value).toBe('A');
});
