const FIELDS = new Set(['input', 'textarea', 'select']);

/** True when the keydown should open the command palette: Cmd/Ctrl+K anywhere, or `/` outside text fields. */
export function paletteShortcut(event) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        return true;
    }

    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
        return false;
    }

    const target = event.target;

    return !(FIELDS.has(target?.tagName?.toLowerCase()) || target?.isContentEditable);
}
