import { describe, it, expect, vi } from 'vitest';
import { submit } from './submit.js';

describe('submit', () => {
    it('POSTs multipart with metadata + snapshot', async () => {
        const fd = new FormData();
        fd.set('title', 't');
        globalThis.fetch = vi.fn(() => Promise.resolve(new Response(JSON.stringify({ report_id: 7 }), { status: 201 })));

        const out = await submit({
            endpoint: 'https://bugs.test', token: 'abc.def',
            formData: fd, metadata: { url: 'x' }, snapshotBlob: new Blob(['x'], { type: 'image/jpeg' }),
        });

        expect(out.ok).toBe(true);
        expect(out.report_id).toBe(7);
        const call = fetch.mock.calls[0];
        expect(call[0]).toBe('https://bugs.test/api/reports');
        expect(call[1].headers.Authorization).toBe('Bearer abc.def');
    });
});
