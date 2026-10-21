/**
 * A capped queue of auto-dismissing toasts. `onChange` receives the new `items` after every mutation
 * so a reactive host can mirror them.
 */
export function createToastQueue({ max = 3, duration = 4000, setTimer = setTimeout, clearTimer = clearTimeout, onChange = () => {} } = {}) {
    const timers = new Map();
    let nextId = 1;

    const queue = {
        items: [],

        push({ message, variant = 'neutral', action = null }) {
            const id = nextId++;
            queue.items = [...queue.items, { id, message, variant, action }];
            start(id, duration);

            while (queue.items.length > max) {
                queue.dismiss(queue.items[0].id);
            }

            onChange(queue.items);

            return id;
        },

        dismiss(id) {
            stop(id);
            timers.delete(id);
            queue.items = queue.items.filter((toast) => toast.id !== id);
            onChange(queue.items);
        },

        pause(id) {
            const timer = timers.get(id);

            if (timer?.handle != null) {
                clearTimer(timer.handle);
                timer.remaining -= Date.now() - timer.startedAt;
                timer.handle = null;
            }
        },

        resume(id) {
            const timer = timers.get(id);

            if (timer && timer.handle === null) {
                start(id, timer.remaining);
            }
        },
    };

    function start(id, remaining) {
        timers.set(id, {
            remaining,
            startedAt: Date.now(),
            handle: setTimer(() => queue.dismiss(id), remaining),
        });
    }

    function stop(id) {
        const handle = timers.get(id)?.handle;

        if (handle != null) {
            clearTimer(handle);
        }
    }

    return queue;
}
