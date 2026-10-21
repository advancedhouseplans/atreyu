// resources/widget/index.js
import { Ring } from './lib/ring.js';
import { installNetworkCapture } from './lib/net.js';
import { readConfig } from './lib/config.js';
import { mountUi } from './lib/ui.js';
import { snapshot } from './lib/snapshot.js';
import { submit } from './lib/submit.js';
import { DEFAULT_DIAGNOSTIC_WINDOW_MS, selectRecentDiagnostics } from './lib/diagnostics.js';
import { startTelemetryActivation } from './lib/activation.js';

(function () {
    if (typeof window === 'undefined') return;

    const scriptEl = document.currentScript || Array.from(document.scripts).find(s => s.src && s.src.endsWith('/widget.js'));
    const config = readConfig(scriptEl);
    if (!config || !config.endpoint) return;

    startTelemetryActivation({
        token: config.token,
        refreshMs: Number(config.activation_refresh_ms) || 60000,
        onToken: token => { config.token = token; },
    });

    const maxRequests = Number(config.diagnostic_requests) || 5;
    const netRing = new Ring(maxRequests);
    installNetworkCapture({
        ring: netRing,
        skipUrlPrefix: config.endpoint,
        activeToken: () => config.token,
    });

    const boot = () => {
        let currentSnapshot = null;
        let currentDiagnostics = null;

        mountUi({
            config,
            onOpen: () => {
                currentDiagnostics = selectRecentDiagnostics({
                    network: netRing.toArray(),
                    events: [],
                    initialDebugId: config.debug_id,
                    initialDebugTimestamp: config.debug_ts,
                    windowMs: Number(config.diagnostic_window_ms) || DEFAULT_DIAGNOSTIC_WINDOW_MS,
                    maxRequests,
                    maxEvents: 0,
                });
            },
            onCapture: async () => {
                currentSnapshot = null;
                const blob = await snapshot({ endpoint: config.endpoint });
                currentSnapshot = blob;
                return URL.createObjectURL(blob);
            },
            onSubmit: async (formData, { close, disable, enable }) => {
                disable();
                try {
                    const diagnostics = currentDiagnostics || { network: [], events: [], debugIds: [] };

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
                        network_log: diagnostics.network,
                        client_events: diagnostics.events,
                        debug_ids: diagnostics.debugIds,
                    };

                    const result = await submit({
                        endpoint: config.endpoint, token: config.token,
                        formData, metadata, snapshotBlob: currentSnapshot,
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
