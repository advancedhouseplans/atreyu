import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import { createToastQueue } from './toasts.js';

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

test('auto-dismisses after 4s', () => {
    const q = createToastQueue();
    q.push({ message: 'Saved' });
    vi.advanceTimersByTime(3999);
    expect(q.items).toHaveLength(1);
    vi.advanceTimersByTime(1);
    expect(q.items).toHaveLength(0);
});

test('keeps at most three', () => {
    const q = createToastQueue();
    ['a', 'b', 'c', 'd'].forEach((m) => q.push({ message: m }));
    expect(q.items.map((t) => t.message)).toEqual(['b', 'c', 'd']);
});

test('pause holds the timer and resume restarts the remaining time', () => {
    const q = createToastQueue();
    const id = q.push({ message: 'x' });
    vi.advanceTimersByTime(1000);
    q.pause(id);
    vi.advanceTimersByTime(10000);
    expect(q.items).toHaveLength(1);
    q.resume(id);
    vi.advanceTimersByTime(2999);
    expect(q.items).toHaveLength(1);
    vi.advanceTimersByTime(1);
    expect(q.items).toHaveLength(0);
});

test('a dropped toast never fires its timer later', () => {
    const q = createToastQueue({ max: 1 });
    q.push({ message: 'a' });
    vi.advanceTimersByTime(3000);
    q.push({ message: 'b' });
    vi.advanceTimersByTime(1000);
    expect(q.items.map((t) => t.message)).toEqual(['b']);
});

test('defaults the variant and reports changes', () => {
    const onChange = vi.fn();
    const q = createToastQueue({ onChange });
    q.push({ message: 'x' });
    expect(q.items[0]).toMatchObject({ variant: 'neutral', action: null });
    expect(onChange).toHaveBeenCalledWith(q.items);
});
