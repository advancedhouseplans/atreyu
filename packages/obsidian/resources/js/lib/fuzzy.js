const WORD_BREAKS = new Set([' ', '-', '/', '_']);

const isWordStart = (haystack, index) => index === 0 || WORD_BREAKS.has(haystack[index - 1]);

/**
 * Score of the best subsequence alignment of `query` in `text`, or null when it does not match.
 * Dynamic programming over (query index, text index) with a running prefix maximum, so O(q * n).
 */
function score(query, text) {
    const haystack = text.toLowerCase();
    let previous = null;

    for (let i = 0; i < query.length; i++) {
        const current = new Array(haystack.length).fill(-Infinity);
        let bestBefore = -Infinity; // max of previous[0..j-2]: any earlier, non-adjacent match

        for (let j = 0; j < haystack.length; j++) {
            if (previous && j >= 2) {
                bestBefore = Math.max(bestBefore, previous[j - 2]);
            }

            if (haystack[j] !== query[i]) {
                continue;
            }

            const wordStart = isWordStart(haystack, j);

            if (previous === null) {
                current[j] = wordStart ? 10 : 1;
            } else {
                const adjacent = j >= 1 ? previous[j - 1] + (wordStart ? 10 : 5) : -Infinity;

                current[j] = Math.max(adjacent, bestBefore + (wordStart ? 10 : 1));
            }
        }

        previous = current;
    }

    const best = Math.max(...(previous ?? [-Infinity]));

    if (best === -Infinity) {
        return null;
    }

    return haystack.startsWith(query) ? best + 15 : best;
}

/** Filters `items` to those whose text contains the query as a subsequence, best match first. */
export function rank(query, items, key = (item) => item.label) {
    const needle = query.trim().toLowerCase();

    if (needle === '') {
        return items;
    }

    return items
        .map((item, order) => ({ item, order, text: key(item), score: score(needle, key(item)) }))
        .filter((entry) => entry.score !== null)
        .sort((a, b) => b.score - a.score || a.text.length - b.text.length || a.order - b.order)
        .map((entry) => entry.item);
}
