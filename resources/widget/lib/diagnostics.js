export const DEFAULT_DIAGNOSTIC_WINDOW_MS = 5 * 60 * 1000;

export function selectRecentDiagnostics({
    network,
    events,
    initialDebugId,
    initialDebugTimestamp,
    windowMs = DEFAULT_DIAGNOSTIC_WINDOW_MS,
    maxRequests = 5,
    maxEvents = 0,
    now = Date.now(),
}) {
    const cutoff = now - windowMs;
    const isRecent = (item) => typeof item?.ts !== 'number' || item.ts >= cutoff;
    const recentNetwork = network.filter(isRecent).slice(-Math.max(0, maxRequests));
    const recentEvents = events.filter(isRecent).slice(-Math.max(0, maxEvents));
    const initialDebugIsRecent = initialDebugId
        && (typeof initialDebugTimestamp !== 'number' || initialDebugTimestamp >= cutoff);
    const debugIds = Array.from(new Set([
        initialDebugIsRecent ? initialDebugId : null,
        ...recentNetwork.map(item => item.debug_id),
    ].filter(Boolean))).slice(-Math.max(0, maxRequests));

    return {
        network: recentNetwork,
        events: recentEvents,
        debugIds,
        windowMs,
    };
}
