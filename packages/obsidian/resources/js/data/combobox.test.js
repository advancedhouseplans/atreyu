import { expect, test } from 'vite-plus/test';
import combobox from './combobox.js';

/** Builds the data object with just enough of Alpine's magics for the open/close/query paths. */
function make(config) {
    const inputs = [{ value: '' }];
    const data = combobox({ id: 'cb', ...config });

    inputs.forEach((input) => {
        input.dispatchEvent = () => data.onInput({ target: input });
    });

    Object.assign(data, {
        $root: { dataset: { options: JSON.stringify([{ value: 1, label: 'Ada' }, { value: 2, label: 'Grace' }]) }, querySelectorAll: () => inputs, parentElement: null },
        $refs: { control: { offsetWidth: 200 }, input: { focus() {} }, list: null },
        $store: { obLayers: { push() {}, remove() {} } },
        $watch() {},
        $nextTick() {},
    });
    data.readOptions();

    return { data, inputs };
}

function type(data, inputs, text) {
    inputs[0].value = text;
    data.onInput({ target: inputs[0] });
}

test('typing opens the panel and close() keeps it closed after clearing the query', () => {
    const { data, inputs } = make({ multiple: true });

    type(data, inputs, 'ad');
    expect(data.open).toBe(true);

    data.close();
    expect(data.open).toBe(false);
    expect(data.query).toBe('');
    expect(inputs[0].value).toBe('');
});

test('selecting in single mode closes and stays closed', () => {
    const { data, inputs } = make({ multiple: false });

    type(data, inputs, 'gr');
    data.toggle(data.results[0]);

    expect(data.value).toBe(2);
    expect(data.open).toBe(false);
});

test('selecting in multiple mode clears the query without reopening a closed panel', () => {
    const { data, inputs } = make({ multiple: true });

    type(data, inputs, 'ad');
    data.toggle(data.results[0]);

    expect(data.value).toEqual([1]);
    expect(data.query).toBe('');
});

test('a preselected value shows its label from the initial options even in server mode', () => {
    const { data } = make({ server: true, value: 2 });

    expect(data.selectedOptions).toEqual([{ value: 2, label: 'Grace' }]);
});
