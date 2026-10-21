const normalize = (x) => String(x ?? '').trim();

/**
 * Edit-in-place state machine. `save(draft)` resolves to an error message, or null on success; a rejection becomes a generic error.
 * Methods use `this` so the object stays reactive when Alpine spreads it into a component.
 */
export function createInlineEdit({ value, save }) {
    return {
        value,
        draft: value,
        editing: false,
        saving: false,
        error: null,

        start() {
            this.draft = this.value;
            this.error = null;
            this.editing = true;
        },

        cancel() {
            this.draft = this.value;
            this.error = null;
            this.editing = false;
        },

        async commit() {
            if (!this.editing || this.saving) {
                return;
            }

            if (normalize(this.draft) === normalize(this.value)) {
                this.cancel();

                return;
            }

            this.saving = true;
            this.error = null;

            try {
                this.error = await save(normalize(this.draft) === '' ? null : this.draft);
            } catch {
                this.error = "Couldn't save. Try again.";
            } finally {
                this.saving = false;
            }

            if (this.error === null) {
                this.value = this.draft;
                this.editing = false;
            }
        },
    };
}
