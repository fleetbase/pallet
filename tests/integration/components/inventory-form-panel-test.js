import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | inventory-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new inventory', async function (assert) {
        this.inventory = this.owner.lookup('service:store').createRecord('inventory', {});

        await render(hbs`<InventoryFormPanel @inventory={{this.inventory}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('Add Inventory');
    });
});
