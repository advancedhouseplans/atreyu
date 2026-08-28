import { describe, it, expect, beforeEach } from 'vitest';
import { Ring } from './ring.js';
import { installEventCapture } from './events.js';

describe('event capture', () => {
    let ring;
    beforeEach(() => {
        ring = new Ring(10);
        installEventCapture({ ring });
    });

    it('captures console.error', () => {
        console.error('boom');
        const [e] = ring.toArray();
        expect(e.type).toBe('console.error');
        expect(e.message).toContain('boom');
    });

    it('captures error events', () => {
        window.dispatchEvent(new ErrorEvent('error', { message: 'oops', filename: 'a.js', lineno: 1 }));
        const items = ring.toArray();
        expect(items.some(x => x.type === 'error' && x.message === 'oops')).toBe(true);
    });
});
