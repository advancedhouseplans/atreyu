import { expect, test, vi } from 'vite-plus/test';
import { createLayerStack } from './layers.js';

test('escape closes only the top layer', () => {
    const s = createLayerStack();
    const a = vi.fn();
    const b = vi.fn();
    s.push('modal', a);
    s.push('menu', b);
    s.escape();
    expect(b).toHaveBeenCalledOnce();
    expect(a).not.toHaveBeenCalled();
    expect(s.top()).toBe('modal');
});

test('escape on an empty stack is a no-op', () => expect(() => createLayerStack().escape()).not.toThrow());

test('removing a layer leaves the others in order', () => {
    const s = createLayerStack();
    s.push('a', vi.fn());
    s.push('b', vi.fn());
    s.remove('b');
    expect(s.top()).toBe('a');
});

test('pushing an id twice keeps one entry on top', () => {
    const s = createLayerStack();
    s.push('a', vi.fn());
    s.push('b', vi.fn());
    s.push('a', vi.fn());
    s.remove('a');
    expect(s.top()).toBe('b');
});
