/* eslint-disable */
import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | inventory-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    hooks.beforeEach(function () {
        // TEMP DEBUG
        const Ember = window.Ember || require('ember').default;
        Ember.onerror = (e) => {
            console.error('DEBUGSTACK ' + (e && e.stack));
        };
        window.addEventListener('error', (ev) => console.error('DEBUGSTACK2 ' + (ev.error && ev.error.stack)));
    });

    test('it titles a new inventory', async function (assert) {
        // the inventory route always creates the record with a fresh batch
        const store = this.owner.lookup('service:store');
        this.inventory = store.createRecord('inventory', { type: 'pallet-inventory', batch: store.createRecord('batch'), meta: {} });

        await render(hbs`<InventoryFormPanel @inventory={{this.inventory}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('Add Inventory');
    });
});
