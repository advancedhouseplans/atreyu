export function startTelemetryActivation({ token, refreshMs = 60000, fetcher = globalThis.fetch.bind(globalThis), onToken = () => {} }) {
    let currentToken = token;
    const activate = () => fetcher('/_atreyu/activate', {
        method: 'GET',
        headers: { Authorization: `Bearer ${currentToken}` },
        credentials: 'same-origin',
        cache: 'no-store',
    }).then(async (response) => {
        if (!response.ok) return;
        const payload = await response.json().catch(() => null);
        if (typeof payload?.token === 'string') {
            currentToken = payload.token;
            onToken(currentToken);
        }
    }).catch(() => null);

    activate();
    const timer = globalThis.setInterval(activate, Math.max(15000, refreshMs));
    const deactivate = () => {
        fetcher('/_atreyu/deactivate', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
        }).catch(() => null);
    };
    globalThis.addEventListener?.('pagehide', deactivate);
    globalThis.addEventListener?.('pageshow', activate);

    return () => {
        globalThis.clearInterval(timer);
        globalThis.removeEventListener?.('pagehide', deactivate);
        globalThis.removeEventListener?.('pageshow', activate);
        deactivate();
    };
}
