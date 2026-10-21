import { expect, test } from 'vite-plus/test';
import { shouldDismiss } from './sheet.js';

test.each([
    [100, 400, 0, false],
    [120, 400, 0, true],
    [20, 400, 0.6, true],
    [-50, 400, 2, false],
])('shouldDismiss(%i, %i, %f) is %s', (distance, height, velocity, expected) =>
    expect(shouldDismiss({ distance, height, velocity })).toBe(expected));
