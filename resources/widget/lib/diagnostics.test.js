import { describe, expect, it } from 'vite-plus/test';
import { selectRecentDiagnostics } from './diagnostics.js';

describe('selectRecentDiagnostics', () => {
    it('only includes diagnostics from the configured time window', () => {
        const now = 1_000_000;
        const result = selectRecentDiagnostics({
            now,
            windowMs: 300_000,
            maxEvents: 25,
            initialDebugId: 'initial-old',
            initialDebugTimestamp: now - 300_001,
            network: [
                { ts: now - 300_001, debug_id: 'old', url: '/old' },
                { ts: now - 10_000, debug_id: 'recent', url: '/recent' },
            ],
            events: [
                { ts: now - 400_000, message: 'old' },
                { ts: now - 20_000, message: 'recent' },
            ],
        });

        expect(result.network.map(item => item.url)).toEqual(['/recent']);
        expect(result.events.map(item => item.message)).toEqual(['recent']);
        expect(result.debugIds).toEqual(['recent']);
    });

    it('keeps the initial request when it is recent and deduplicates debug ids', () => {
        const now = 1_000_000;
        const result = selectRecentDiagnostics({
            now,
            initialDebugId: 'same-id',
            initialDebugTimestamp: now - 1_000,
            network: [{ ts: now, debug_id: 'same-id' }],
            events: [],
        });

        expect(result.debugIds).toEqual(['same-id']);
    });

    it('keeps only the last few requests and client events', () => {
        const result = selectRecentDiagnostics({
            now: 1_000_000,
            maxRequests: 2,
            maxEvents: 2,
            initialDebugId: 'initial',
            initialDebugTimestamp: 1_000_000,
            network: [
                { ts: 999_000, debug_id: 'one' },
                { ts: 999_500, debug_id: 'two' },
                { ts: 1_000_000, debug_id: 'three' },
            ],
            events: [
                { ts: 998_000, message: 'one' },
                { ts: 999_000, message: 'two' },
                { ts: 1_000_000, message: 'three' },
            ],
        });

        expect(result.network.map(item => item.debug_id)).toEqual(['two', 'three']);
        expect(result.events.map(item => item.message)).toEqual(['two', 'three']);
        expect(result.debugIds).toEqual(['two', 'three']);
    });
});
