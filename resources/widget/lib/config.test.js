import { describe, it, expect } from 'vitest';
import { readConfig } from './config.js';

function b64url(s) {
    return btoa(s).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
}

describe('readConfig', () => {
    it('decodes payload', () => {
        const payload = { endpoint: 'https://bugs.test', source_slug: 'ahp', env: 'prod',
            user_id: 1, user_email: 'a@b', user_name: 'A', exp: 9e9 };
        const token = `${b64url(JSON.stringify(payload))}.sig`;
        const el = document.createElement('script');
        el.setAttribute('data-config', token);
        const cfg = readConfig(el);
        expect(cfg.endpoint).toBe('https://bugs.test');
        expect(cfg.token).toBe(token);
    });

    it('returns null for missing config', () => {
        const el = document.createElement('script');
        expect(readConfig(el)).toBeNull();
    });
});
