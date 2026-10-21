let loader;

export async function snapshot({ endpoint }) {
    await loadSnapshotRuntime(endpoint);
    return globalThis.AtreyuSnapshot.capture();
}

function loadSnapshotRuntime(endpoint) {
    if (globalThis.AtreyuSnapshot?.capture) return Promise.resolve();
    if (loader) return loader;

    loader = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `${endpoint.replace(/\/$/, '')}/widget-snapshot.js`;
        script.async = true;
        script.onload = () => globalThis.AtreyuSnapshot?.capture
            ? resolve()
            : reject(new Error('Atreyu snapshot runtime did not initialize.'));
        script.onerror = () => reject(new Error('Atreyu snapshot runtime failed to load.'));
        document.head.appendChild(script);
    }).catch((error) => {
        loader = null;
        throw error;
    });

    return loader;
}
