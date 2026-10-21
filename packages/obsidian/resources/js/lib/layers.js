/**
 * A stack of open overlays so a single Esc listener only ever closes the topmost one.
 */
export function createLayerStack() {
    let layers = [];

    return {
        push(id, close) {
            layers = [...layers.filter((layer) => layer.id !== id), { id, close }];
        },

        remove(id) {
            layers = layers.filter((layer) => layer.id !== id);
        },

        top() {
            return layers.at(-1)?.id ?? null;
        },

        escape() {
            const layer = layers.at(-1);

            if (layer) {
                layers = layers.slice(0, -1);
                layer.close();
            }
        },
    };
}
