import { releaseVelocity, shouldDismiss } from '../lib/sheet.js';
import { overlay } from './modal.js';

/**
 * Drag-to-dismiss for a bottom sheet. The host provides `close()` and a `[data-ob-sheet-panel]`
 * ancestor of the drag handle; the panel is translated with `dragY` while the pointer is down.
 */
export function sheetDrag() {
    return {
        dragY: 0,
        dragging: false,
        settling: false,
        drag: null,

        startDrag(event) {
            event.currentTarget.setPointerCapture?.(event.pointerId);
            const panel = event.currentTarget.closest('[data-ob-sheet-panel]');

            this.dragging = true;
            this.drag = { startY: event.clientY, lastY: event.clientY, lastTime: event.timeStamp, velocity: 0, height: panel.offsetHeight };
        },

        moveDrag(event) {
            if (!this.dragging) {
                return;
            }

            const elapsed = event.timeStamp - this.drag.lastTime;

            if (elapsed > 0) {
                this.drag.velocity = (event.clientY - this.drag.lastY) / elapsed;
            }

            this.drag.lastY = event.clientY;
            this.drag.lastTime = event.timeStamp;
            this.dragY = Math.max(0, event.clientY - this.drag.startY);
        },

        endDrag(event) {
            if (!this.dragging) {
                return;
            }

            const { height, lastTime } = this.drag;
            const velocity = releaseVelocity(this.drag.velocity, event.timeStamp - lastTime);
            const dismiss = shouldDismiss({ distance: this.dragY, height, velocity });

            this.dragging = false;
            this.drag = null;

            if (dismiss) {
                this.close();
            } else {
                // Animate back to rest; the transition class only applies while settling.
                this.settling = true;
                this.dragY = 0;
                setTimeout(() => {
                    this.settling = false;
                }, 220);
            }
        },

        resetDrag() {
            this.dragY = 0;
            this.dragging = false;
            this.settling = false;
        },
    };
}

export default (config) => ({
    ...overlay(config),
    ...sheetDrag(),

    init() {
        this.initOverlay();
        this.$watch('open', (isOpen) => isOpen && this.resetDrag());
    },
});
