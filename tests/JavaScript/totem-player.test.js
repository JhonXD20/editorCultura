import assert from 'node:assert/strict';
import test from 'node:test';
import { createInactivityTimer, useLandscapeLayout } from '../../resources/js/totem-player.js';

function setup(timeoutMs = 120_000) {
    let currentTime = 0;
    let nextId = 0;
    let returns = 0;
    const pending = new Map();
    const timer = createInactivityTimer({
        timeoutMs,
        onIdle: () => returns++,
        now: () => currentTime,
        setTimer: (callback, delay) => {
            const id = ++nextId;
            pending.set(id, { callback, time: currentTime + delay });
            return id;
        },
        clearTimer: (id) => pending.delete(id),
    });

    return {
        timer,
        get returns() { return returns; },
        advance(milliseconds) {
            currentTime += milliseconds;
            for (const [id, entry] of pending) {
                if (entry.time <= currentTime) {
                    pending.delete(id);
                    entry.callback();
                }
            }
        },
    };
}

test('horizontal Android layout is retained even on a narrow or rotated screen', () => {
    assert.equal(useLandscapeLayout('landscape', true), true);
    assert.equal(useLandscapeLayout('landscape', false), true);
    assert.equal(useLandscapeLayout('auto', true), true);
    assert.equal(useLandscapeLayout('auto', false), false);
    assert.equal(useLandscapeLayout('portrait', true), false);
});

test('idle return runs once, only after the configured timeout', () => {
    const session = setup();
    session.advance(119_999);
    assert.equal(session.returns, 0);
    session.advance(1);
    session.advance(120_000);
    assert.equal(session.returns, 1);
});

test('interaction restarts the full timeout', () => {
    const session = setup();
    session.advance(100_000);
    session.timer.reset();
    session.advance(100_000);
    assert.equal(session.returns, 0);
    session.advance(20_000);
    assert.equal(session.returns, 1);
});

test('a tablet that resumes after the deadline returns immediately', () => {
    const session = setup();
    session.advance(30_000);
    session.timer.pause();
    session.advance(150_000);
    assert.equal(session.returns, 0);
    session.timer.resume();
    assert.equal(session.returns, 1);
});

test('resuming before the deadline retains the remaining time', () => {
    const session = setup();
    session.timer.pause();
    session.advance(30_000);
    session.timer.resume();
    session.advance(89_999);
    assert.equal(session.returns, 0);
    session.advance(1);
    assert.equal(session.returns, 1);
});

test('disabled timeout and stopped sessions do not navigate', () => {
    const disabled = setup(0);
    disabled.advance(1_000_000);
    assert.equal(disabled.returns, 0);

    const stopped = setup();
    stopped.timer.stop();
    stopped.timer.reset();
    stopped.timer.resume();
    stopped.advance(1_000_000);
    assert.equal(stopped.returns, 0);
});
