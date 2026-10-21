import { nextDockVisibility } from '../lib/dock.js';

const THRESHOLD = 8;

export default () => ({
    visible: true,
    previousY: 0,

    onScroll() {
        const y = window.scrollY;

        // Small movements accumulate against the last anchor instead of being lost between scroll events.
        if (y > 0 && Math.abs(y - this.previousY) <= THRESHOLD) {
            return;
        }

        this.visible = nextDockVisibility({ previousY: this.previousY, y, visible: this.visible, threshold: THRESHOLD });
        this.previousY = y;
    },
});
