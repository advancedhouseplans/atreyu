const STYLE = `
    :host { all: initial; }
    .bug-toggle { position: fixed; bottom: 16px; right: 16px; width: 40px; height: 40px;
        border-radius: 20px; border: 0; background: #d33; color: #fff; font: 600 14px/40px system-ui;
        cursor: pointer; z-index: 2147483647; }
    .bug-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 2147483647; align-items: center; justify-content: center; }
    .bug-modal.open { display: flex; }
    .bug-card { background: #fff; padding: 20px; border-radius: 8px; width: min(520px, 92vw);
        font: 14px/1.4 system-ui; color: #111; }
    .bug-card label { display: block; margin-top: 10px; font-weight: 600; }
    .bug-card input, .bug-card textarea, .bug-card select { width: 100%; padding: 6px; box-sizing: border-box; }
    .bug-actions { margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end; }
    .bug-actions button { padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
    .bug-actions .primary { background: #111; color: #fff; border-color: #111; }
    .bug-note { color: #666; font-size: 12px; margin-top: 4px; }
`;

export function mountUi({ config, onSubmit }) {
    const host = document.createElement('div');
    host.id = 'bug-widget-root';
    (document.body || document.documentElement).appendChild(host);
    const root = host.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = STYLE;
    root.appendChild(style);

    root.innerHTML += `
        <button class="bug-toggle" title="Report a bug">🐞</button>
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
                <div class="bug-actions">
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

    function open() { modal.classList.add('open'); }
    function close() { modal.classList.remove('open'); }

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
            disable: () => { submit.disabled = true; },
            enable: () => { submit.disabled = false; },
        });
    });

    return { open, close, root: host };
}

export function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
