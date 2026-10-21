import { overlay } from './modal.js';
import { sheetDrag } from './sheet.js';

const DESKTOP_QUERY = '(min-width: 640px)';
const ITEMS = '[role=menuitem]:not(:disabled):not([aria-disabled=true])';

export default () => ({
    ...overlay(),
    ...sheetDrag(),
    isDesktop: true,
    isInsideLayer: false,

    init() {
        this.initOverlay();
        this.syncViewport();
        // A popover over a modal or sheet is solid: the layer below already spends the blur budget.
        this.isInsideLayer = this.$root.parentElement?.closest('[data-ob-layer]') != null;
        this.$watch('open', (isOpen) => isOpen && this.resetDrag());
    },

    syncViewport() {
        this.isDesktop = window.matchMedia(DESKTOP_QUERY).matches;
    },

    trigger() {
        return this.$root.querySelector('[data-ob-trigger]');
    },

    syncTrigger() {
        const control = this.trigger()?.querySelector('button, a[href], [tabindex]');

        control?.setAttribute('aria-haspopup', 'menu');
        control?.setAttribute('aria-expanded', String(this.open));
    },

    menu() {
        return this.$root.querySelector('[role=menu]');
    },

    items() {
        return Array.from(this.menu()?.querySelectorAll(ITEMS) ?? []);
    },

    /** Closing is delegated here so a consumer's own click handler on an item cannot replace it. */
    onMenuClick(event) {
        if (event.target.closest('[role=menuitem]:not([aria-disabled=true])')) {
            this.close();
        }
    },

    toggle(event) {
        if (this.open) {
            this.close();
        } else {
            // A keyboard activation reports a click with no pointer (detail 0).
            this.openMenu(event.detail === 0 ? 'first' : 'menu');
        }
    },

    openMenu(focus) {
        this.show();
        // Wait for x-show to display the panel (and the sheet's focus trap to take its first pass).
        setTimeout(() => {
            const items = this.items();
            const target = focus === 'first' ? items[0] : focus === 'last' ? items.at(-1) : this.menu();

            target?.focus({ preventScroll: true });
        }, 40);
    },

    close(restoreFocus = true) {
        if (!this.open) {
            return;
        }

        this.open = false;

        if (restoreFocus) {
            this.trigger()?.querySelector('button, a[href], [tabindex]')?.focus({ preventScroll: true });
        }
    },

    onKeydown(event) {
        const onTrigger = this.trigger()?.contains(event.target);

        if (!this.open) {
            if (onTrigger && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
                event.preventDefault();
                this.openMenu(event.key === 'ArrowDown' ? 'first' : 'last');
            }

            return;
        }

        const items = this.items();
        const current = items.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            const next = current === -1 ? (step === 1 ? 0 : items.length - 1) : (current + step + items.length) % items.length;

            items[next]?.focus();
        } else if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault();
            (event.key === 'Home' ? items[0] : items.at(-1))?.focus();
        } else if (event.key === 'Tab') {
            event.preventDefault();
            this.close();
        } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            this.typeahead(event.key.toLowerCase(), items, current);
        }
    },

    /** Moves focus to the next item whose label starts with the typed letter, wrapping around. */
    typeahead(letter, items, current) {
        const ordered = [...items.slice(current + 1), ...items.slice(0, current + 1)];

        ordered.find((item) => item.textContent.trim().toLowerCase().startsWith(letter))?.focus();
    },
});
