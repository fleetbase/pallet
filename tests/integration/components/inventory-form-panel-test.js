import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | inventory-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    // The form's custom fields resolve their owner through the signed-in user's company,
    // which the console always has loaded before a form can open.
    hooks.beforeEach(function () {
        const store = this.owner.lookup('service:store');
        store.push({ data: { id: 'company_1', type: 'company', attributes: { public_id: 'company_1' } } });
        this.owner.lookup('service:current-user').user = { id: 'user_1', company_uuid: 'company_1' };
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
