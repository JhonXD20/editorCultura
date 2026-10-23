import assert from 'node:assert/strict';
import test from 'node:test';
import { sectionTapDirection } from '../../resources/js/player-navigation.js';

const tap = (extra = {}) => sectionTapDirection({ start: { x: 80, y: 100 }, end: { x: 80, y: 100 }, bounds: { left: 0, width: 1000 }, elapsed: 120, ...extra });

test('section navigation uses stationary taps in each half and ignores the central gap', () => {
    assert.equal(tap(), 'previous');
    assert.equal(tap({ start: { x: 900, y: 100 }, end: { x: 900, y: 100 } }), 'next');
    assert.equal(tap({ start: { x: 500, y: 100 }, end: { x: 500, y: 100 } }), null);
});
test('controls and text selections do not trigger page navigation', () => {
    assert.equal(tap({ interactive: true }), null);
    assert.equal(tap({ selectedText: true }), null);
});
test('scrolls, swipes and long presses are not interpreted as taps', () => {
    assert.equal(tap({ end: { x: 90, y: 200 } }), null);
    assert.equal(tap({ end: { x: 140, y: 100 } }), null);
    assert.equal(tap({ elapsed: 900 }), null);
});
test('missing starts, empty areas and taps outside the display are ignored', () => {
    assert.equal(tap({ start: null }), null);
    assert.equal(tap({ bounds: { left: 0, width: 0 } }), null);
    assert.equal(tap({ bounds: { left: 100, width: 1000 } }), null);
});
