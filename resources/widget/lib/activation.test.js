import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { startTelemetryActivation } from './activation.js';

describe('startTelemetryActivation', () => {
    afterEach(() => vi.useRealTimers());

    it('arms telemetry immediately and refreshes it while mounted', async () => {
        vi.useFakeTimers();
        const fetcher = vi.fn(() => Promise.resolve(new Response(JSON.stringify({ token: 'refreshed.token' }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
        })));
        const onToken = vi.fn();
        const stop = startTelemetryActivation({ token: 'signed.token', refreshMs: 60000, fetcher, onToken });

        expect(fetcher).toHaveBeenCalledWith('/_atreyu/activate', expect.objectContaining({
            headers: { Authorization: 'Bearer signed.token' },
            credentials: 'same-origin',
        }));
        await vi.advanceTimersByTimeAsync(0);
        expect(onToken).toHaveBeenCalledWith('refreshed.token');

        await vi.advanceTimersByTimeAsync(60000);
        expect(fetcher).toHaveBeenCalledTimes(2);
        expect(fetcher.mock.calls[1][1].headers.Authorization).toBe('Bearer refreshed.token');

        globalThis.dispatchEvent(new Event('pagehide'));
        expect(fetcher).toHaveBeenLastCalledWith('/_atreyu/deactivate', expect.objectContaining({
            keepalive: true,
        }));

        stop();
    });
});
