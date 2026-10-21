const STYLE = `
    :host {
        all: initial;
        --atreyu-primary: #605dff;
        --atreyu-primary-hover: #4f46e5;
        --atreyu-base-100: #ffffff;
        --atreyu-base-200: #f6f7f9;
        --atreyu-base-300: #e5e7eb;
        --atreyu-content: #18181b;
        --atreyu-muted: #71717a;
        --atreyu-error: #dc2626;
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .bug-toggle {
        position: fixed; left: 20px; bottom: 20px;
        z-index: 2147483646;
        display: flex; align-items: center; justify-content: center;
        gap: 8px; min-height: 42px; box-sizing: border-box; padding: 0 14px;
        border: 1px solid rgba(255, 255, 255,.16); border-radius: 999px;
        background: #18181b; color: #fff; cursor: pointer;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .22), 0 1px 2px rgba(0, 0, 0, .2);
        font: 650 13px/1 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        letter-spacing: -.005em;
        transition: transform .16s ease, background-color .16s ease, box-shadow .16s ease;
    }
    .bug-toggle:hover {
        background: #27272a; transform: translateY(-1px);
        box-shadow: 0 13px 34px rgba(0, 0, 0, .25), 0 1px 2px rgba(0, 0, 0, .2);
    }
    .bug-toggle:focus-visible { outline: 3px solid color-mix(in srgb, var(--atreyu-primary) 35%, transparent); outline-offset: 3px; }
    .bug-toggle-icon { display: grid; place-items: center; line-height: 0; }
    .bug-toggle-icon svg { width: 18px; height: 18px; display: block; }
    .bug-toggle-spin {
        display: none; width: 14px; height: 14px; box-sizing: border-box;
        border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; border-radius: 50%;
        animation: bug-spin .8s linear infinite;
    }
    .bug-toggle.working .bug-toggle-icon { display: none; }
    .bug-toggle.working .bug-toggle-spin { display: block; }
    .bug-toggle.working .bug-toggle-label::after { content: 'Preparing…'; }
    .bug-toggle.working .bug-toggle-label-text { display: none; }
    .bug-toggle[disabled] { cursor: wait; transform: none; }
    .bug-toggle[hidden] { display: none; }
    .bug-modal {
        display: none; position: fixed; inset: 0; z-index: 2147483647;
        box-sizing: border-box; padding: 24px;
        align-items: center; justify-content: center;
        background: rgba(9, 9, 11, .58); backdrop-filter: blur(4px);
    }
    .bug-modal.open { display: flex; animation: bug-fade-in .16s ease-out; }
    .bug-card {
        position: relative; width: min(600px, 100%); max-height: min(860px, 92vh);
        overflow-y: auto; box-sizing: border-box;
        background: var(--atreyu-base-100); color: var(--atreyu-content);
        border: 1px solid rgba(24, 24, 27, .1); border-radius: 16px;
        box-shadow: 0 28px 70px rgba(0, 0, 0, .28);
        font: 14px/1.45 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        animation: bug-pop-in .18s cubic-bezier(.22, 1, .36, 1);
    }
    .bug-header { display: flex; align-items: flex-start; gap: 12px; padding: 22px 24px 18px; border-bottom: 1px solid var(--atreyu-base-300); }
    .bug-mark { display: grid; place-items: center; flex: 0 0 auto; width: 40px; height: 40px; border-radius: 12px; background: color-mix(in srgb, var(--atreyu-primary) 12%, white); color: var(--atreyu-primary); }
    .bug-mark svg { width: 21px; height: 21px; }
    .bug-heading { min-width: 0; flex: 1; }
    .bug-heading h2 { margin: 0; font-size: 18px; line-height: 1.35; font-weight: 700; letter-spacing: -.015em; }
    .bug-heading p { margin: 4px 0 0; color: var(--atreyu-muted); font-size: 13px; }
    .bug-close { display: grid; place-items: center; width: 32px; height: 32px; margin: -4px -6px 0 0; padding: 0; border: 0; border-radius: 9px; background: transparent; color: var(--atreyu-muted); cursor: pointer; }
    .bug-close:hover { background: var(--atreyu-base-200); color: var(--atreyu-content); }
    .bug-close svg { width: 18px; height: 18px; }
    .bug-form-body { display: grid; gap: 16px; padding: 20px 24px 4px; }
    .bug-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .bug-field { display: grid; gap: 7px; min-width: 0; }
    .bug-field > span { font-size: 13px; font-weight: 650; color: var(--atreyu-content); }
    .bug-optional { color: var(--atreyu-muted); font-weight: 400; }
    .bug-control {
        width: 100%; min-height: 42px; box-sizing: border-box; padding: 9px 11px;
        border: 1px solid #d4d4d8; border-radius: 10px; outline: none;
        background: var(--atreyu-base-100); color: var(--atreyu-content); font: inherit;
        transition: border-color .14s ease, box-shadow .14s ease;
    }
    textarea.bug-control { min-height: 104px; resize: vertical; }
    .bug-control:focus { border-color: var(--atreyu-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--atreyu-primary) 18%, transparent); }
    .bug-control::file-selector-button { margin: -5px 10px -5px -6px; padding: 7px 10px; border: 0; border-radius: 7px; background: var(--atreyu-base-200); color: var(--atreyu-content); font: inherit; font-weight: 600; cursor: pointer; }
    .bug-note { color: var(--atreyu-muted); font-size: 12px; }
    .bug-preview { border: 1px solid var(--atreyu-base-300); border-radius: 10px; overflow: hidden;
        background: var(--atreyu-base-200); min-height: 104px; display: flex; align-items: center; justify-content: center;
        color: var(--atreyu-muted); font-size: 12px; }
    .bug-preview img { display: block; width: 100%; height: auto; max-height: 230px; object-fit: cover; object-position: top; }
    .bug-actions { margin-top: 16px; padding: 16px 24px 20px; display: flex; gap: 9px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--atreyu-base-300); }
    .bug-actions button { min-height: 40px; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--atreyu-base-300); background: var(--atreyu-base-100); color: var(--atreyu-content); font: inherit; font-weight: 650; cursor: pointer; }
    .bug-actions button:hover { background: var(--atreyu-base-200); }
    .bug-actions .primary { background: var(--atreyu-primary); color: #fff; border-color: var(--atreyu-primary); }
    .bug-actions .primary:hover { background: var(--atreyu-primary-hover); border-color: var(--atreyu-primary-hover); }
    .bug-actions .primary[disabled] { opacity: .6; cursor: wait; }
    .bug-status { flex: 1; text-align: left; color: var(--atreyu-muted); font-size: 12px; }
    .bug-spinner { width: 12px; height: 12px; border: 2px solid #ccc; border-top-color: #111;
        border-radius: 50%; animation: bug-spin .8s linear infinite; display: inline-block;
        vertical-align: middle; margin-right: 6px; }
    @media (max-width: 560px) {
        .bug-toggle { left: 14px; bottom: 14px; width: 44px; height: 44px; min-height: 44px; padding: 0; }
        .bug-toggle-label { display: none; }
        .bug-modal { align-items: flex-end; padding: 0; }
        .bug-card { width: 100%; max-height: 94vh; border-radius: 16px 16px 0 0; }
        .bug-grid { grid-template-columns: 1fr; }
        .bug-header, .bug-form-body { padding-left: 18px; padding-right: 18px; }
        .bug-actions { padding-left: 18px; padding-right: 18px; }
    }
    @keyframes bug-fade-in { from { opacity: 0; } }
    @keyframes bug-pop-in { from { opacity: 0; transform: translateY(10px) scale(.985); } }
    @keyframes bug-spin { to { transform: rotate(360deg); } }
`;

