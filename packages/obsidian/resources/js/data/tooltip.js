const SHOW_DELAY_MS = 400;

export default () => ({
    open: false,
    timer: null,

    show() {
        // Touch devices have no hover; a tooltip would only get stuck open after a tap.
        if (window.matchMedia('(hover: none)').matches) {
            return;
        }

        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.open = true;
        }, SHOW_DELAY_MS);
    },

    hide() {
        clearTimeout(this.timer);
        this.open = false;
    },
});
