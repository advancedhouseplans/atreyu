/**
 * Whether the mobile dock should be visible after a scroll: always at the top, hidden when scrolling
 * down past the threshold, shown when scrolling up past it, and unchanged for smaller movements.
 */
export function nextDockVisibility({ previousY, y, visible, threshold = 8 }) {
    if (y <= 0) {
        return true;
    }

    if (y - previousY > threshold) {
        return false;
    }

    if (previousY - y > threshold) {
        return true;
    }

    return visible;
}
