// resources/widget/index.js
import { Ring } from './lib/ring.js';
import { installNetworkCapture } from './lib/net.js';
import { installEventCapture } from './lib/events.js';
import { readConfig } from './lib/config.js';
import { mountUi } from './lib/ui.js';
import { snapshot } from './lib/snapshot.js';
import { submit } from './lib/submit.js';

(function () {
    if (typeof window === 'undefined') return;

    const scriptEl = document.currentScript || Array.from(document.scripts).find(s => s.src && s.src.endsWith('/widget.js'));
    const config = readConfig(scriptEl);
    if (!config || !config.endpoint) return;

    const netRing = new Ring(50);
    const eventRing = new Ring(100);
    installNetworkCapture({ ring: netRing, skipUrlPrefix: config.endpoint });
    installEventCapture({ ring: eventRing });

    const boot = () => {
        mountUi({
            config,
            onSubmit: async (formData, { close, disable, enable }) => {
                disable();
                try {
                    let snap = null;
                    try { snap = await snapshot(); } catch (_) { /* ignore */ }

                    const network = netRing.toArray();
                    const debugIds = Array.from(new Set(network.map(x => x.debug_id).filter(Boolean)));

                    const metadata = {
                        url: location.href,
                        referrer: document.referrer || null,
                        title: document.title,
                        screen_wh: `${screen.width}x${screen.height}`,
                        viewport_wh: `${innerWidth}x${innerHeight}`,
                        dpr: devicePixelRatio,
                        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        locale: navigator.language,
                        user_agent: navigator.userAgent,
                        app_version: document.querySelector('meta[name=app-version]')?.getAttribute('content') || null,
                        network_log: network,
                        client_events: eventRing.toArray(),
                        debug_ids: debugIds,
                    };

                    const result = await submit({
                        endpoint: config.endpoint, token: config.token,
                        formData, metadata, snapshotBlob: snap,
                    });

                    if (result.ok) {
                        close();
                        alert('Report sent — thanks!');
                    } else {
                        alert(`Failed to send (status ${result.status}). Please try again.`);
                    }
                } catch (e) {
                    alert('Failed to send. Please try again.');
                    console.error(e);
                } finally {
                    enable();
                }
            },
        });
    };

    if (document.body) {
        boot();
    } else {
        new MutationObserver((_, obs) => {
            if (document.body) { obs.disconnect(); boot(); }
        }).observe(document.documentElement, { childList: true, subtree: true });
    }
})();
