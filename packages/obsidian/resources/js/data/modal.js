let nextId = 1;

/**
 * Open state shared by modal, sheet and dropdown: registers with the layer stack (the only Esc
 * handler) while open, and responds to the `modal-open` / `modal-close` window events by name.
 * Hosts call `initOverlay()` from `init()` and provide nothing else.
 */
export function overlay({ name = null, open = false } = {}) {
    return {
        open,
        name,
        layerId: `ob-layer-${nextId++}`,

        initOverlay() {
            const sync = (isOpen) => {
                const layers = this.$store.obLayers;

                if (isOpen) {
                    layers.push(this.layerId, () => this.close());
                } else {
                    layers.remove(this.layerId);
                }
            };

            this.$watch('open', sync);
            sync(this.open);
        },

        destroy() {
            this.$store.obLayers.remove(this.layerId);
        },

        show() {
            this.open = true;
        },

        close() {
            this.open = false;
        },

        onOpenEvent(event) {
            if (this.name !== null && event.detail?.name === this.name) {
                this.show();
            }
        },

        onCloseEvent(event) {
            if (!event.detail?.name || event.detail.name === this.name) {
                this.close();
            }
        },
    };
}

export default (config) => ({
    ...overlay(config),

    init() {
        this.initOverlay();
    },
});
