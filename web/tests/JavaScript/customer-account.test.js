import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const script = await readFile(new URL('../../resources/js/customer-account.js', import.meta.url), 'utf8');

function setup({ nativeDialog = true, confirm = true } = {}) {
    const handlers = {};
    const form = { submitted: false, requestSubmit() { this.submitted = true; } };
    const dialog = {
        dataset: { confirmMessage: 'Remove only QueueFlow guest data?' },
        opened: false,
        closed: false,
        showModal() { this.opened = true; },
        close() { this.closed = true; },
        querySelector(selector) { return selector === 'form' ? form : cancel; },
    };
    if (!nativeDialog) delete dialog.showModal;
    const cancel = { addEventListener(name, fn) { handlers[`cancel:${name}`] = fn; } };
    const trigger = { dataset: { dialogId: 'confirm' }, addEventListener(name, fn) { handlers[`trigger:${name}`] = fn; } };
    const document = { querySelector: () => trigger, getElementById: () => dialog };
    const window = { confirm: () => confirm };
    vm.runInNewContext(script, { document, window });
    return { handlers, dialog, form };
}

test('opens the confirmation dialog and Cancel closes it without submitting', () => {
    const { handlers, dialog, form } = setup();
    handlers['trigger:click']();
    assert.equal(dialog.opened, true);
    handlers['cancel:click']();
    assert.equal(dialog.closed, true);
    assert.equal(form.submitted, false);
});

test('uses an explicit confirmation fallback when native dialog is unavailable', () => {
    const { handlers, form } = setup({ nativeDialog: false });
    handlers['trigger:click']();
    assert.equal(form.submitted, true);
});

test('does not submit when the fallback confirmation is cancelled', () => {
    const { handlers, form } = setup({ nativeDialog: false, confirm: false });
    handlers['trigger:click']();
    assert.equal(form.submitted, false);
});
