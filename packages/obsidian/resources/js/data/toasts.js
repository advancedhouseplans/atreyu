import { createToastQueue } from '../lib/toasts.js';

export default (config = {}) => ({
    items: [],
    queue: null,

    init() {
        this.queue = createToastQueue({
            ...config,
            onChange: (items) => {
                this.items = [...items];
            },
        });
    },

    add(detail) {
        if (detail?.message) {
            this.queue.push(detail);
        }
    },

    dismiss(id) {
        this.queue.dismiss(id);
    },

    pause(id) {
        this.queue.pause(id);
    },

    resume(id) {
        this.queue.resume(id);
    },
});
