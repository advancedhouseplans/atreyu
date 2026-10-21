import { rank } from '../lib/fuzzy.js';
import { paletteShortcut } from '../lib/shortcuts.js';
import { overlay } from './modal.js';

const RECENT_KEY = 'ob-command-recent';
const RECENT_LIMIT = 5;
const SAFE_HREF = /^(\/|https?:)/;

let nextItemId = 1;

function readRecent() {
    try {
        const entries = JSON.parse(localStorage.getItem(RECENT_KEY));

        return Array.isArray(entries) ? entries.filter((entry) => typeof entry?.href === 'string' && SAFE_HREF.test(entry.href)).slice(0, RECENT_LIMIT) : [];
    } catch {
        return [];
    }
}

/**
 * Command palette: client-ranked actions plus a slot of server results. Arrow keys move through
 * every visible `[data-ob-command-item]` in DOM order; the active item is tracked as an element so
 * a server re-render of the slot keeps it when it still exists.
 */
export default ({ actions = [], icons = {} }) => ({
    ...overlay(),
    actions,
    icons,
    query: '',
    recent: [],
    activeEl: null,
    isEmpty: false,
    observer: null,

    init() {
        this.initOverlay();
        this.$watch('open', (isOpen) => {
            if (isOpen) {
                this.recent = readRecent();
                this.resetQuery();
                this.$nextTick(() => this.refresh());
            }
        });
        this.$watch('query', () => {
            this.activeEl = null;
            this.$nextTick(() => this.refresh());
        });
        // $refs are registered after a parent's init, so wait a tick before observing the list.
        this.$nextTick(() => {
            this.observer = new MutationObserver(() => this.refresh());
            this.observer.observe(this.$refs.list, { childList: true, subtree: true });
        });
    },

    destroy() {
        this.$store.obLayers.remove(this.layerId);
        this.observer?.disconnect();
    },

    get visibleActions() {
        return this.query.trim() === '' ? this.actions : rank(this.query, this.actions);
    },

    get showRecent() {
        return this.query.trim() === '' && this.recent.length > 0;
    },

    iconFor(name) {
        return this.icons[name] ?? '';
    },

    /** Empties the input itself so a forwarded `wire:model` follows. */
    resetQuery() {
        this.query = '';

        if (this.$refs.input.value !== '') {
            this.$refs.input.value = '';
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },

    onShortcut(event) {
        if (paletteShortcut(event)) {
            event.preventDefault();
            this.open ? this.close() : this.show();
        }
    },

    items() {
        return Array.from(this.$refs.list.querySelectorAll('[data-ob-command-item]')).filter((item) => item.offsetParent !== null);
    },

    /** Keeps the active item valid (first by default) and mirrors it to aria-selected / aria-activedescendant. */
    refresh() {
        const items = this.items();

        if (!this.activeEl?.isConnected || !items.includes(this.activeEl)) {
            this.activeEl = items[0] ?? null;
        }

        items.forEach((item) => {
            item.id ||= `ob-command-item-${nextItemId++}`;
            item.setAttribute('aria-selected', String(item === this.activeEl));
        });

        this.isEmpty = items.length === 0;

        if (this.activeEl) {
            this.$refs.input.setAttribute('aria-activedescendant', this.activeEl.id);
        } else {
            this.$refs.input.removeAttribute('aria-activedescendant');
        }
    },

    move(step) {
        const items = this.items();

        if (items.length === 0) {
            return;
        }

        this.activeEl = items[(items.indexOf(this.activeEl) + step + items.length) % items.length];
        this.refresh();
        this.activeEl.scrollIntoView({ block: 'nearest' });
    },

    onKeydown(event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            this.move(event.key === 'ArrowDown' ? 1 : -1);
        } else if (event.key === 'Enter' && this.activeEl) {
            event.preventDefault();
            this.activeEl.click();
        }
    },

    onHover(event) {
        const item = event.target.closest('[data-ob-command-item]');

        if (item && item !== this.activeEl) {
            this.activeEl = item;
            this.refresh();
        }
    },

    onItemClick(event) {
        const item = event.target.closest('[data-ob-command-item]');

        if (!item) {
            return;
        }

        const href = item.getAttribute('href');

        if (href) {
            this.remember({
                label: item.querySelector('[data-ob-label]')?.textContent.trim() ?? item.textContent.trim(),
                href,
                icon: item.dataset.icon ?? null,
            });
        }

        if (item.dataset.event) {
            window.dispatchEvent(new CustomEvent(item.dataset.event));
        }

        this.close();
    },

    remember(entry) {
        try {
            const entries = [entry, ...readRecent().filter((existing) => existing.href !== entry.href)].slice(0, RECENT_LIMIT);

            localStorage.setItem(RECENT_KEY, JSON.stringify(entries));
        } catch {
            // Storage can be blocked; the palette still works without a recent list.
        }
    },
});
