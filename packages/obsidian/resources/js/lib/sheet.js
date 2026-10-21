/**
 * Whether a released drag should dismiss the sheet. Velocity is px/ms, downward positive.
 */
export function shouldDismiss({ distance, height, velocity }) {
    return distance > 0 && (distance / height >= 0.3 || velocity > 0.5);
}
