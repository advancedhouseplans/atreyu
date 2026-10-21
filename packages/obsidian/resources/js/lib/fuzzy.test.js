import { expect, test } from 'vite-plus/test';
import { rank } from './fuzzy.js';

const L = (...xs) => xs.map((label) => ({ label }));

test('prefix and word starts win', () => expect(rank('nt', L('Accounts', 'New task')).map((i) => i.label)).toEqual(['New task', 'Accounts']));
test('ties go to the shorter label', () => expect(rank('rep', L('Report settings', 'Reports', 'Projects')).map((i) => i.label)).toEqual(['Reports', 'Report settings']));
test('empty query keeps order', () => expect(rank('', L('b', 'a')).map((i) => i.label)).toEqual(['b', 'a']));
test('non-matching items are dropped', () => expect(rank('zz', L('Tasks'))).toEqual([]));
test('equal scores and lengths keep original order', () => expect(rank('a', L('ab', 'ac')).map((i) => i.label)).toEqual(['ab', 'ac']));
test('a custom key selects the text', () => expect(rank('b', [{ name: 'abc' }, { name: 'b' }], (i) => i.name)).toEqual([{ name: 'b' }, { name: 'abc' }]));
test('the best alignment wins, not the leftmost', () => expect(rank('ct', L('Cost center', 'Create task')).map((i) => i.label)).toEqual(['Create task', 'Cost center']));
