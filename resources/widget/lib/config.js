export function readConfig(scriptEl) {
    if (!scriptEl) return null;
    const token = scriptEl.getAttribute('data-atreyu-config') || scriptEl.getAttribute('data-config');
    if (!token) return null;
    const [body] = token.split('.');
    if (!body) return null;
    try {
        const pad = body.length % 4 ? '='.repeat(4 - (body.length % 4)) : '';
        const json = atob(body.replaceAll('-', '+').replaceAll('_', '/') + pad);
        const payload = JSON.parse(json);
        return { ...payload, token };
    } catch (_) {
        return null;
    }
}
