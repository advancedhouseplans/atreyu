export function installNetworkCapture({ ring, skipUrlPrefix }) {
    const originalFetch = globalThis.fetch;

    globalThis.fetch = async function (input, init) {
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        const method = (init && init.method) || (input && input.method) || 'GET';
        if (skipUrlPrefix && url.startsWith(skipUrlPrefix)) {
            return originalFetch(input, init);
        }
        const start = performance.now();
        try {
            const resp = await originalFetch(input, init);
            ring.push({
                method, url, status: resp.status,
                duration_ms: Math.round(performance.now() - start),
                ts: Date.now(),
                debug_id: resp.headers.get('X-Debug-Id') || null,
            });
            return resp;
        } catch (e) {
            ring.push({ method, url, status: 0, duration_ms: Math.round(performance.now() - start), ts: Date.now(), debug_id: null });
            throw e;
        }
    };

    if (typeof XMLHttpRequest !== 'undefined') {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method, url) {
            this.__bug = { method, url, start: 0 };
            return origOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
            if (!this.__bug || (skipUrlPrefix && this.__bug.url.startsWith(skipUrlPrefix))) {
                return origSend.apply(this, arguments);
            }
            this.__bug.start = performance.now();
            this.addEventListener('loadend', () => {
                ring.push({
                    method: this.__bug.method, url: this.__bug.url, status: this.status,
                    duration_ms: Math.round(performance.now() - this.__bug.start),
                    ts: Date.now(),
                    debug_id: this.getResponseHeader('X-Debug-Id') || null,
                });
            });
            return origSend.apply(this, arguments);
        };
    }
}
