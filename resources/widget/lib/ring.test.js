import { describe, it, expect } from 'vite-plus/test';
import { Ring } from './ring.js';

describe('Ring', () => {
    it('caps and preserves order', () => {
        const r = new Ring(3);
        r.push(1);
        r.push(2);
        r.push(3);
        r.push(4);
        expect(r.toArray()).toEqual([2, 3, 4]);
    });
    it('handles zero-size gracefully', () => {
        const r = new Ring(0);
        r.push(1);
        expect(r.toArray()).toEqual([]);
    });
});
