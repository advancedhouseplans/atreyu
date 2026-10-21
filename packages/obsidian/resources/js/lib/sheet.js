/**
 * Whether a released drag should dismiss the sheet. Velocity is px/ms, downward positive.
 */
export function shouldDismiss({ distance, height, velocity }) {
    return distance > 0 && (distance / height >= 0.3 || velocity > 0.5);
}

const STALE_AFTER_MS = 100;

/**
 * The speed to judge a release by: a pointer that rested before lifting is not still moving.
 */
export function releaseVelocity(lastVelocity, msSinceLastMove) {
    return msSinceLastMove > STALE_AFTER_MS ? 0 : lastVelocity;
}
