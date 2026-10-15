/** Mantém o layout horizontal original, mesmo em tablets com até 992px. */
export function useLandscapeLayout(orientation, isLandscape) {
    return orientation === 'landscape'
        || (orientation === 'auto' && isLandscape);
}

/** O relógio permite testar inatividade sem aguardar minutos reais. */
export function createInactivityTimer({
    timeoutMs,
    onIdle,
    now = Date.now,
    setTimer = setTimeout,
    clearTimer = clearTimeout,
}) {
    let timer;
    let deadline;
    let paused = false;
    let stopped = false;

    function schedule() {
        clearTimer(timer);
        if (paused || stopped || timeoutMs <= 0) return;

        const remaining = deadline - now();
        if (remaining <= 0) {
            stopped = true;
            onIdle();
            return;
        }

        timer = setTimer(schedule, remaining);
    }

    function reset() {
        if (stopped) return;
        deadline = now() + timeoutMs;
        schedule();
    }

    reset();

    return {
        reset,
        pause() {
            paused = true;
            clearTimer(timer);
        },
        resume() {
            paused = false;
            schedule();
        },
        stop() {
            stopped = true;
            clearTimer(timer);
        },
    };
}
