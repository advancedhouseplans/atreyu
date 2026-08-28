import { describe, it, expect, vi } from 'vitest';
import { mountUi } from './ui.js';

describe('mountUi', () => {
    it('mounts a shadow root with a button', () => {
        document.body.innerHTML = '';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');
        expect(root).toBeTruthy();
        expect(root.shadowRoot.querySelector('button')).toBeTruthy();
    });

    it('opens on button click', () => {
        document.body.innerHTML = '';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');
        const button = root.shadowRoot.querySelector('button.bug-toggle');
        button.click();
        expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
    });
});
