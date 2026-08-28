const STYLE = `
    :host { all: initial; }
    /* Edge tab pinned to bottom-right. Only horizontal motion — height is
       locked so there's no vertical drift. */
    .bug-toggle {
        position: fixed; right: 0; bottom: 24px;
        width: 4px; height: 48px;
        padding: 0; border: 0; background: transparent; cursor: pointer;
        z-index: 2147483647; color: #fff;
        display: flex; align-items: center; justify-content: flex-end;
        transition: width .19s cubic-bezier(.22, 1, .36, 1);
    }
    .bug-toggle:hover,
    .bug-toggle.working { width: 64px; }
    .bug-toggle-shell {
        width: 4px; height: 48px;
        background: #ef4444; /* tailwind red-500 */
        border-radius: 4px 0 0 4px;
        margin-right: 0;
        box-shadow: -2px 4px 10px rgba(0, 0, 0, .18);
        transition: width .19s cubic-bezier(.22, 1, .36, 1),
                    border-radius .19s cubic-bezier(.22, 1, .36, 1),
                    margin-right .19s cubic-bezier(.22, 1, .36, 1),
                    box-shadow .19s ease-out;
        position: relative;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .bug-toggle-icon,
    .bug-toggle-spin {
        position: absolute; opacity: 0;
        transition: opacity .14s ease-out;
    }
    .bug-toggle-icon { color: #fff; line-height: 0; }
    .bug-toggle-icon svg { width: 18px; height: 18px; display: block; fill: currentColor; }
    .bug-toggle-spin {
        width: 14px; height: 14px;
        border: 2px solid #fff; border-top-color: transparent; border-radius: 50%;
        animation: bug-spin .8s linear infinite;
    }
    /* Expanded: shell grows horizontally into a 48×48 circle (same height). */
    .bug-toggle:hover .bug-toggle-shell,
    .bug-toggle.working .bug-toggle-shell {
        width: 48px;
        border-radius: 50%;
        margin-right: 8px;
        box-shadow: -10px 12px 28px rgba(0, 0, 0, .35),
                    0 0 0 1px rgba(255, 255, 255, .06);
    }
    .bug-toggle:hover .bug-toggle-icon { opacity: 1; transition-delay: .08s; }
    .bug-toggle.working .bug-toggle-spin { opacity: 1; transition-delay: .08s; }
    .bug-toggle.working .bug-toggle-icon { opacity: 0 !important; transition-delay: 0s; }
    .bug-toggle[disabled] { cursor: wait; }
    /* Viewport-corner black radial glow — fades in when widget expands. */
    .bug-corner-glow {
        position: fixed; right: 0; bottom: 0;
        width: 200px; height: 200px;
        pointer-events: none; z-index: 2147483646;
        background: radial-gradient(circle at 100% 100%,
            rgba(0, 0, 0, .8) 0,
            rgba(0, 0, 0, .5) 40px,
            rgba(0, 0, 0, 0) 100px);
        opacity: 0;
        transition: opacity .19s cubic-bezier(.22, 1, .36, 1);
    }
    .bug-toggle:hover ~ .bug-corner-glow,
    .bug-toggle.working ~ .bug-corner-glow { opacity: 1; }
    .bug-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 2147483647; align-items: center; justify-content: center; }
    .bug-modal.open { display: flex; }
    .bug-card { background: #fff; padding: 20px; border-radius: 8px; width: min(520px, 92vw);
        max-height: 90vh; overflow-y: auto; font: 14px/1.4 system-ui; color: #111; }
    .bug-card label { display: block; margin-top: 10px; font-weight: 600; }
    .bug-card input, .bug-card textarea, .bug-card select { width: 100%; padding: 6px; box-sizing: border-box; }
    .bug-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; align-items: center; }
    .bug-actions button { padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
    .bug-actions .primary { background: #111; color: #fff; border-color: #111; }
    .bug-actions .primary[disabled] { opacity: .6; cursor: wait; }
    .bug-note { color: #666; font-size: 12px; margin-top: 4px; }
    .bug-preview { margin-top: 10px; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;
        background: #fafafa; min-height: 80px; display: flex; align-items: center; justify-content: center;
        color: #999; font-size: 12px; }
    .bug-preview img { display: block; width: 100%; height: auto; }
    .bug-status { flex: 1; text-align: left; color: #666; font-size: 12px; }
    .bug-spinner { width: 12px; height: 12px; border: 2px solid #ccc; border-top-color: #111;
        border-radius: 50%; animation: bug-spin .8s linear infinite; display: inline-block;
        vertical-align: middle; margin-right: 6px; }
    @keyframes bug-spin { to { transform: rotate(360deg); } }
`;

