import { describe, it, expect, vi } from 'vite-plus/test';
import { mountUi } from './ui.js';

describe('mountUi', () => {
    it('mounts a shadow root with a button', () => {
        document.body.innerHTML = '';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');
        expect(root).toBeTruthy();
        expect(root.shadowRoot.querySelector('button')).toBeTruthy();
    });

    it('can hide the built-in launcher without disabling native triggers', () => {
        document.body.innerHTML = '';
        mountUi({ config: { launcher: false }, onSubmit: vi.fn() });
        const root = document.getElementById('bug-widget-root');

        expect(root.shadowRoot.querySelector('.bug-toggle').hidden).toBe(true);
    });

    it('opens on button click', async () => {
        document.body.innerHTML = '';
        const onOpen = vi.fn();
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn(), onOpen });
        const root = document.getElementById('bug-widget-root');
        const button = root.shadowRoot.querySelector('button.bug-toggle');
        button.click();
        expect(onOpen).toHaveBeenCalledOnce();
        await vi.waitFor(() => {
            expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
        });
    });

    it('opens from an application-owned trigger', async () => {
        document.body.innerHTML = '<button class="host-app-button" data-atreyu-open>Report a problem</button>';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });

        document.querySelector('.host-app-button').click();

        await vi.waitFor(() => {
            const root = document.getElementById('bug-widget-root');
            expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
        });
    });

    it('opens from a trigger added after the widget mounts', async () => {
        document.body.innerHTML = '';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });
        const trigger = document.createElement('button');
        trigger.setAttribute('data-atreyu-open', '');
        document.body.appendChild(trigger);

        trigger.click();

        await vi.waitFor(() => {
            const root = document.getElementById('bug-widget-root');
            expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
        });
    });

    it('opens from a programmatic event', async () => {
        document.body.innerHTML = '';
        mountUi({ config: { user_name: 'A', user_email: 'a@b' }, onSubmit: vi.fn() });

        document.dispatchEvent(new CustomEvent('atreyu:open'));

        await vi.waitFor(() => {
            const root = document.getElementById('bug-widget-root');
            expect(root.shadowRoot.querySelector('.bug-modal').classList.contains('open')).toBe(true);
        });
    });
});
