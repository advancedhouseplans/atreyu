const WORD_BREAKS = new Set([' ', '-', '/', '_']);

/**
 * Greedy case-insensitive subsequence score, or null when the query does not match.
 * ponytail: leftmost match only, so a later better alignment is not searched; fine for short labels.
 */
function score(query, text) {
    const haystack = text.toLowerCase();
    let total = 0;
    let position = 0;
    let previous = -2;

    for (const char of query) {
        const index = haystack.indexOf(char, position);

        if (index === -1) {
            return null;
        }

        if (index === 0 || WORD_BREAKS.has(haystack[index - 1])) {
            total += 10;
        } else if (index === previous + 1) {
            total += 5;
        } else {
            total += 1;
        }

        previous = index;
        position = index + 1;
    }

    return haystack.startsWith(query) ? total + 15 : total;
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