export function mountUi({ config, onSubmit, onCapture }) {
    const host = document.createElement('div');
    host.id = 'bug-widget-root';
    (document.body || document.documentElement).appendChild(host);
    const root = host.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = STYLE;
    root.appendChild(style);

    root.innerHTML += `
        <button class="bug-toggle" title="Report a bug" aria-label="Report a bug">
            <span class="bug-toggle-shell">
                <span class="bug-toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true"><path d="M256 0c53 0 96 43 96 96l0 3.6c0 15.7-12.7 28.4-28.4 28.4l-135.1 0c-15.7 0-28.4-12.7-28.4-28.4l0-3.6c0-53 43-96 96-96zM41.4 105.4c12.5-12.5 32.8-12.5 45.3 0l64 64c.7 .7 1.3 1.4 1.9 2.1c14.2-7.3 30.4-11.4 47.5-11.4l112 0c17.1 0 33.2 4.1 47.5 11.4c.6-.7 1.2-1.4 1.9-2.1l64-64c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3l-64 64c-.7 .7-1.4 1.3-2.1 1.9c6.2 12 10.1 25.3 11.1 39.5l64.3 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c0 24.6-5.5 47.8-15.4 68.6c2.2 1.3 4.2 2.9 6 4.8l64 64c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0l-63.1-63.1c-24.5 21.8-55.8 36.2-90.3 39.6L272 240c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 239.2c-34.5-3.4-65.8-17.8-90.3-39.6L86.6 502.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l64-64c1.9-1.9 3.9-3.4 6-4.8C101.5 367.8 96 344.6 96 320l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64.3 0c1.1-14.1 5-27.5 11.1-39.5c-.7-.6-1.4-1.2-2.1-1.9l-64-64c-12.5-12.5-12.5-32.8 0-45.3z"/></svg></span>
                <span class="bug-toggle-spin"></span>
            </span>
        </button>
        <div class="bug-corner-glow" aria-hidden="true"></div>
        <div class="bug-modal" role="dialog" aria-modal="true">
            <div class="bug-card">
                <h2 style="margin:0">Report a bug or request</h2>
                <div class="bug-note">Reporting as ${escapeHtml(config.user_name || 'anon')} &lt;${escapeHtml(config.user_email || '')}&gt;</div>
                <label>Type
                    <select name="type">
                        <option value="bug">Bug</option>
                        <option value="feature">Feature request</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>Severity
                    <select name="severity">
                        <option value="low">Low</option>
                        <option value="med" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="blocker">Blocker</option>
                    </select>
                </label>
                <label>Title <input name="title" required></label>
                <label>Description <textarea name="description" rows="4"></textarea></label>
                <label>Additional files <input type="file" name="attachments" multiple></label>
                <label>Screenshot preview
                    <div class="bug-preview">Capturing…</div>
                </label>
                <div class="bug-actions">
                    <span class="bug-status"></span>
                    <button class="cancel" type="button">Cancel</button>
                    <button class="primary submit" type="button">Send report</button>
                </div>
            </div>
        </div>
    `;

    const modal = root.querySelector('.bug-modal');
    const toggle = root.querySelector('.bug-toggle');
    const cancel = root.querySelector('.cancel');
    const submit = root.querySelector('.submit');
    const preview = root.querySelector('.bug-preview');
    const status = root.querySelector('.bug-status');

    async function open() {
        if (toggle.disabled) { return; }
        toggle.disabled = true;
        toggle.classList.add('working');
        preview.innerHTML = 'Capturing…';
        // Yield to browser so the working state paints before html2canvas
        // locks the main thread.
        await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
        try {
            if (onCapture) {
                const dataUrl = await onCapture();
                setPreview(dataUrl);
            }
        } catch (_) {
            setPreview(null);
        } finally {
            toggle.disabled = false;
            toggle.classList.remove('working');
        }
        modal.classList.add('open');
    }
    function close() { modal.classList.remove('open'); }
    function setPreview(dataUrl) {
        preview.innerHTML = dataUrl ? `<img alt="snapshot" src="${dataUrl}">` : 'Snapshot unavailable';
    }
    function setStatus(text) {
        status.innerHTML = text ? `<span class="bug-spinner"></span>${escapeHtml(text)}` : '';
    }

    toggle.addEventListener('click', open);
    cancel.addEventListener('click', close);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { close(); }
        if (e.ctrlKey && e.shiftKey && e.key === 'B') { open(); }
    });

    submit.addEventListener('click', () => {
        const fd = new FormData();
        fd.set('title', root.querySelector('[name=title]').value);
        fd.set('description', root.querySelector('[name=description]').value);
        fd.set('type', root.querySelector('[name=type]').value);
        fd.set('severity', root.querySelector('[name=severity]').value);
        const files = root.querySelector('[name=attachments]').files;
        for (const f of files) { fd.append('attachments[]', f); }
        onSubmit(fd, {
            close,
            disable: () => { submit.disabled = true; submit.textContent = 'Sending…'; setStatus('Sending report…'); },
            enable: () => { submit.disabled = false; submit.textContent = 'Send report'; setStatus(''); },
        });
    });

    return { open, close, root: host };
}

export function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
