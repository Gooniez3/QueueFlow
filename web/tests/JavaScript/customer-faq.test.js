import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const script = await readFile(new URL('../../resources/js/customer-faq.js', import.meta.url), 'utf8');

function setup() {
    const panels = [{ hidden: true }, { hidden: true }];
    const triggers = panels.map((panel, index) => ({
        attributes: { 'aria-controls': `faq-answer-${index + 1}`, 'aria-expanded': 'false' },
        handlers: {},
        addEventListener(name, handler) { this.handlers[name] = handler; },
        getAttribute(name) { return this.attributes[name]; },
        setAttribute(name, value) { this.attributes[name] = value; },
    }));
    const document = {
        querySelectorAll: () => triggers,
        getElementById: (id) => panels[Number(id.at(-1)) - 1] ?? null,
    };

    vm.runInNewContext(script, { document });

    return { panels, triggers };
}

test('expands and collapses an FAQ answer', () => {
    const { panels, triggers } = setup();

    triggers[0].handlers.click();
    assert.equal(triggers[0].attributes['aria-expanded'], 'true');
    assert.equal(panels[0].hidden, false);

    triggers[0].handlers.click();
    assert.equal(triggers[0].attributes['aria-expanded'], 'false');
    assert.equal(panels[0].hidden, true);
});

test('keeps independently expanded FAQ answers open', () => {
    const { panels, triggers } = setup();

    triggers[0].handlers.click();
    triggers[1].handlers.click();

    assert.equal(triggers[0].attributes['aria-expanded'], 'true');
    assert.equal(triggers[1].attributes['aria-expanded'], 'true');
    assert.equal(panels[0].hidden, false);
    assert.equal(panels[1].hidden, false);
});
