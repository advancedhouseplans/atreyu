import { describe, it, expect, beforeEach, vi } from 'vitest';
import { Ring } from './ring.js';
import { installNetworkCapture } from './net.js';

describe('network capture', () => {
    let ring;
    beforeEach(() => {
        ring = new Ring(10);
        globalThis.fetch = vi.fn(() => Promise.resolve(new Response('ok', {
            status: 200,
            headers: { 'X-Debug-Id': 'abc' },
        })));
        installNetworkCapture({ ring, skipUrlPrefix: 'https://skip.test' });
    });

    it('records fetch calls', async () => {
        await fetch('/api/thing');
        const [call] = ring.toArray();
        expect(call.url).toBe('/api/thing');
        expect(call.status).toBe(200);
        expect(call.debug_id).toBe('abc');
    });

    it('skips prefixed urls', async () => {
        await fetch('https://skip.test/anything');
        expect(ring.toArray()).toHaveLength(0);
    });
});