export function mountUi({ config, onSubmit, onCapture, onOpen }) {
    const host = document.createElement('div');
    host.id = 'bug-widget-root';
    (document.body || document.documentElement).appendChild(host);
    const root = host.attachShadow({ mode: 'open' });

    const style = document.createElement('style');
    style.textContent = STYLE;
    root.appendChild(style);
    const diagnosticMinutes = Math.max(1, Math.round((Number(config.diagnostic_window_ms) || 300000) / 60000));

    root.innerHTML += `
        <button class="bug-toggle" title="Report an issue" aria-label="Report an issue" ${config.launcher === false ? 'hidden' : ''}>
            <span class="bug-toggle-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a3.375 3.375 0 1 1 6.75 0v1.5a3.375 3.375 0 0 1-6.75 0v-1.5Zm0 0H6.75m8.625 0h1.875M8.84 13.5l-1.34 1.34m7.66-1.34 1.34 1.34M9 7.02 7.875 5.625m6.25 0L15 7.02M12 6.375V4.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 12v3A5.25 5.25 0 0 0 10.5 20.25h3A5.25 5.25 0 0 0 18.75 15v-3"/></svg></span>
            <span class="bug-toggle-spin" aria-hidden="true"></span>
            <span class="bug-toggle-label"><span class="bug-toggle-label-text">Report issue</span></span>
        </button>
        <div class="bug-modal" role="dialog" aria-modal="true" aria-labelledby="atreyu-report-title">
            <div class="bug-card" role="document">
                <div class="bug-header">
                    <span class="bug-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    </span>
                    <div class="bug-heading">
                        <h2 id="atreyu-report-title">Report an issue</h2>
                        <p>Send the team a bug or feature request with helpful diagnostic context.</p>
                    </div>
                    <button class="bug-close" type="button" aria-label="Close report form">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form class="bug-form">
                    <div class="bug-form-body">
                        <div class="bug-grid">
                            <label class="bug-field"><span>Type</span>
                                <select class="bug-control" name="type">
                                    <option value="bug">Bug</option>
                                    <option value="feature">Feature request</option>
                                    <option value="other">Other</option>
                                </select>
                            </label>
                            <label class="bug-field"><span>Severity</span>
                                <select class="bug-control" name="severity">
                                    <option value="low">Low</option>
                                    <option value="med" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="blocker">Blocker</option>
                                </select>
                            </label>
                        </div>
                        <label class="bug-field"><span>Title</span><input class="bug-control" name="title" required maxlength="200" placeholder="What happened?"></label>
                        <label class="bug-field"><span>Description <span class="bug-optional">(optional)</span></span><textarea class="bug-control" name="description" rows="4" placeholder="What were you doing, and what did you expect instead?"></textarea></label>
                        <label class="bug-field"><span>Additional files <span class="bug-optional">(optional)</span></span><input class="bug-control" type="file" name="attachments" multiple></label>
                        <div class="bug-field">
                            <span>Automatic screenshot</span>
                            <div class="bug-preview">Capturing…</div>
                            <div class="bug-note">A screenshot and relevant technical context from the last ${diagnosticMinutes} minutes will be included.</div>
                        </div>
                        <div class="bug-note">Reporting as ${escapeHtml(config.user_email || config.user_name || 'Anonymous reporter')}</div>
                    </div>
                <div class="bug-actions">
                    <span class="bug-status"></span>
                    <button class="cancel" type="button">Cancel</button>
                    <button class="primary submit" type="submit">Send report</button>
                </div>
                </form>
            </div>
        </div>
    `;

    const modal = root.querySelector('.bug-modal');
    const toggle = root.querySelector('.bug-toggle');
    const form = root.querySelector('.bug-form');
    const closeButton = root.querySelector('.bug-close');
    const cancel = root.querySelector('.cancel');
    const submit = root.querySelector('.submit');
    const preview = root.querySelector('.bug-preview');
    const status = root.querySelector('.bug-status');
    let opening = false;
    let previousFocus = null;

    async function open(trigger = null) {
        if (opening) { return; }
        opening = true;
        onOpen?.();
        previousFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
        if (trigger instanceof HTMLElement) { trigger.setAttribute('data-atreyu-loading', 'true'); }
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
            opening = false;
            if (trigger instanceof HTMLElement) { trigger.removeAttribute('data-atreyu-loading'); }
            toggle.disabled = false;
            toggle.classList.remove('working');
            modal.classList.add('open');
            root.querySelector('[name=title]').focus();
        }
    }
    function close() {
        modal.classList.remove('open');
        if (previousFocus instanceof HTMLElement) { previousFocus.focus(); }
    }
    function setPreview(dataUrl) {
        preview.innerHTML = dataUrl ? `<img alt="snapshot" src="${dataUrl}">` : 'Snapshot unavailable';
    }
    function setStatus(text) {
        status.innerHTML = text ? `<span class="bug-spinner"></span>${escapeHtml(text)}` : '';
    }

    toggle.addEventListener('click', () => open(toggle));
    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest('[data-atreyu-open]')
            : null;

        if (!trigger || trigger.matches(':disabled') || trigger.getAttribute('aria-disabled') === 'true') {
            return;
        }

        event.preventDefault();
        open(trigger);
    });
    document.addEventListener('atreyu:open', open);
    closeButton.addEventListener('click', close);
    cancel.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) { close(); }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { close(); }
        if (e.ctrlKey && e.shiftKey && e.key === 'B') { open(); }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
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
