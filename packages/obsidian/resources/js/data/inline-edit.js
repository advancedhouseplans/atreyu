import { createInlineEdit } from '../lib/inline-edit.js';

const SAVED_FLASH_MS = 1200;
let nextId = 1;

export default ({ value, action, name, type = 'text', options = [] }) => {
    let host;
    let pendingValue = null;

    return {
        ...createInlineEdit({
            value,
            // `first()` returns '' when the field has no error.
            save: async (draft) => {
                await host.$wire.$call(action, draft);

                const error = host.$wire.$errors.first(name) || null;

                if (error === null) {
                    host.saved = true;
                    setTimeout(() => (host.saved = false), SAVED_FLASH_MS);
                }

                return error;
            },
        }),
        saved: false,
        layerId: `ob-inline-edit-${nextId++}`,

        init() {
            host = this;

            this.$watch('editing', (isEditing) => {
                if (isEditing) {
                    this.$store.obLayers.push(this.layerId, () => this.cancel());
                    this.$nextTick(() => {
                        this.$refs.field.focus();
                        this.$refs.field.select?.();
                    });
                } else {
                    this.$store.obLayers.remove(this.layerId);

                    // A server value that morphed in mid-edit applies once editing ends.
                    if (pendingValue !== null) {
                        this.value = this.draft = pendingValue;
                        pendingValue = null;
                    }
                }
            });

            // A server re-render with a new value changes data-value; morph does not re-run x-data.
            new MutationObserver(() => {
                if (this.editing || this.saving) {
                    pendingValue = this.$root.dataset.value;
                } else {
                    this.value = this.$root.dataset.value;
                }
            }).observe(this.$root, { attributes: true, attributeFilter: ['data-value'] });
        },

        destroy() {
            this.$store.obLayers.remove(this.layerId);
        },

        displayText() {
            if (type === 'select') {
                return options.find((option) => String(option.value) === String(this.value ?? ''))?.label ?? '';
            }

            return String(this.value ?? '');
        },
    };
};
