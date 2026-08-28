const STYLE = `
    :host { all: initial; }
    /* Ambient dot that reveals into a full pill on hover (option B). */
    .bug-toggle {
        position: fixed; bottom: 8px; right: 8px;
        width: 56px; height: 56px; padding: 0;
        border: 0; background: transparent; cursor: pointer;
        z-index: 2147483647;
        display: flex; align-items: center; justify-content: center;
    }
    .bug-toggle-shell {
        width: 8px; height: 8px; border-radius: 50%;
        background: #d33;
        box-shadow: 0 0 0 0 rgba(211, 51, 51, .55);
        transition: width .32s cubic-bezier(.34, 1.56, .64, 1),
                    height .32s cubic-bezier(.34, 1.56, .64, 1),
                    border-radius .32s cubic-bezier(.34, 1.56, .64, 1),
                    box-shadow .32s ease-out;
        animation: bug-idle-pulse 2.4s ease-in-out infinite;
        position: relative;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .bug-toggle-icon,
    .bug-toggle-spin {
        position: absolute; opacity: 0;
        transition: opacity .18s ease-out;
    }
    .bug-toggle-icon { font: 20px/1 system-ui; }
    .bug-toggle-spin {
        width: 14px; height: 14px;
        border: 2px solid #fff; border-top-color: transparent; border-radius: 50%;
        animation: bug-spin .8s linear infinite;
    }
    /* Expanded state: hover on the invisible hit area OR the .working state */
    .bug-toggle:hover .bug-toggle-shell,
    .bug-toggle.working .bug-toggle-shell {
        width: 40px; height: 40px; border-radius: 20px;
        animation: none;
        box-shadow: 0 6px 16px rgba(0, 0, 0, .25);
    }
    .bug-toggle:hover .bug-toggle-icon { opacity: 1; transition-delay: .1s; }
    .bug-toggle.working .bug-toggle-spin { opacity: 1; transition-delay: .1s; }
    .bug-toggle.working .bug-toggle-icon { opacity: 0 !important; transition-delay: 0s; }
    .bug-toggle[disabled] { cursor: wait; }
    @keyframes bug-idle-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(211, 51, 51, .55); }
        50%      { box-shadow: 0 0 0 7px rgba(211, 51, 51, 0); }
    }
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
                <span class="bug-toggle-icon">🐞</span>
                <span class="bug-toggle-spin"></span>
            </span>
        </button>
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
