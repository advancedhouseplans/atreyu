export function installEventCapture({ ring }) {
    window.addEventListener('error', (e) => {
        ring.push({
            type: 'error',
            message: e.message,
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            stack: e.error?.stack || null,
            ts: Date.now(),
        });
    });

    window.addEventListener('unhandledrejection', (e) => {
        ring.push({
            type: 'unhandledrejection',
            message: String(e.reason?.message ?? e.reason ?? ''),
            stack: e.reason?.stack || null,
            ts: Date.now(),
        });
    });

    ['error', 'warn'].forEach((level) => {
        const orig = console[level].bind(console);
        console[level] = function (...args) {
            try {
                ring.push({
                    type: `console.${level}`,
                    message: args.map((a) => (typeof a === 'string' ? a : JSON.stringify(a))).join(' '),
                    ts: Date.now(),
                });
            } catch (_) { /* ignore */ }
            return orig(...args);
        };
    });

    window.addEventListener('popstate', () => ring.push({ type: 'nav.popstate', url: location.href, ts: Date.now() }));
    window.addEventListener('hashchange', () => ring.push({ type: 'nav.hashchange', url: location.href, ts: Date.now() }));

    const origPushState = history.pushState.bind(history);
    history.pushState = function (state, title, url) {
        ring.push({ type: 'nav.pushState', url: String(url || location.href), ts: Date.now() });
        return origPushState(state, title, url);
    };
}
