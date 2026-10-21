import { expect, test } from 'vite-plus/test';
import { nextDockVisibility } from './dock.js';

test.each([
    [100, 120, true, false],
    [120, 110, false, true],
    [100, 104, true, true],
    [100, 104, false, false],
    [50, 0, false, true],
])('prev %i → %i (visible %s) gives %s', (previousY, y, visible, expected) =>
    expect(nextDockVisibility({ previousY, y, visible })).toBe(expected));
