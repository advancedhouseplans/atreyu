import { rank } from '../lib/fuzzy.js';
import { overlay } from './modal.js';
import { sheetDrag } from './sheet.js';

const DESKTOP_QUERY = '(min-width: 640px)';
const same = (a, b) => String(a) === String(b);

/**
 * Searchable select. Options are read from the root's `data-options` JSON (observed, so a server
 * re-render of the options refreshes the list); `server` skips client-side filtering.
 */
export default ({ id, multiple = false, server = false, value = null }) => ({
    ...overlay(),
    ...sheetDrag(),
    id,
    multiple,
    server,
    value,
    options: [],
    known: {},
    query: '',
    active: 0,
    panelWidth: 0,
    isDesktop: true,
    isInsideLayer: false,
    observer: null,
    clearing: false,

    init() {
        this.initOverlay();
        this.syncViewport();
        this.readOptions();
        this.isInsideLayer = this.$root.parentElement?.closest('[data-ob-layer]') != null;
        this.observer = new MutationObserver(() => this.readOptions());
        this.observer.observe(this.$root, { attributes: true, attributeFilter: ['data-options'] });
        this.$watch('open', (isOpen) => isOpen && this.resetDrag());
        this.$watch('query', () => {
            this.active = 0;
        });
    },

    destroy() {
        this.$store.obLayers.remove(this.layerId);
        this.observer?.disconnect();
    },

    syncViewport() {
        this.isDesktop = window.matchMedia(DESKTOP_QUERY).matches;
    },

    readOptions() {
        try {
            this.options = JSON.parse(this.$root.dataset.options ?? '[]');
        } catch {
            this.options = [];
        }

        // Remember every option seen so a chip keeps its label after a server search replaces the list.
        this.options.forEach((option) => {
            this.known[String(option.value)] = option;
        });
        this.active = Math.min(this.active, Math.max(this.results.length - 1, 0));
    },

    get selected() {
        if (this.multiple) {
            return Array.isArray(this.value) ? this.value : [];
        }

        return this.value === null || this.value === undefined || this.value === '' ? [] : [this.value];
    },

    get selectedOptions() {
        return this.selected.map((selectedValue) => this.known[String(selectedValue)] ?? { value: selectedValue, label: String(selectedValue) });
    },

    get results() {
        return this.server ? this.options : rank(this.query, this.options);
    },

    get activeId() {
        return this.open && this.results[this.active] ? `${this.id}-option-${this.active}` : null;
    },

    get placeholderText() {
        return !this.multiple && this.selectedOptions[0] ? this.selectedOptions[0].label : null;
    },

    isSelected(option) {
        return this.selected.some((selectedValue) => same(selectedValue, option.value));
    },

    show() {
        this.panelWidth = this.$refs.control.offsetWidth;
        this.open = true;
    },

    close() {
        if (!this.open) {
            return;
        }

        this.open = false;
        this.clearQuery();
    },

    /** Resets the query on the inputs themselves so a `wire:model` bound to them follows. */
    clearQuery() {
        this.query = '';
        this.clearing = true;
        this.$root.querySelectorAll('[data-ob-combobox-input]').forEach((input) => {
            if (input.value !== '') {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        this.clearing = false;
    },

    onControlClick(event) {
        if (this.isDesktop && !event.target.closest('button')) {
            this.show();
            this.$refs.input.focus();
        }
    },

    onInput(event) {
        this.query = event.target.value;

        // Clearing the query programmatically (on close or select) must never reopen the panel.
        if (!this.clearing && !this.open) {
            this.show();
        }
    },

    toggle(option) {
        if (this.multiple) {
            this.value = this.isSelected(option) ? this.selected.filter((selectedValue) => !same(selectedValue, option.value)) : [...this.selected, option.value];
            this.clearQuery();
            this.isDesktop && this.$refs.input.focus();
        } else {
            this.value = option.value;
            this.close();
        }
    },

    remove(option) {
        this.value = this.selected.filter((selectedValue) => !same(selectedValue, option.value));
    },

    move(step) {
        const count = this.results.length;

        this.active = count === 0 ? 0 : (this.active + step + count) % count;
        this.$nextTick(() => this.$refs.list?.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
    },

    onKeydown(event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (this.open) {
                this.move(event.key === 'ArrowDown' ? 1 : -1);
            } else {
                this.show();
            }
        } else if (event.key === 'Enter' && this.open && this.results[this.active]) {
            event.preventDefault();
            this.toggle(this.results[this.active]);
        } else if (event.key === 'Backspace' && this.multiple && event.target.value === '' && this.selected.length > 0) {
            this.remove(this.selectedOptions.at(-1));
        } else if (event.key === 'Tab') {
            this.close();
        }
    },
});
