import { describe, it, expect, beforeEach, vi } from 'vite-plus/test';
import { Ring } from './ring.js';
import { installNetworkCapture } from './net.js';

describe('network capture', () => {
    let ring;
    let fetchMock;
    beforeEach(() => {
        ring = new Ring(10);
        fetchMock = vi.fn(() => Promise.resolve(new Response('ok', {
            status: 200,
            headers: { 'X-Debug-Id': 'abc' },
        })));
        globalThis.fetch = fetchMock;
    });

    it('records fetch calls', async () => {
        installNetworkCapture({ ring, skipUrlPrefix: 'https://skip.test' });
        await fetch('/api/thing');
        const [call] = ring.toArray();
        expect(call.url).toBe('/api/thing');
        expect(call.status).toBe(200);
        expect(call.debug_id).toBe('abc');
    });

    it('skips prefixed urls', async () => {
        installNetworkCapture({ ring, skipUrlPrefix: 'https://skip.test' });
        await fetch('https://skip.test/anything');
        expect(ring.toArray()).toHaveLength(0);
    });

    it('marks same-origin requests while never leaking the token cross-origin', async () => {
        installNetworkCapture({
            ring,
            skipUrlPrefix: 'https://skip.test',
            activeToken: () => 'signed.active.token',
        });

        await fetch('/api/thing');
        await fetch('https://example.test/api/thing');

        const sameOriginInit = fetchMock.mock.calls[0]?.[1];
        const crossOriginInit = fetchMock.mock.calls[1]?.[1];
        expect(sameOriginInit.headers.get('X-Atreyu-Active')).toBe('signed.active.token');
        expect(crossOriginInit).toBeUndefined();
    });
});
